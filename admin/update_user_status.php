<?php
/**
 * Update User Status API Endpoint
 * AR Homes Posadas Farm Resort Reservation System
 */

session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

// Include database connection
require_once '../config/connection.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['user_id']) || !isset($input['status'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields'
    ]);
    exit;
}

$userId = trim($input['user_id']);
$status = intval($input['status']);

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Update user status
    $query = "UPDATE users SET is_active = :status, updated_at = NOW() WHERE user_id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':status', $status, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $userId);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'User status updated successfully',
            'user_id' => $userId,
            'new_status' => $status
        ]);
    } else {
        throw new Exception('Failed to update user status');
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>

