<?php
/**
 * Staff Logout Handler
 * Only clears staff session variables, doesn't affect admin session
 */

session_start();

// Only clear staff-specific session variables
unset($_SESSION['staff_logged_in']);
unset($_SESSION['staff_id']);
unset($_SESSION['staff_username']);
unset($_SESSION['staff_full_name']);
unset($_SESSION['staff_email']);
unset($_SESSION['staff_role']);
unset($_SESSION['staff_login_time']);
unset($_SESSION['staff_last_activity']);

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Logged out successfully'
]);
?>

