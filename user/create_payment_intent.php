<?php
/**
 * Create PayMongo Payment Intent for GCash
 * This endpoint creates a payment source for GCash payments
 */

session_start();
header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../config/paymongo.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Please login to proceed with payment'
    ]);
    exit;
}

// Check if PayMongo is configured
if (empty(PAYMONGO_SECRET_KEY) || PAYMONGO_SECRET_KEY === 'sk_test_YOUR_SECRET_KEY_HERE') {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Payment system is not configured. Please contact the administrator.'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

try {
    // Log request start
    error_log('=== Payment Intent Request Started ===');
    error_log('User ID: ' . $_SESSION['user_id']);
    error_log('Session data: ' . json_encode($_SESSION));
    
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    $rawInput = file_get_contents('php://input');
    error_log('Raw input: ' . $rawInput);
    
    $data = json_decode($rawInput, true);
    
    if (!isset($data['reservation_id'])) {
        throw new Exception('Reservation ID is required');
    }
    
    $reservation_id = $data['reservation_id'];
    $payment_type = $data['payment_type'] ?? 'downpayment'; // 'downpayment' or 'full_payment'
    $user_id = $_SESSION['user_id'];
    
    // Get reservation details
    $stmt = $pdo->prepare("
        SELECT * FROM reservations 
        WHERE reservation_id = ? AND user_id = ?
    ");
    $stmt->execute([$reservation_id, $user_id]);
    $reservation = $stmt->fetch();
    
    if (!$reservation) {
        throw new Exception('Reservation not found');
    }
    
    error_log('Reservation found: ' . json_encode([
        'reservation_id' => $reservation_id,
        'status' => $reservation['status'],
        'downpayment_verified' => $reservation['downpayment_verified'],
        'full_payment_paid' => $reservation['full_payment_paid'],
        'total_amount' => $reservation['total_amount'],
        'downpayment_amount' => $reservation['downpayment_amount'],
        'payment_type_requested' => $payment_type
    ]));
    
    // Determine amount and validate based on payment type
    if ($payment_type === 'full_payment') {
        // For full payment, check if reservation fee / downpayment is paid
        if ($reservation['downpayment_paid'] != 1 && $reservation['downpayment_verified'] != 1) {
            error_log('Full payment rejected: downpayment_paid=' . $reservation['downpayment_paid'] . ', downpayment_verified=' . $reservation['downpayment_verified']);
            throw new Exception('Reservation fee must be paid before paying remaining balance');
        }
        if ($reservation['full_payment_paid'] == 1) {
            error_log('Full payment rejected: already paid');
            throw new Exception('Full payment has already been made');
        }
        
        // Calculate remaining balance
        $amount = $reservation['total_amount'] - $reservation['downpayment_amount'];
        error_log('Full payment amount calculated: ' . $amount);
        $payment_description = 'AR Homes Resort - Reservation #' . $reservation_id . ' - Remaining Balance';
    } else {
        // For downpayment
        if ($reservation['status'] !== 'pending_payment') {
            throw new Exception('This reservation has already been processed');
        }
        
        $amount = $reservation['downpayment_amount'];
        $payment_description = 'AR Homes Resort - Reservation #' . $reservation_id . ' - Downpayment';
    }
    
    $amount_in_centavos = (int)($amount * 100); // PayMongo requires amount in centavos
    
    // Build success URL with reservation ID and payment type
    $success_url = PAYMONGO_SUCCESS_URL . '?reservation_id=' . $reservation_id . '&payment_type=' . urlencode($payment_type);
    
    // Prepare checkout session data with automatic redirect back to resort
    $session_data = [
        'data' => [
            'attributes' => [
                'send_email_receipt' => false,
                'show_description' => true,
                'show_line_items' => true,
                'line_items' => [
                    [
                        'currency' => 'PHP',
                        'amount' => $amount_in_centavos,
                        'name' => ($payment_type === 'full_payment' ? 'Full Balance Payment' : 'Reservation Fee (Downpayment)'),
                        'quantity' => 1
                    ]
                ],
                'payment_method_types' => ['card', 'gcash', 'paymaya', 'dob', 'dob_ubp', 'qrph'],
                'description' => $payment_description,
                'reference_number' => $reservation_id,
                'success_url' => $success_url,
                'cancel_url' => defined('PAYMONGO_FAILED_URL') ? PAYMONGO_FAILED_URL . '?reservation_id=' . $reservation_id : $success_url
            ]
        ]
    ];
    
    // Attempt to create Checkout Session for automatic redirect
    $result = makePaymongoRequest('/checkout_sessions', 'POST', $session_data);
    
    if ($result['success'] && !empty($result['data']['data']['attributes']['checkout_url'])) {
        $link = $result['data']['data'];
        $link_id = $link['id'];
        $checkout_url = $link['attributes']['checkout_url'];
    } else {
        // Fallback to Links API
        $link_data = [
            'data' => [
                'attributes' => [
                    'amount' => $amount_in_centavos,
                    'description' => $payment_description,
                    'remarks' => 'Reservation ID: ' . $reservation_id . ' - Payment Type: ' . $payment_type
                ]
            ]
        ];
        
        $result = makePaymongoRequest('/links', 'POST', $link_data);
        
        if (!$result['success']) {
            $error_message = 'Failed to create payment session';
            if ($result['http_code'] == 401) {
                $error_message = 'Payment system authentication failed. Please contact administrator.';
            } elseif (isset($result['data']['errors'])) {
                $errors = $result['data']['errors'];
                $error_message .= ': ' . implode(', ', array_map(function($err) {
                    return $err['detail'] ?? 'Unknown error';
                }, $errors));
            } elseif (isset($result['data']['message'])) {
                $error_message .= ': ' . $result['data']['message'];
            }
            throw new Exception($error_message);
        }
        
        $link = $result['data']['data'];
        $link_id = $link['id'];
        $checkout_url = $link['attributes']['checkout_url'];
    }
    
    // Log payment link details
    error_log('Payment Link ID: ' . $link_id);
    error_log('Reservation ID: ' . $reservation_id);
    error_log('Payment Type: ' . $payment_type);
    error_log('Amount: ₱' . number_format($amount, 2) . ' (' . $amount_in_centavos . ' centavos)');
    error_log('Checkout URL: ' . $checkout_url);
    
    // Update reservation to track payment attempt
    if ($payment_type === 'full_payment') {
        // Store the payment link for full payment tracking
        $stmt = $pdo->prepare("
            UPDATE reservations 
            SET paymongo_full_payment_link_id = ?
            WHERE reservation_id = ?
        ");
        $stmt->execute([$link_id, $reservation_id]);
    } else {
        // Store for downpayment
        $stmt = $pdo->prepare("
            UPDATE reservations 
            SET paymongo_source_id = ?,
                paymongo_payment_type = 'gcash'
            WHERE reservation_id = ?
        ");
        $stmt->execute([$link_id, $reservation_id]);
    }
    
    // Return the dynamic checkout URL with correct amount
    echo json_encode([
        'success' => true,
        'message' => 'Payment link created successfully',
        'checkout_url' => $checkout_url,
        'link_id' => $link_id,
        'amount' => $amount,
        'reservation_id' => $reservation_id
    ]);
    
} catch (PDOException $e) {
    error_log('Payment Intent PDO Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log('Payment Intent Error: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

