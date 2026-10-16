<?php
/**
 * Check Admin Session
 * AR Homes Posadas Farm Resort Reservation System
 */

session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Initialize response
$response = [
    'success' => false,
    'logged_in' => false,
    'message' => '',
    'data' => null
];

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    $response['message'] = 'Not logged in';
    echo json_encode($response);
    exit;
}

// Check session timeout
$timeout = SESSION_TIMEOUT;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
    // Session has expired
    session_unset();
    session_destroy();
    $response['message'] = 'Session expired';
    echo json_encode($response);
    exit;
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Return session data
$response['success'] = true;
$response['logged_in'] = true;
$response['message'] = 'Session active';
$response['data'] = [
    'admin_id' => $_SESSION['admin_id'] ?? null,
    'username' => $_SESSION['admin_username'] ?? null,
    'full_name' => $_SESSION['admin_full_name'] ?? null,
    'email' => $_SESSION['admin_email'] ?? null,
    'role' => $_SESSION['admin_role'] ?? null,
    'login_time' => $_SESSION['login_time'] ?? null,
    'last_activity' => $_SESSION['last_activity'] ?? null
];

echo json_encode($response);
?>

