<?php
/**
 * Staff Activity Log API - Get recent activity logs
 */
session_start();
header('Content-Type: application/json');

// Allow admin, super_admin, and staff roles (both admin session and staff-specific session)
$allowedRoles = ['admin', 'super_admin', 'staff'];
$userRole = $_SESSION['admin_role'] ?? '';

$isAdminSession = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true && in_array($userRole, $allowedRoles);
$isStaffSession = isset($_SESSION['staff_logged_in']) && $_SESSION['staff_logged_in'] === true;

if (!$isAdminSession && !$isStaffSession) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../config/connection.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    
    // Check if table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'activity_log'");
    if ($tableCheck->rowCount() === 0) {
        echo json_encode(['success' => true, 'activities' => []]);
        exit;
    }
    
    $sql = "SELECT 
                action_type,
                action_description,
                user_name,
                created_at
            FROM activity_log
            WHERE user_role = 'staff'
            ORDER BY created_at DESC
            LIMIT :limit";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'activities' => $activities
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>

