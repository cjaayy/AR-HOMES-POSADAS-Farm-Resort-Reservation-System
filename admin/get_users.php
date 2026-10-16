<?php
/**
 * Get All Users API Endpoint
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

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Get all users from database (including pending email info)
    $query = "SELECT 
                user_id,
                username,
                email,
                pending_email,
                pending_email_expires,
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
                updated_at
              FROM users
              ORDER BY created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format the data
    foreach ($users as &$user) {
        // Format dates
        if ($user['last_login']) {
            $user['last_login_formatted'] = date('M d, Y', strtotime($user['last_login'])) . '<br><small style="color: #666; font-size: 0.85em;">' . date('h:i A', strtotime($user['last_login'])) . '</small>';
        } else {
            $user['last_login_formatted'] = 'Never';
        }
        
        // Check for pending email change
        $user['has_pending_email'] = !empty($user['pending_email']);
        if ($user['has_pending_email'] && $user['pending_email_expires']) {
            $expiresAt = strtotime($user['pending_email_expires']);
            $user['pending_email_expired'] = time() > $expiresAt;
            $user['pending_email_expires_formatted'] = date('M d, Y h:i A', $expiresAt);
        }
        
        // Format member_since
        if ($user['member_since']) {
            $user['member_since'] = date('M d, Y', strtotime($user['member_since']));
        }
        
        $user['created_at_formatted'] = date('M d, Y', strtotime($user['created_at']));
        $user['updated_at_formatted'] = date('M d, Y h:i A', strtotime($user['updated_at']));
        
        // Format status
        $user['status'] = $user['is_active'] ? 'Active' : 'Inactive';
        
        // Format phone number
        $user['phone_formatted'] = $user['phone_number'];
    }

    echo json_encode([
        'success' => true,
        'users' => $users,
        'total_users' => count($users)
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>

