<?php
/**
 * Staff: Mark No-Show
 * Mark guest as no-show, forfeit downpayment, unlock date
 */

session_start();
header('Content-Type: application/json');

require_once '../config/connection.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
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
    $db = new Database();
    $conn = $db->getConnection();
    
    $reservation_id = $_POST['reservation_id'] ?? null;
    $notes = $_POST['notes'] ?? '';
    
    if (!$reservation_id) {
        throw new Exception('Reservation ID is required');
    }
    
    $staff_id = $_SESSION['admin_id'] ?? 0;
    
    // Get reservation details
    $stmt = $conn->prepare("
        SELECT * FROM reservations 
        WHERE reservation_id = :id 
        AND status = 'confirmed'
        AND checked_in = 0
    ");
    $stmt->execute([':id' => $reservation_id]);
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reservation) {
        throw new Exception('Reservation not found, not confirmed, or already checked in');
    }
    
    // Check if check-in date has passed (guest should have checked in by now)
    $check_in_date = new DateTime($reservation['check_in_date'] . ' ' . $reservation['check_in_time']);
    $now = new DateTime();
    
    // Allow marking no-show only if check-in time has passed (e.g., 1 hour grace period)
    $grace_period = clone $check_in_date;
    $grace_period->modify('+1 hour');
    
    if ($now < $grace_period) {
        throw new Exception('Cannot mark as no-show yet. Guest still has time to check in.');
    }
    
    // Mark as no-show: forfeit downpayment, unlock date for other bookings
    $stmt = $conn->prepare("
        UPDATE reservations 
        SET status = 'no_show',
            no_show_marked_at = NOW(),
            no_show_marked_by = :staff_id,
            date_locked = 0,
            locked_until = NULL,
            admin_notes = CONCAT(COALESCE(admin_notes, ''), '\n[', NOW(), '] Marked as NO-SHOW. Downpayment forfeited. ', :notes),
            updated_at = NOW()
        WHERE reservation_id = :id
    ");
    
    $stmt->bindParam(':staff_id', $staff_id, PDO::PARAM_INT);
    $stmt->bindParam(':notes', $notes, PDO::PARAM_STR);
    $stmt->bindParam(':id', $reservation_id);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'Guest marked as no-show. Downpayment forfeited and date unlocked for other bookings.',
        'downpayment_amount' => $reservation['downpayment_amount'],
        'marked_at' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>

