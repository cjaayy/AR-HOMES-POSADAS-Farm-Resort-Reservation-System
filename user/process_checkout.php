<?php
/**
 * Process Checkout Reservation
 * AR Homes Posadas Farm Resort Reservation System
 * 
 * Comprehensive checkout/booking processor with full validation,
 * security measures, and proper state management
 */

session_start();
header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../config/security.php';
require_once '../config/ReservationValidator.php';
require_once '../config/IDGenerator.php';

// Set security headers
Security::setSecurityHeaders();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed',
        'error_code' => 'METHOD_NOT_ALLOWED'
    ]);
    exit;
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Please login to make a reservation',
        'error_code' => 'UNAUTHORIZED',
        'redirect' => 'index.html'
    ]);
    exit;
}

try {
    // Initialize database connection
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
    
    // Parse request body
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON request body');
    }
    
    // === SECURITY CHECKS ===
    
    // 1. CSRF Token Validation
    $csrfToken = $data['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!Security::validateCSRFToken($csrfToken)) {
        Security::logSecurityEvent('CSRF_FAILURE', 'Invalid CSRF token on checkout', [
            'user_id' => $_SESSION['user_id']
        ]);
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Security validation failed. Please refresh the page and try again.',
            'error_code' => 'CSRF_INVALID',
            'new_token' => Security::generateCSRFToken()
        ]);
        exit;
    }
    
    // 2. Duplicate Submission Check
    $formToken = $data['form_token'] ?? null;
    if ($formToken && Security::isDuplicateSubmission($formToken)) {
        echo json_encode([
            'success' => false,
            'message' => 'This reservation request has already been processed.',
            'error_code' => 'DUPLICATE_SUBMISSION'
        ]);
        exit;
    }
    
    // 3. Rate Limiting
    if (Security::isRateLimited('checkout_reservation', 5, 60)) {
        Security::logSecurityEvent('RATE_LIMIT', 'Checkout rate limit exceeded', [
            'user_id' => $_SESSION['user_id']
        ]);
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'message' => 'Too many reservation attempts. Please wait a minute before trying again.',
            'error_code' => 'RATE_LIMITED'
        ]);
        exit;
    }
    
    // === INPUT VALIDATION & SANITIZATION ===
    
    $validator = new ReservationValidator($pdo);
    
    // Sanitize inputs
    $bookingType = Security::sanitizeString($data['booking_type'] ?? '');
    $packageType = Security::sanitizeString($data['package_type'] ?? $bookingType . '-day');
    $checkInDate = Security::sanitizeDate($data['check_in_date'] ?? '');
    $paymentMethod = Security::sanitizeString($data['payment_method'] ?? '');
    $specialRequests = Security::sanitizeString($data['special_requests'] ?? '');
    $groupType = Security::sanitizeString($data['group_type'] ?? '');
    
    // Parse number of guests/group size
    $groupSizeInput = $data['group_size'] ?? $data['number_of_guests'] ?? '1';
    if (is_numeric($groupSizeInput)) {
        $numberOfGuests = (int)$groupSizeInput;
    } else {
        // Handle ranges like "31-40"
        if (preg_match('/(\d+)-(\d+)/', $groupSizeInput, $matches)) {
            $numberOfGuests = (int)(((int)$matches[1] + (int)$matches[2]) / 2);
        } else {
            $numberOfGuests = 1;
        }
    }
    
    // Get booking configuration
    $config = $validator->getBookingConfig($bookingType);
    if (!$config) {
        throw new Exception('Invalid booking type');
    }
    
    // Determine duration
    if ($config['duration_type'] === 'days') {
        $duration = Security::sanitizeInt($data['number_of_days'] ?? 1, 1, 30);
    } else {
        $duration = Security::sanitizeInt($data['number_of_nights'] ?? 1, 1, 30);
    }
    
    if ($duration === false) {
        throw new Exception('Invalid duration value');
    }
    
    // Prepare validation data
    $validationData = [
        'booking_type' => $bookingType,
        'check_in_date' => $checkInDate,
        'payment_method' => $paymentMethod,
        'number_of_days' => $config['duration_type'] === 'days' ? $duration : null,
        'number_of_nights' => $config['duration_type'] === 'nights' ? $duration : null,
        'number_of_guests' => $numberOfGuests
    ];
    
    // Run full validation
    if (!$validator->validateNewReservation($validationData)) {
        $errors = $validator->getErrors();
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $validator->getFirstError(),
            'errors' => $errors,
            'error_code' => 'VALIDATION_FAILED'
        ]);
        exit;
    }
    
    // === AVAILABILITY CHECK (Double-check with lock) ===
    
    $pdo->beginTransaction();
    
    try {
        // Lock row to prevent race conditions
        $stmt = $pdo->prepare("
            SELECT reservation_id, status, downpayment_verified 
            FROM reservations 
            WHERE check_in_date = :date 
            AND booking_type = :type
            AND status IN ('confirmed', 'checked_in', 'pending_confirmation')
            AND (downpayment_verified = 1 OR date_locked = 1)
            FOR UPDATE
        ");
        $stmt->execute([
            ':date' => $checkInDate,
            ':type' => $bookingType
        ]);
        
        if ($stmt->fetch()) {
            $pdo->rollBack();
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'message' => 'Sorry, this date has just been booked by another guest. Please select a different date.',
                'error_code' => 'DATE_UNAVAILABLE'
            ]);
            exit;
        }
        
        // === CALCULATE PRICING ===
        
        $pricing = $validator->calculatePricing($bookingType, $duration, $packageType);
        $checkOutDate = $validator->calculateCheckOutDate($checkInDate, $bookingType, $duration);
        
        // === GET USER INFO ===
        
        $userId = $_SESSION['user_id'];
        $guestName = $_SESSION['user_full_name'] ?? 
                     (($_SESSION['user_given_name'] ?? '') . ' ' . ($_SESSION['user_last_name'] ?? ''));
        $guestName = trim($guestName) ?: 'Guest';
        $guestEmail = $_SESSION['user_email'] ?? null;
        $guestPhone = $_SESSION['user_phone'] ?? null;
        
        // === GENERATE RESERVATION ID ===
        
        $reservationId = IDGenerator::generateReservationId($pdo);
        
        // === INSERT RESERVATION ===
        
        $stmt = $pdo->prepare("
            INSERT INTO reservations (
                reservation_id,
                user_id,
                guest_name,
                guest_email,
                guest_phone,
                booking_type,
                package_type,
                check_in_date,
                check_out_date,
                check_in_time,
                check_out_time,
                number_of_days,
                number_of_nights,
                number_of_guests,
                group_type,
                special_requests,
                base_price,
                total_amount,
                downpayment_amount,
                remaining_balance,
                security_bond,
                payment_method,
                status,
                locked_until,
                created_at,
                updated_at
            ) VALUES (
                :reservation_id,
                :user_id,
                :guest_name,
                :guest_email,
                :guest_phone,
                :booking_type,
                :package_type,
                :check_in_date,
                :check_out_date,
                :check_in_time,
                :check_out_time,
                :number_of_days,
                :number_of_nights,
                :number_of_guests,
                :group_type,
                :special_requests,
                :base_price,
                :total_amount,
                :downpayment_amount,
                :remaining_balance,
                :security_bond,
                :payment_method,
                'pending_payment',
                DATE_ADD(NOW(), INTERVAL 24 HOUR),
                NOW(),
                NOW()
            )
        ");
        
        $numberOfDays = $config['duration_type'] === 'days' ? $duration : null;
        $numberOfNights = $config['duration_type'] === 'nights' ? $duration : null;
        
        $stmt->execute([
            ':reservation_id' => $reservationId,
            ':user_id' => $userId,
            ':guest_name' => $guestName,
            ':guest_email' => $guestEmail,
            ':guest_phone' => $guestPhone,
            ':booking_type' => $bookingType,
            ':package_type' => $packageType,
            ':check_in_date' => $checkInDate,
            ':check_out_date' => $checkOutDate,
            ':check_in_time' => $pricing['check_in_time'],
            ':check_out_time' => $pricing['check_out_time'],
            ':number_of_days' => $numberOfDays,
            ':number_of_nights' => $numberOfNights,
            ':number_of_guests' => $numberOfGuests,
            ':group_type' => $groupType ?: null,
            ':special_requests' => $specialRequests ?: null,
            ':base_price' => $pricing['base_price'],
            ':total_amount' => $pricing['total_amount'],
            ':downpayment_amount' => $pricing['downpayment_amount'],
            ':remaining_balance' => $pricing['remaining_balance'],
            ':security_bond' => $pricing['security_bond'],
            ':payment_method' => $paymentMethod
        ]);
        
        $pdo->commit();
        
        // === LOG SUCCESS ===
        
        Security::logSecurityEvent('RESERVATION_CREATED', 'New reservation created', [
            'reservation_id' => $reservationId,
            'user_id' => $userId,
            'check_in_date' => $checkInDate,
            'booking_type' => $bookingType,
            'total_amount' => $pricing['total_amount']
        ]);
        
        // === RETURN SUCCESS RESPONSE ===
        
        echo json_encode([
            'success' => true,
            'message' => 'Reservation created successfully! Please complete payment to confirm your booking.',
            'reservation_id' => $reservationId,
            'booking_details' => [
                'check_in_date' => $checkInDate,
                'check_out_date' => $checkOutDate,
                'check_in_time' => $pricing['check_in_time'],
                'check_out_time' => $pricing['check_out_time'],
                'booking_type' => $bookingType,
                'duration' => $duration,
                'duration_type' => $pricing['duration_type'],
                'number_of_guests' => $numberOfGuests
            ],
            'pricing' => [
                'base_price' => $pricing['base_price'],
                'total_amount' => $pricing['total_amount'],
                'downpayment_amount' => $pricing['downpayment_amount'],
                'remaining_balance' => $pricing['remaining_balance'],
                'security_bond' => $pricing['security_bond']
            ],
            'payment_method' => $paymentMethod,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+24 hours')),
            'policy' => [
                'downpayment_required' => '₱' . number_format($pricing['downpayment_amount'], 2),
                'balance_due' => 'Before check-in',
                'security_bond' => '₱' . number_format($pricing['security_bond'], 2),
                'rebooking_notice' => '7 days prior',
                'non_refundable' => true,
                'cancellation' => 'Downpayment is non-refundable/non-transferable'
            ],
            'next_step' => 'Complete downpayment within 24 hours to secure your reservation.',
            'new_csrf_token' => Security::generateCSRFToken()
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    error_log('Checkout Reservation PDO Error: ' . $e->getMessage());
    Security::logSecurityEvent('RESERVATION_DB_ERROR', $e->getMessage(), [
        'user_id' => $_SESSION['user_id'] ?? null
    ]);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'A database error occurred. Please try again.',
        'error_code' => 'DATABASE_ERROR'
    ]);
} catch (Exception $e) {
    error_log('Checkout Reservation Error: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => 'GENERAL_ERROR'
    ]);
}
?>

