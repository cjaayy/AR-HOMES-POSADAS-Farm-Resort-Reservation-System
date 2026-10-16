<?php
/**
 * Check Date Availability
 * Returns whether a date is available for booking
 * Rule: "First to pay, first to reserve"
 * - If date has confirmed payment → LOCKED
 * - If date has only pending → AVAILABLE
 */

session_start();
header('Content-Type: application/json');

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

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
    
    $data = json_decode(file_get_contents('php://input'), true);
    $check_in_date = $data['check_in_date'] ?? null;
    $booking_type = $data['booking_type'] ?? null;
    
    if (!$check_in_date || !$booking_type) {
        throw new Exception('Check-in date and booking type are required');
    }
    
    // Check if date is already locked by a confirmed/paid reservation
    $stmt = $pdo->prepare("
        SELECT 
            reservation_id,
            status,
            downpayment_paid,
            downpayment_verified,
            date_locked,
            guest_name
        FROM reservations 
        WHERE check_in_date = :date 
        AND booking_type = :type
        AND status IN ('confirmed', 'checked_in', 'checked_out', 'pending_confirmation')
        AND (downpayment_verified = 1 OR date_locked = 1)
        LIMIT 1
    ");
    
    $stmt->execute([
        ':date' => $check_in_date,
        ':type' => $booking_type
    ]);
    
    $locked_booking = $stmt->fetch();
    
    if ($locked_booking) {
        echo json_encode([
            'success' => true,
            'available' => false,
            'locked' => true,
            'message' => 'This date is already booked and confirmed.',
            'locked_by' => $locked_booking['guest_name'],
            'reservation_id' => $locked_booking['reservation_id']
        ]);
        exit;
    }
    
    // Check for pending reservations (not yet paid/verified)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as pending_count
        FROM reservations 
        WHERE check_in_date = :date 
        AND booking_type = :type
        AND status = 'pending_payment'
        AND downpayment_verified = 0
    ");
    
    $stmt->execute([
        ':date' => $check_in_date,
        ':type' => $booking_type
    ]);
    
    $result = $stmt->fetch();
    $pending_count = $result['pending_count'];
    
    echo json_encode([
        'success' => true,
        'available' => true,
        'locked' => false,
        'message' => 'Date is available! First to pay, first to reserve.',
        'pending_reservations' => $pending_count,
        'warning' => $pending_count > 0 ? "Note: $pending_count pending reservation(s) for this date. Complete payment quickly to secure your booking!" : null
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

