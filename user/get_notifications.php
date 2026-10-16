<?php
/**
 * Get User Notifications
 */

session_start();
header('Content-Type: application/json');

require_once '../config/connection.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Could not connect to database');
    }
    
    $user_id = $_SESSION['user_id'];
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
    $unread_only = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';
    
    // Check if notifications table exists
    $check_table = $db->query("SHOW TABLES LIKE 'notifications'");
    if ($check_table->rowCount() == 0) {
        echo json_encode([
            'success' => true,
            'notifications' => [],
            'unread_count' => 0,
            'total_count' => 0,
            'message' => 'Notifications table does not exist. Please run init_notifications.php'
        ]);
        exit;
    }
    
    $sql = "SELECT * FROM notifications WHERE user_id = :user_id";
    
    if ($unread_only) {
        $sql .= " AND is_read = 0";
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT :limit";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    $notifications = $stmt->fetchAll();
    
    // Get unread count
    $count_stmt = $db->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = :user_id AND is_read = 0");
    $count_stmt->bindParam(':user_id', $user_id);
    $count_stmt->execute();
    $unread_count = $count_stmt->fetch()['count'];
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => intval($unread_count),
        'total_count' => count($notifications)
    ]);
    
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
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

