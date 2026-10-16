<?php
/**
 * User: Accept Terms & Conditions
 * User must accept terms before making reservation
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
    $terms_accepted = $data['terms_accepted'] ?? false;
    
    if (!$reservation_id || !$terms_accepted) {
        throw new Exception('You must accept the terms and conditions');
    }
    
    $user_id = $_SESSION['user_id'];
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Update reservation with terms acceptance
    $stmt = $conn->prepare("
        UPDATE reservations 
        SET terms_accepted = 1,
            terms_accepted_at = NOW(),
            terms_ip_address = :ip,
            updated_at = NOW()
        WHERE reservation_id = :id 
        AND user_id = :user_id
    ");
    
    $stmt->bindParam(':ip', $ip_address, PDO::PARAM_STR);
    $stmt->bindParam(':id', $reservation_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Reservation not found or already accepted terms');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Terms and conditions accepted',
        'accepted_at' => date('Y-m-d H:i:s'),
        'ip_address' => $ip_address
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

