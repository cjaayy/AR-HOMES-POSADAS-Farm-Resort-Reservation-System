<?php
/**
 * User: Cancel Reservation
 * User can cancel their booking (no refund for downpayment)
 */

session_start();
header('Content-Type: application/json');

require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $data = json_decode(file_get_contents('php://input'), true);
    $reservation_id = $data['reservation_id'] ?? null;
    $reason = $data['reason'] ?? '';
    
    if (!$reservation_id) {
        throw new Exception('Reservation ID is required');
    }
    
    $user_id = $_SESSION['user_id'];
    
    // Get reservation details
    $stmt = $conn->prepare("
        SELECT * FROM reservations 
        WHERE reservation_id = :id 
        AND user_id = :user_id
    ");
    $stmt->execute([
        ':id' => $reservation_id,
        ':user_id' => $user_id
    ]);
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reservation) {
        throw new Exception('Reservation not found');
    }
    
    // Cancellation is NOT allowed - downpayment is non-refundable
    // Users must request rebooking instead (within 3 months)
    if ($reservation['downpayment_verified'] == 1 || $reservation['full_payment_verified'] == 1 || $reservation['status'] === 'confirmed') {
        throw new Exception('Cancellation is not allowed. Downpayment is non-refundable/non-transferable. Please request REBOOKING (within 3 months) instead.');
    }
    
    // Only allow cancellation for unpaid/unconfirmed reservations
    if (!in_array($reservation['status'], ['pending_payment']) || $reservation['downpayment_verified'] == 1) {
        throw new Exception('Cannot cancel this reservation. Please contact us for rebooking options.');
    }
    
    if ($reservation['checked_in'] == 1) {
        throw new Exception('Cannot cancel a reservation after check-in');
    }
    
    // Cancel reservation: downpayment non-refundable
    $cancellation_notes = "User cancelled reservation. Reason: " . $reason;
    
    if ($reservation['downpayment_paid'] == 1) {
        $cancellation_notes .= " | Downpayment non-refundable: ₱" . number_format($reservation['downpayment_amount'], 2);
    }
    
    $stmt = $conn->prepare("
        UPDATE reservations 
        SET status = 'cancelled',
            date_locked = 0,
            locked_until = NULL,
            cancelled_at = NOW(),
            cancelled_by = :user_id,
            cancellation_reason = :reason,
            admin_notes = CONCAT(COALESCE(admin_notes, ''), '\n[', NOW(), '] ', :notes),
            updated_at = NOW()
        WHERE reservation_id = :id
    ");
    
    $stmt->execute([
        ':user_id' => $user_id,
        ':reason' => $reason,
        ':notes' => $cancellation_notes,
        ':id' => $reservation_id
    ]);
    
    // Build response message
    $message = 'Reservation cancelled successfully.';
    
    if ($reservation['downpayment_paid'] == 1) {
        $message .= ' Downpayment (₱' . number_format($reservation['downpayment_amount'], 2) . ') is non-refundable as per booking policy.';
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'downpayment_forfeited' => $reservation['downpayment_paid'] == 1,
        'downpayment_amount' => $reservation['downpayment_amount']
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

