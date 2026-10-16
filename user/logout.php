<?php
/**
 * Guest User Logout Handler
 * AR Homes Posadas Farm Resort Reservation System
 * 
 * Destroys the guest user session and redirects to the home page.
 */

session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session cookie if it exists
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Clear any output buffers
if (ob_get_level()) {
    ob_end_clean();
}

// Redirect to home page
header('Location: ../index.html');
exit;

