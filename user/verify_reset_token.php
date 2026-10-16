<?php
/**
 * Verify Reset Token
 * AR Homes Posadas Farm Resort Reservation System
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();

require_once '../config/connection.php';

header('Content-Type: application/json');

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit;
}

// Get JSON input
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    $response['message'] = 'Invalid JSON: ' . json_last_error_msg();
    echo json_encode($response);
    exit;
}

// Validate input
if (!isset($input['token']) || empty(trim($input['token']))) {
    $response['message'] = 'Token is required';
    echo json_encode($response);
    exit;
}

$token = trim($input['token']);

try {
    // Create database connection
    $database = new Database();
    $conn = $database->getConnection();

    // Hash the token to match database
    $tokenHash = hash('sha256', $token);

    // Find user with valid reset token
    $sql = "SELECT user_id, username, email, full_name, reset_token_expires 
            FROM users 
            WHERE reset_token = :token 
            AND is_active = 1 
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':token', $tokenHash, PDO::PARAM_STR);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        $response['message'] = 'Invalid reset token';
        echo json_encode($response);
        exit;
    }

    $user = $stmt->fetch();

    // Check if token has expired
    $expiresAt = strtotime($user['reset_token_expires']);
    $currentTime = time();

    if ($currentTime > $expiresAt) {
        $response['message'] = 'Reset token has expired. Please request a new password reset.';
        echo json_encode($response);
        exit;
    }

    // Calculate remaining time
    $remainingMinutes = round(($expiresAt - $currentTime) / 60);

    $response['success'] = true;
    $response['message'] = 'Token is valid';
    $response['data'] = [
        'username' => $user['username'],
        'email' => $user['email'],
        'expires_in_minutes' => $remainingMinutes
    ];

    echo json_encode($response);

} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
    echo json_encode($response);
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
    echo json_encode($response);
}

