<?php
/**
 * PayMongo Webhook Handler
 * Receives webhook events from PayMongo for payment status updates
 */

require_once '../config/database.php';
require_once '../config/paymongo.php';

// Get raw POST data
$raw_input = file_get_contents('php://input');
$webhook_data = json_decode($raw_input, true);

// Log webhook for debugging
error_log('PayMongo Webhook Received: ' . $raw_input);

// Verify webhook signature (optional but recommended)
// SIGNATURE VERIFICATION DISABLED FOR TESTING
error_log('Webhook signature check: DISABLED (bypassed for testing)');

// Signature verification is completely disabled - uncomment to enable
/*
$signature = $_SERVER['HTTP_PAYMONGO_SIGNATURE'] ?? '';
if (PAYMONGO_WEBHOOK_SECRET && $signature) {
    $computed_signature = hash_hmac('sha256', $raw_input, PAYMONGO_WEBHOOK_SECRET);
    if (!hash_equals($computed_signature, $signature)) {
        http_response_code(401);
        error_log('Invalid webhook signature');
        exit;
    }
}
*/

try {
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
    
    if (!$webhook_data || !isset($webhook_data['data'])) {
        throw new Exception('Invalid webhook data');
    }
    
    $event = $webhook_data['data'];
    $event_type = $event['attributes']['type'] ?? '';
    
    // Handle different event types
    switch ($event_type) {
        case 'source.chargeable':
            handleSourceChargeable($pdo, $event);
            break;
            
        case 'payment.paid':
            handlePaymentPaid($pdo, $event);
            break;
            
        case 'link.payment.paid':
        case 'checkout_session.payment.paid':
            handleLinkPaymentPaid($pdo, $event);
            break;
            
        case 'payment.failed':
            handlePaymentFailed($pdo, $event);
            break;
            
        default:
            error_log('Unhandled event type: ' . $event_type);
    }
    
    http_response_code(200);
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    error_log('Webhook Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

/**
 * Handle source.chargeable event
 * This means the GCash payment was authorized and ready to be charged
 */
function handleSourceChargeable($pdo, $event) {
    $source_id = $event['attributes']['data']['id'] ?? null;
    
    if (!$source_id) {
        throw new Exception('Source ID not found in event');
    }
    
    // Find reservation with this source ID
    $stmt = $pdo->prepare("
        SELECT * FROM reservations 
        WHERE paymongo_source_id = ?
    ");
    $stmt->execute([$source_id]);
    $reservation = $stmt->fetch();
    
    if (!$reservation) {
        error_log('No reservation found for source: ' . $source_id);
        return;
    }
    
    // Create payment from the source
    $amount_in_centavos = (int)($reservation['downpayment_amount'] * 100);
    
    $payment_data = [
        'data' => [
            'attributes' => [
                'amount' => $amount_in_centavos,
                'currency' => 'PHP',
                'source' => [
                    'id' => $source_id,
                    'type' => 'source'
                ],
                'description' => 'AR Homes Resort - Reservation #' . $reservation['reservation_id']
            ]
        ]
    ];
    
    $result = makePaymongoRequest('/payments', 'POST', $payment_data);
    
    if ($result['success']) {
        $payment = $result['data']['data'];
        $payment_id = $payment['id'];
        
        // Extract payment method from source type
        $payment_method = 'gcash'; // default
        $source_type = $event['attributes']['data']['attributes']['type'] ?? null;
        if ($source_type) {
            $source_type = strtolower($source_type);
            
            $type_mapping = [
                'gcash' => 'gcash',
                'paymaya' => 'paymaya',
                'grab_pay' => 'grab_pay',
                'card' => 'card',
                'atome' => 'atome'
            ];
            
            $payment_method = $type_mapping[$source_type] ?? $source_type;
        }
        
        // Update reservation with downpayment payment details - pending admin confirmation
        $stmt = $pdo->prepare("
            UPDATE reservations 
            SET paymongo_payment_id = ?,
                status = 'pending_confirmation',
                payment_method = ?,
                downpayment_paid = 1,
                downpayment_reference = ?,
                downpayment_paid_at = NOW(),
                downpayment_verified = 0,
                updated_at = NOW()
            WHERE reservation_id = ?
        ");
        $stmt->execute([
            $payment_id,
            $payment_method,
            $payment_id,
            $reservation['reservation_id']
        ]);
        
        error_log('Payment created for reservation: ' . $reservation['reservation_id'] . ' using ' . $payment_method);
    }
}

/**
 * Handle payment.paid event
 * Confirmation that payment was successfully processed
 */
function handlePaymentPaid($pdo, $event) {
    $payment_id = $event['attributes']['data']['id'] ?? null;
    
    if (!$payment_id) {
        return;
    }
    
    // Extract payment method from payment data
    $payment_method = 'gcash'; // default
    $source = $event['attributes']['data']['attributes']['source'] ?? null;
    if ($source && isset($source['type'])) {
        $source_type = strtolower($source['type']);
        
        $type_mapping = [
            'gcash' => 'gcash',
            'paymaya' => 'paymaya',
            'grab_pay' => 'grab_pay',
            'card' => 'card',
            'dob' => 'dob_ubp',
            'atome' => 'atome'
        ];
        
        // Check DOB provider for specific bank
        if ($source_type === 'dob' && isset($source['provider'])) {
            $provider = strtolower($source['provider']);
            if (strpos($provider, 'bpi') !== false || $provider === 'test_bank_one') {
                $payment_method = 'dob_bpi';
            } else if (strpos($provider, 'ubp') !== false || strpos($provider, 'union') !== false || $provider === 'test_bank_two') {
                $payment_method = 'dob_ubp';
            } else {
                $payment_method = 'dob_ubp';
            }
        } else {
            $payment_method = $type_mapping[$source_type] ?? $source_type;
        }
    }
    
    // Update reservation status - pending admin confirmation
    $stmt = $pdo->prepare("
        UPDATE reservations 
        SET status = 'pending_confirmation',
            payment_method = ?,
            downpayment_paid = 1,
            downpayment_paid_at = NOW(),
            downpayment_verified = 0,
            updated_at = NOW()
        WHERE paymongo_payment_id = ?
    ");
    $stmt->execute([$payment_method, $payment_id]);
    
    error_log('Payment confirmed: ' . $payment_id . ' using ' . $payment_method);
}

/**
 * Handle link.payment.paid event
 * This is triggered when a payment link is successfully paid
 */
function handleLinkPaymentPaid($pdo, $event) {
    $link_data = $event['attributes']['data'] ?? null;
    
    if (!$link_data) {
        error_log('No link data in link.payment.paid event');
        return;
    }
    
    $link_id = $link_data['id'] ?? null;
    
    if (!$link_id) {
        error_log('No link ID found in payment data');
        return;
    }
    
    // Extract payment method from nested payments array
    $payment_method = 'gcash'; // default
    $payment_id = null;
    
    $payments = $link_data['attributes']['payments'] ?? [];
    if (!empty($payments)) {
        $first_payment = $payments[0]['data'] ?? null;
        if ($first_payment) {
            $payment_id = $first_payment['id'] ?? null;
            $source = $first_payment['attributes']['source'] ?? null;
            
            if ($source && isset($source['type'])) {
                $source_type = strtolower($source['type']);
                
                // Map PayMongo source types to database payment methods
                $type_mapping = [
                    'gcash' => 'gcash',
                    'paymaya' => 'paymaya',
                    'grab_pay' => 'grab_pay',
                    'card' => 'card',
                    'dob' => 'dob_ubp', // Default DOB to UnionBank, check provider for specifics
                    'atome' => 'atome'
                ];
                
                // Check DOB provider to determine specific bank
                if ($source_type === 'dob' && isset($source['provider'])) {
                    $provider = strtolower($source['provider']);
                    if (strpos($provider, 'bpi') !== false || $provider === 'test_bank_one') {
                        $payment_method = 'dob_bpi';
                    } else if (strpos($provider, 'ubp') !== false || strpos($provider, 'union') !== false || $provider === 'test_bank_two') {
                        $payment_method = 'dob_ubp';
                    } else {
                        $payment_method = 'dob_ubp'; // default
                    }
                } else {
                    $payment_method = $type_mapping[$source_type] ?? $source_type;
                }
            }
        }
    }
    
    error_log('Payment method detected: ' . $payment_method . ' for link: ' . $link_id);
    
    $ref_num = $link_data['attributes']['reference_number'] ?? null;
    
    // Find reservation with this link ID or reservation reference number
    $stmt = $pdo->prepare("
        SELECT * FROM reservations 
        WHERE paymongo_source_id = ? OR paymongo_full_payment_link_id = ? OR reservation_id = ?
    ");
    $stmt->execute([$link_id, $link_id, $ref_num]);
    $reservation = $stmt->fetch();
    
    if (!$reservation) {
        error_log('No reservation found for link ID: ' . $link_id);
        return;
    }
    
    // Determine if this is a downpayment or full payment
    $is_full_payment = ($reservation['paymongo_full_payment_link_id'] === $link_id);
    
    if ($is_full_payment) {
        // Update reservation with full payment success details - auto-verify since it's from PayMongo
        // If payment_method is not set yet (direct full payment), set it now
        if (empty($reservation['payment_method'])) {
            $stmt = $pdo->prepare("
                UPDATE reservations 
                SET full_payment_paid = 1,
                    full_payment_reference = ?,
                    full_payment_paid_at = NOW(),
                    full_payment_verified = 1,
                    full_payment_verified_at = NOW(),
                    payment_method = ?,
                    updated_at = NOW()
                WHERE reservation_id = ?
            ");
            $stmt->execute([
                $payment_id,
                $payment_method,
                $reservation['reservation_id']
            ]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE reservations 
                SET full_payment_paid = 1,
                    full_payment_reference = ?,
                    full_payment_paid_at = NOW(),
                    full_payment_verified = 1,
                    full_payment_verified_at = NOW(),
                    updated_at = NOW()
                WHERE reservation_id = ?
            ");
            $stmt->execute([
                $payment_id,
                $reservation['reservation_id']
            ]);
        }
        
        error_log('Full payment link paid - Reservation #' . $reservation['reservation_id'] . ' full payment marked as paid and verified (PayMongo) with method: ' . $payment_method);
    } else {
        // Update reservation with downpayment success details - pending admin confirmation
        $stmt = $pdo->prepare("
            UPDATE reservations 
            SET paymongo_payment_id = ?,
                status = 'pending_confirmation',
                payment_method = ?,
                downpayment_paid = 1,
                downpayment_reference = ?,
                downpayment_paid_at = NOW(),
                downpayment_verified = 0,
                updated_at = NOW()
            WHERE reservation_id = ?
        ");
        $stmt->execute([
            $payment_id,
            $payment_method,
            $payment_id,
            $reservation['reservation_id']
        ]);
        
        error_log('Downpayment link paid - Reservation #' . $reservation['reservation_id'] . ' updated to pending_confirmation with method: ' . $payment_method);
    }
}

/**
 * Handle payment.failed event
 */
function handlePaymentFailed($pdo, $event) {
    $payment_id = $event['attributes']['data']['id'] ?? null;
    
    if (!$payment_id) {
        return;
    }
    
    error_log('Payment failed: ' . $payment_id);
    // You can add logic here to notify the user or mark the reservation
}

