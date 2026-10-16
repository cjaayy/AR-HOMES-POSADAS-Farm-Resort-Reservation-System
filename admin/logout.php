<?php
/**
 * Admin Logout Handler
 * AR Homes Posadas Farm Resort Reservation System
 */

session_start();

// Log logout attempt for debugging
error_log("Admin logout initiated - Session ID: " . session_id());

// Unset all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Clear any session regeneration
session_write_close();

// Return JSON response
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
echo json_encode([
    'success' => true,
    'message' => 'Logged out successfully'
]);
exit;
?>

