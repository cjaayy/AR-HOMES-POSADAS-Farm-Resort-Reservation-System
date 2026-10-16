<?php
/**
 * Update Admin Profile
 * Handles admin profile updates including name, email, and password changes
 */

header('Content-Type: application/json');
header("Cache-Control: no-cache, no-store, must-revalidate");

session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

require_once '../config/connection.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        // Try regular POST
        $data = $_POST;
    }
    
    $adminId = $_SESSION['admin_id'];
    $fullName = trim($data['fullName'] ?? '');
    $email = trim($data['email'] ?? '');
    $currentPassword = $data['currentPassword'] ?? '';
    $newPassword = $data['newPassword'] ?? '';
    $confirmPassword = $data['confirmPassword'] ?? '';
    
    // Validation
    if (empty($fullName)) {
        echo json_encode(['success' => false, 'message' => 'Full name is required']);
        exit;
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Valid email is required']);
        exit;
    }
    
    if (empty($currentPassword)) {
        echo json_encode(['success' => false, 'message' => 'Current password is required to make changes']);
        exit;
    }
    
    // Verify current password
    $query = "SELECT password FROM admin_users WHERE admin_id = :admin_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':admin_id', $adminId, PDO::PARAM_INT);
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin) {
        echo json_encode(['success' => false, 'message' => 'Admin user not found']);
        exit;
    }
    
    // Check if password is hashed (starts with $2y$ for bcrypt) or plain text
    if (strpos($admin['password'], '$2y$') === 0) {
        // Password is hashed
        if (!password_verify($currentPassword, $admin['password'])) {
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
            exit;
        }
    } else {
        // Password is plain text (legacy)
        if ($currentPassword !== $admin['password']) {
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
            exit;
        }
    }
    
    // Check if email is already taken by another admin
    $checkQuery = "SELECT admin_id FROM admin_users WHERE email = :email AND admin_id != :admin_id";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bindParam(':email', $email);
    $checkStmt->bindParam(':admin_id', $adminId, PDO::PARAM_INT);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Email is already in use by another account']);
        exit;
    }
    
    // Build update query
    $updateFields = ['full_name = :full_name', 'email = :email'];
    $params = [
        ':full_name' => $fullName,
        ':email' => $email,
        ':admin_id' => $adminId
    ];
    
    // If new password is provided, validate and include it
    if (!empty($newPassword)) {
        if (strlen($newPassword) < 6) {
            echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters']);
            exit;
        }
        
        if ($newPassword !== $confirmPassword) {
            echo json_encode(['success' => false, 'message' => 'New passwords do not match']);
            exit;
        }
        
        // Hash the new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $updateFields[] = 'password = :password';
        $params[':password'] = $hashedPassword;
    }
    
    // Update admin profile
    $updateQuery = "UPDATE admin_users SET " . implode(', ', $updateFields) . " WHERE admin_id = :admin_id";
    $updateStmt = $conn->prepare($updateQuery);
    
    foreach ($params as $key => $value) {
        $updateStmt->bindValue($key, $value);
    }
    
    if ($updateStmt->execute()) {
        // Update session data
        $_SESSION['admin_full_name'] = $fullName;
        $_SESSION['admin_email'] = $email;
        
        echo json_encode([
            'success' => true, 
            'message' => 'Profile updated successfully',
            'data' => [
                'fullName' => $fullName,
                'email' => $email,
                'passwordChanged' => !empty($newPassword)
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
    }
    
} catch (PDOException $e) {
    error_log("Admin profile update error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("Admin profile update error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}

