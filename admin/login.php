<?php
/**
 * Admin Login Handler
 * AR Homes Posadas Farm Resort Reservation System
 */

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
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($input['username']) || !isset($input['password'])) {
    $response['message'] = 'Username and password are required';
    echo json_encode($response);
    exit;
}

$username = trim($input['username']);
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
    $sql = "SELECT admin_id, username, full_name, email, password_hash, role, is_active, last_login, created_at 
            FROM admin_users 
            WHERE username = :username OR email = :email 
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->bindParam(':email', $username, PDO::PARAM_STR);
    $stmt->execute();

    // Check if admin exists
    if ($stmt->rowCount() === 0) {
        $response['message'] = 'Invalid username or password';
        echo json_encode($response);
        exit;
    }

    // Fetch admin data
    $admin = $stmt->fetch();

    // Check if account is active
    if ($admin['is_active'] != 1) {
        $response['message'] = 'Account is inactive. Please contact support.';
        echo json_encode($response);
        exit;
    }

    // Verify password
    if (!password_verify($password, $admin['password_hash'])) {
        $response['message'] = 'Invalid username or password';
        echo json_encode($response);
        exit;
    }

    // Update last login time
    $updateSql = "UPDATE admin_users SET last_login = NOW() WHERE admin_id = :admin_id";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bindParam(':admin_id', $admin['admin_id'], PDO::PARAM_INT);
    $updateStmt->execute();

    // Set session variables
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_id'] = $admin['admin_id'];
    $_SESSION['admin_username'] = $admin['username'];
    $_SESSION['admin_full_name'] = $admin['full_name'];
    $_SESSION['admin_email'] = $admin['email'];
    $_SESSION['admin_role'] = $admin['role'];
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();

    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);

    // Prepare success response
    $response['success'] = true;
    $response['message'] = 'Login successful';
    $response['data'] = [
        'admin_id' => $admin['admin_id'],
        'username' => $admin['username'],
        'full_name' => $admin['full_name'],
        'email' => $admin['email'],
        'role' => $admin['role']
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

