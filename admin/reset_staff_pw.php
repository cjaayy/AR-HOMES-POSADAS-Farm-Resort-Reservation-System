<?php
/**
 * Reset Staff Password - Admin Only
 * Generates a new random password for a staff member and sends it via email
 */
session_start();
header('Content-Type: application/json');

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check if user is admin (not staff) - accepts admin or super_admin roles
$userRole = strtolower($_SESSION['admin_role'] ?? '');
if ($userRole !== 'admin' && $userRole !== 'super_admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit;
}

require_once '../config/connection.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    $admin_id = (int)($_POST['admin_id'] ?? 0);

    if ($admin_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid staff ID']);
        exit;
    }

    // Check if staff member exists
    $checkStmt = $conn->prepare("SELECT admin_id, full_name, email, username FROM admin_users WHERE admin_id = :id AND role = 'staff'");
    $checkStmt->bindParam(':id', $admin_id, PDO::PARAM_INT);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Staff member not found']);
        exit;
    }

    $staff = $checkStmt->fetch(PDO::FETCH_ASSOC);

    // Generate a random password (8 characters)
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $newPassword = '';
    for ($i = 0; $i < 8; $i++) {
        $newPassword .= $chars[random_int(0, strlen($chars) - 1)];
    }

    // Hash the new password
    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

    // Update the password
    $stmt = $conn->prepare("UPDATE admin_users SET password_hash = :password_hash WHERE admin_id = :id");
    $stmt->bindParam(':password_hash', $passwordHash);
    $stmt->bindParam(':id', $admin_id, PDO::PARAM_INT);
    $stmt->execute();

    // Try to send email with the new password
    $emailSent = false;
    $emailError = '';
    
    try {
        require_once '../config/Mailer.php';
        
        $mailer = new Mailer();
        $emailSent = $mailer->sendStaffPasswordResetEmail(
            $staff['email'],
            $staff['full_name'],
            $newPassword,
            $staff['username']
        );
        
        if (!$emailSent) {
            $emailError = $mailer->getError();
            error_log("Failed to send staff password reset email to: {$staff['email']}. Error: {$emailError}");
        }
    } catch (Exception $e) {
        $emailError = $e->getMessage();
        error_log("Mailer Error for staff password reset: " . $emailError);
    }

    // Prepare response
    $response = [
        'success' => true, 
        'message' => 'Password reset successfully',
        'staff_name' => $staff['full_name'],
        'email_sent' => $emailSent
    ];
    
    // Always include the new password in response so admin can see it
    // (In case email fails, admin can manually share the password)
    $response['new_password'] = $newPassword;
    
    if ($emailSent) {
        $response['message'] = "Password reset successfully! An email has been sent to {$staff['email']} with the new credentials.";
    } else {
        $response['message'] = "Password reset successfully! Email could not be sent. Please share the new password manually.";
        $response['email_error'] = $emailError;
    }

    echo json_encode($response);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>

