<?php
/**
 * Get System Settings
 * Retrieves system configuration and settings data
 */

header('Content-Type: application/json');
header("Cache-Control: no-cache, no-store, must-revalidate");

session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

require_once '../config/connection.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Get admin info
    $adminId = $_SESSION['admin_id'];
    $adminQuery = "SELECT admin_id, full_name, email, username, role, created_at, last_login FROM admin_users WHERE admin_id = :admin_id";
    $adminStmt = $conn->prepare($adminQuery);
    $adminStmt->bindParam(':admin_id', $adminId, PDO::PARAM_INT);
    $adminStmt->execute();
    $adminData = $adminStmt->fetch(PDO::FETCH_ASSOC);
    
    // Get total admin/staff count
    $staffCountQuery = "SELECT COUNT(*) as total FROM admin_users";
    $staffCountStmt = $conn->prepare($staffCountQuery);
    $staffCountStmt->execute();
    $staffCount = $staffCountStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get active sessions (admins logged in within last 24 hours)
    $activeQuery = "SELECT COUNT(*) as active FROM admin_users WHERE last_login >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    $activeStmt = $conn->prepare($activeQuery);
    $activeStmt->execute();
    $activeSessions = $activeStmt->fetch(PDO::FETCH_ASSOC)['active'];
    
    // Get last system activity
    $lastActivityQuery = "SELECT MAX(last_login) as last_activity FROM admin_users";
    $lastActivityStmt = $conn->prepare($lastActivityQuery);
    $lastActivityStmt->execute();
    $lastActivity = $lastActivityStmt->fetch(PDO::FETCH_ASSOC)['last_activity'];
    
    // Get database stats
    $dbStatsQuery = "SELECT 
        (SELECT COUNT(*) FROM users) as total_users,
        (SELECT COUNT(*) FROM reservations) as total_reservations,
        (SELECT COUNT(*) FROM admin_users) as total_staff
    ";
    $dbStatsStmt = $conn->prepare($dbStatsQuery);
    $dbStatsStmt->execute();
    $dbStats = $dbStatsStmt->fetch(PDO::FETCH_ASSOC);
    
    // System settings (you can add a settings table later)
    $systemSettings = [
        'resort_name' => 'AR Homes Posadas Farm Resort',
        'contact_email' => 'info@arhomesposadas.com',
        'contact_phone' => '+63 912 345 6789',
        'language' => 'English (US)',
        'timezone' => 'Asia/Manila (UTC+8)',
        'currency' => 'PHP (₱)',
        'date_format' => 'M d, Y',
        'time_format' => '12-hour',
        'session_timeout' => '30 minutes'
    ];
    
    echo json_encode([
        'success' => true,
        'admin' => $adminData,
        'stats' => [
            'total_staff' => $staffCount,
            'active_sessions' => $activeSessions,
            'last_activity' => $lastActivity,
            'total_users' => $dbStats['total_users'] ?? 0,
            'total_reservations' => $dbStats['total_reservations'] ?? 0
        ],
        'settings' => $systemSettings
    ]);
    
} catch (PDOException $e) {
    error_log("Get settings error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("Get settings error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}

