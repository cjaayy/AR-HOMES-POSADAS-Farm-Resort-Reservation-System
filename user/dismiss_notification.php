<?php
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
    
    if (!isset($input['notification_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Notification ID is required.'
        ]);
        exit;
    }
    
    $notification_id = $input['notification_id'];
    
    // Delete the notification (only if it belongs to this user)
    $query = "DELETE FROM notifications 
              WHERE notification_id = :notification_id 
              AND user_id = :user_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':notification_id', $notification_id);
    $stmt->bindParam(':user_id', $user_id);
    
    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Notification dismissed successfully.'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Notification not found or already dismissed.'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to dismiss notification.'
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

