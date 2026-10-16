<?php
/**
 * Get CSRF Token Endpoint
 * AR Homes Posadas Farm Resort Reservation System
 * 
 * Returns a new CSRF token for form protection
 */

session_start();
header('Content-Type: application/json');

require_once '../config/security.php';

// Generate and return new CSRF token
$token = Security::getCSRFToken();

echo json_encode([
    'success' => true,
    'csrf_token' => $token,
    'expires_in' => 3600 // 1 hour
]);
?>

