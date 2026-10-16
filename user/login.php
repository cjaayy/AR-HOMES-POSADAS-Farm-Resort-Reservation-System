<?php
/**
 * User Login Handler
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

// Validate input - accept either 'username' or 'usernameOrEmail'
$usernameField = isset($input['usernameOrEmail']) ? 'usernameOrEmail' : 'username';
if (!isset($input[$usernameField]) || !isset($input['password'])) {
    $response['message'] = 'Username and password are required';
    echo json_encode($response);
    exit;
}

$username = trim($input[$usernameField]);
$password = trim($input['password']);

// Validate not empty
if (empty($username) || empty($password)) {
    $response['message'] = 'Username and password cannot be empty';
    echo json_encode($response);
    exit;
}

try {
    // Create database connection
    $database = new Database();
    $conn = $database->getConnection();

    // Prepare SQL statement to prevent SQL injection
    $sql = "SELECT user_id, username, email, password_hash, full_name, last_name, given_name, middle_name, 
                   phone_number, is_active, email_verified, last_login, created_at 
            FROM users 
            WHERE username = :username OR email = :email 
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->bindParam(':email', $username, PDO::PARAM_STR);
    $stmt->execute();

    // Check if user exists
    if ($stmt->rowCount() === 0) {
        $response['message'] = 'Invalid username or password';
        echo json_encode($response);
        exit;
    }

    // Fetch user data
    $user = $stmt->fetch();

    // Check if account is active
    if ($user['is_active'] != 1) {
        $response['message'] = 'Account is inactive. Please contact support.';
        echo json_encode($response);
        exit;
    }

    // Check if email is verified (only if email verification is required)
    $requireEmailVerification = defined('REQUIRE_EMAIL_VERIFICATION') ? REQUIRE_EMAIL_VERIFICATION : false;
    if ($requireEmailVerification && (!isset($user['email_verified']) || $user['email_verified'] != 1)) {
        $response['message'] = 'Please verify your email address before logging in. Check your inbox for the verification link.';
        $response['data'] = [
            'email_verified' => false,
            'email' => $user['email']
        ];
        echo json_encode($response);
        exit;
    }

    // Verify password
    if (!password_verify($password, $user['password_hash'])) {
        $response['message'] = 'Invalid username or password';
        echo json_encode($response);
        exit;
    }

    // Update last login time
    $updateSql = "UPDATE users SET last_login = NOW() WHERE user_id = :user_id";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bindParam(':user_id', $user['user_id']);
    $updateStmt->execute();

    // Set session variables
    $_SESSION['user_logged_in'] = true;
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['user_username'] = $user['username'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_full_name'] = $user['full_name'];
    $_SESSION['user_given_name'] = $user['given_name'];
    $_SESSION['user_last_name'] = $user['last_name'];
    $_SESSION['user_middle_name'] = $user['middle_name'];
    $_SESSION['user_phone'] = $user['phone_number'];
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();

    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);

    // Prepare success response
    $response['success'] = true;
    $response['message'] = 'Login successful';
    $response['data'] = [
        'user_id' => $user['user_id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'full_name' => $user['full_name'],
        'given_name' => $user['given_name'],
        'last_name' => $user['last_name'],
        'middle_name' => $user['middle_name'],
        'phone_number' => $user['phone_number']
    ];
    
    echo json_encode($response);

} catch (PDOException $e) {
    $response['message'] = 'Database error occurred. Please try again.';
    error_log('Login PDO Error: ' . $e->getMessage());
    echo json_encode($response);
} catch (Exception $e) {
    $response['message'] = 'An error occurred. Please try again.';
    error_log('Login Exception: ' . $e->getMessage());
    echo json_encode($response);
}
