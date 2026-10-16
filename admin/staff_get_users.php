<?php
/**
 * Staff Get Users API - returns users for staff views (read-only)
 * Supports both admin session and staff-specific session
 */
session_start();
header('Content-Type: application/json');

// Check for either admin session or staff-specific session
$isAdminLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$isStaffLoggedIn = isset($_SESSION['staff_logged_in']) && $_SESSION['staff_logged_in'] === true;

if (!$isAdminLoggedIn && !$isStaffLoggedIn) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../config/connection.php';

try {
    $db = new Database(); $conn = $db->getConnection();
    
    // Check if cancellation_count column exists, if not add it
    try {
        $checkCol = $conn->query("SHOW COLUMNS FROM users LIKE 'cancellation_count'");
        if ($checkCol->rowCount() == 0) {
            $conn->exec("ALTER TABLE users ADD COLUMN cancellation_count INT DEFAULT 0");
        }
    } catch (Exception $e) {
        // Column check failed, continue anyway
    }
    
    $query = "SELECT 
                user_id,
                username,
                email,
                last_name,
                given_name,
                middle_name,
                full_name,
                phone_number,
                is_active,
                member_since,
                loyalty_level,
                last_login,
                created_at,
                updated_at,
                COALESCE(cancellation_count, 0) as cancellation_count
              FROM users
              ORDER BY created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($users as &$user) {
        $user['last_login_formatted'] = $user['last_login'] ? date('M d, Y', strtotime($user['last_login'])) . '<br><small style="color: #666; font-size: 0.85em;">' . date('h:i A', strtotime($user['last_login'])) . '</small>' : 'Never';
        $user['member_since'] = $user['member_since'] ? date('M d, Y', strtotime($user['member_since'])) : '';
        $user['created_at_formatted'] = $user['created_at'] ? date('M d, Y', strtotime($user['created_at'])) : '';
        $user['updated_at_formatted'] = $user['updated_at'] ? date('M d, Y h:i A', strtotime($user['updated_at'])) : '';
        $user['status'] = $user['is_active'] ? 'Active' : 'Inactive';
        $user['phone_formatted'] = $user['phone_number'];
    }

    echo json_encode(['success' => true, 'users' => $users, 'total_users' => count($users)]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

?>

