<?php
/**
 * Staff: Check-In Guest
 * Mark guest as checked in and collect security bond
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
    $security_bond_collected = $_POST['security_bond_collected'] ?? 2000; // Default ₱2,000
    $notes = $_POST['notes'] ?? '';
    
    if (!$reservation_id) {
        throw new Exception('Reservation ID is required');
    }
    
    $staff_id = $_SESSION['admin_id'] ?? 0;
    
    // Check if reservation exists and is confirmed
    $stmt = $conn->prepare("
        SELECT * FROM reservations 
        WHERE reservation_id = :id 
        AND status = 'confirmed'
    ");
    $stmt->execute([':id' => $reservation_id]);
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reservation) {
        throw new Exception('Reservation not found or not confirmed');
    }
    
    // Check if already checked in
    if ($reservation['checked_in'] == 1) {
        throw new Exception('Guest is already checked in');
    }
    
    // Perform check-in
    $stmt = $conn->prepare("
        UPDATE reservations 
        SET checked_in = 1,
            checked_in_at = NOW(),
            checked_in_by = :staff_id,
            security_bond_collected = :bond_amount,
            status = 'checked_in',
            staff_notes = CONCAT(COALESCE(staff_notes, ''), '\n[', NOW(), '] Check-in completed. Security bond: ₱', :bond_amount, ' collected. ', :notes),
            updated_at = NOW()
        WHERE reservation_id = :id
    ");
    
    $stmt->bindParam(':staff_id', $staff_id, PDO::PARAM_INT);
    $stmt->bindParam(':bond_amount', $security_bond_collected, PDO::PARAM_STR);
    $stmt->bindParam(':notes', $notes, PDO::PARAM_STR);
    $stmt->bindParam(':id', $reservation_id);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'Guest checked in successfully!',
        'checked_in_at' => date('Y-m-d H:i:s'),
        'security_bond_collected' => $security_bond_collected
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

