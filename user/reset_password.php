<?php
/**
 * Reset Password Handler
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
if (!isset($input['token']) || !isset($input['password'])) {
    $response['message'] = 'Token and password are required';
    echo json_encode($response);
    exit;
}

$token = trim($input['token']);
$newPassword = trim($input['password']);
$confirmPassword = isset($input['confirm_password']) ? trim($input['confirm_password']) : '';

// Validate password
if (empty($newPassword)) {
    $response['message'] = 'Password cannot be empty';
    echo json_encode($response);
    exit;
}

if (strlen($newPassword) < 6) {
    $response['message'] = 'Password must be at least 6 characters long';
    echo json_encode($response);
    exit;
}

if ($confirmPassword !== '' && $newPassword !== $confirmPassword) {
    $response['message'] = 'Passwords do not match';
    echo json_encode($response);
    exit;
}

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
        $response['message'] = 'Invalid or expired reset token';
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

    // Hash the new password
    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

    // Update password and clear reset token
    $updateSql = "UPDATE users SET 
                  password_hash = :password_hash,
                  reset_token = NULL,
                  reset_token_expires = NULL
                  WHERE user_id = :user_id";
    
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bindParam(':password_hash', $passwordHash, PDO::PARAM_STR);
    $updateStmt->bindParam(':user_id', $user['user_id']);
    $updateStmt->execute();

    // Log the password reset
    $logDir = __DIR__ . '/password_resets';
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/reset_log_' . date('Y-m-d') . '.txt';
    $logMessage = "\n" . str_repeat('=', 80) . "\n";
    $logMessage .= "Password Reset Completed\n";
    $logMessage .= "Time: " . date('Y-m-d H:i:s') . "\n";
    $logMessage .= "User: {$user['full_name']} ({$user['username']})\n";
    $logMessage .= "Email: {$user['email']}\n";
    $logMessage .= str_repeat('=', 80) . "\n";
    
    file_put_contents($logFile, $logMessage, FILE_APPEND);

    $response['success'] = true;
    $response['message'] = 'Password has been reset successfully. You can now login with your new password.';
    $response['data'] = [
        'username' => $user['username'],
        'email' => $user['email']
    ];

    echo json_encode($response);

} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
    echo json_encode($response);
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
    echo json_encode($response);
}
?>

