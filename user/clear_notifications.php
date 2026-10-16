<?php
/**
 * Clear All User Notifications
 */

session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized. Please log in.'
    ]);
    exit;
}

require_once '../config/connection.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $user_id = $_SESSION['user_id'];
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Check if clearing all notifications
    if (isset($input['clear_all']) && $input['clear_all'] === true) {
        // Delete all notifications for this user
        $query = "DELETE FROM notifications WHERE user_id = :user_id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        
        if ($stmt->execute()) {
            $affected_rows = $stmt->rowCount();
            echo json_encode([
                'success' => true,
                'message' => 'All notifications cleared.',
                'deleted_count' => $affected_rows
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to clear notifications.'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid request. Specify clear_all parameter.'
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

