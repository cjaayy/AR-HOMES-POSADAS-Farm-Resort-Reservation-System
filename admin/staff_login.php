<?php
/**
 * Staff Login Handler
 * AR Homes Posadas Farm Resort Reservation System
 * Uses separate session variables from admin to allow concurrent logins
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

    // Prepare SQL statement - only allow staff role
    $sql = "SELECT admin_id, username, full_name, email, password_hash, role, is_active, last_login, created_at 
            FROM admin_users 
            WHERE (username = :username OR email = :email) AND role = 'staff'
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->bindParam(':email', $username, PDO::PARAM_STR);
    $stmt->execute();

    // Check if staff exists
    if ($stmt->rowCount() === 0) {
        $response['message'] = 'Invalid username or password';
        echo json_encode($response);
        exit;
    }

    // Fetch staff data
    $staff = $stmt->fetch();

    // Check if account is active
    if ($staff['is_active'] != 1) {
        $response['message'] = 'Account is inactive. Please contact support.';
        echo json_encode($response);
        exit;
    }

    // Verify password
    if (!password_verify($password, $staff['password_hash'])) {
        $response['message'] = 'Invalid username or password';
        echo json_encode($response);
        exit;
    }

    // Update last login time
    $updateSql = "UPDATE admin_users SET last_login = NOW() WHERE admin_id = :admin_id";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bindParam(':admin_id', $staff['admin_id'], PDO::PARAM_INT);
    $updateStmt->execute();

    // Set STAFF-SPECIFIC session variables (separate from admin)
    $_SESSION['staff_logged_in'] = true;
    $_SESSION['staff_id'] = $staff['admin_id'];
    $_SESSION['staff_username'] = $staff['username'];
    $_SESSION['staff_full_name'] = $staff['full_name'];
    $_SESSION['staff_email'] = $staff['email'];
    $_SESSION['staff_role'] = $staff['role'];
    $_SESSION['staff_login_time'] = time();
    $_SESSION['staff_last_activity'] = time();

    // Prepare success response
    $response['success'] = true;
    $response['message'] = 'Login successful';
    $response['data'] = [
        'staff_id' => $staff['admin_id'],
        'username' => $staff['username'],
        'full_name' => $staff['full_name'],
        'email' => $staff['email'],
        'role' => $staff['role']
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

