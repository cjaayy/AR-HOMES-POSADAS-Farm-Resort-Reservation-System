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
    
    // Check if marking all as read or specific notification
    if (isset($input['mark_all']) && $input['mark_all'] === true) {
        // Mark all notifications as read for this user
        $query = "UPDATE notifications 
                  SET is_read = 1, read_at = NOW() 
                  WHERE user_id = :user_id AND is_read = 0";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        
        if ($stmt->execute()) {
            $affected_rows = $stmt->rowCount();
            echo json_encode([
                'success' => true,
                'message' => 'All notifications marked as read.',
                'affected_rows' => $affected_rows
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to mark notifications as read.'
            ]);
        }
    } elseif (isset($input['notification_id'])) {
        // Mark specific notification as read
        $notification_id = $input['notification_id'];
        
        $query = "UPDATE notifications 
                  SET is_read = 1, read_at = NOW() 
                  WHERE notification_id = :notification_id 
                  AND user_id = :user_id 
                  AND is_read = 0";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':notification_id', $notification_id);
        $stmt->bindParam(':user_id', $user_id);
        
        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Notification marked as read.'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Notification not found or already read.'
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to mark notification as read.'
            ]);
        }
    } elseif (isset($input['mark_all_unread']) && $input['mark_all_unread'] === true) {
        // Mark all notifications as unread for this user
        $query = "UPDATE notifications 
                  SET is_read = 0, read_at = NULL 
                  WHERE user_id = :user_id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        
        if ($stmt->execute()) {
            $affected_rows = $stmt->rowCount();
            echo json_encode([
                'success' => true,
                'message' => 'All notifications marked as unread.',
                'affected_rows' => $affected_rows
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to mark notifications as unread.'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid request. Specify mark_all, mark_all_unread, or notification_id.'
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

