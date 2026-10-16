<?php
/**
 * Update User API Endpoint
 * AR Homes Posadas Farm Resort Reservation System
 * 
 * If email is changed, it requires re-verification before becoming active
 */

session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

// Include database connection
require_once '../config/connection.php';
require_once '../config/cloudflare.php';
require_once '../config/Mailer.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['user_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing user ID'
    ]);
    exit;
}

$userId = trim($input['user_id']);

try {
    $database = new Database();
    $conn = $database->getConnection();

    // First, get the current user data to check for email change
    $getCurrentUser = $conn->prepare("SELECT email, full_name, given_name FROM users WHERE user_id = :user_id");
    $getCurrentUser->bindParam(':user_id', $userId);
    $getCurrentUser->execute();
    $currentUser = $getCurrentUser->fetch(PDO::FETCH_ASSOC);

    if (!$currentUser) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
        exit;
    }

    $emailChanged = false;
    $newEmail = null;
    $verificationRequired = false;

    // Build update query dynamically based on provided fields
    $updateFields = [];
    $params = [':user_id' => $userId];

    if (isset($input['full_name'])) {
        $updateFields[] = "full_name = :full_name";
        $params[':full_name'] = $input['full_name'];
        
        // Parse full_name to extract individual name parts (Last, Given Middle format)
        $fullName = trim($input['full_name']);
        if (strpos($fullName, ',') !== false) {
            $parts = explode(',', $fullName, 2);
            $lastName = trim($parts[0]);
            $givenMiddle = isset($parts[1]) ? trim($parts[1]) : '';
            $givenParts = explode(' ', $givenMiddle, 2);
            $givenName = isset($givenParts[0]) ? trim($givenParts[0]) : '';
            $middleName = isset($givenParts[1]) ? trim($givenParts[1]) : '';
            
            $updateFields[] = "last_name = :last_name";
            $params[':last_name'] = $lastName;
            $updateFields[] = "given_name = :given_name";
            $params[':given_name'] = $givenName;
            $updateFields[] = "middle_name = :middle_name";
            $params[':middle_name'] = $middleName;
        }
    }
    
    if (isset($input['username'])) {
        // Check if username is already taken by another user
        $checkUsername = $conn->prepare("SELECT user_id FROM users WHERE username = :username AND user_id != :user_id");
        $checkUsername->bindParam(':username', $input['username']);
        $checkUsername->bindParam(':user_id', $userId);
        $checkUsername->execute();
        
        if ($checkUsername->rowCount() > 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Username is already taken by another user'
            ]);
            exit;
        }
        
        $updateFields[] = "username = :username";
        $params[':username'] = $input['username'];
    }
    
    // Handle email change - requires re-verification
    if (isset($input['email']) && $input['email'] !== $currentUser['email']) {
        $newEmail = trim($input['email']);
        
        // Validate email format
        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid email format'
            ]);
            exit;
        }
        
        // Check if email is already taken by another user
        $checkEmail = $conn->prepare("SELECT user_id FROM users WHERE email = :email AND user_id != :user_id");
        $checkEmail->bindParam(':email', $newEmail);
        $checkEmail->bindParam(':user_id', $userId);
        $checkEmail->execute();
        
        if ($checkEmail->rowCount() > 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Email is already registered to another user'
            ]);
            exit;
        }
        
        // Also check pending_email field
        $checkPendingEmail = $conn->prepare("SELECT user_id FROM users WHERE pending_email = :email AND user_id != :user_id");
        $checkPendingEmail->bindParam(':email', $newEmail);
        $checkPendingEmail->bindParam(':user_id', $userId);
        $checkPendingEmail->execute();
        
        if ($checkPendingEmail->rowCount() > 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Email is already pending verification for another user'
            ]);
            exit;
        }
        
        $emailChanged = true;
        $verificationRequired = true;
        
        // Generate verification token for new email
        $verificationToken = bin2hex(random_bytes(32));
        $verificationTokenHash = hash('sha256', $verificationToken);
        $verificationExpiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        // Store new email as pending (don't change the main email yet)
        $updateFields[] = "pending_email = :pending_email";
        $params[':pending_email'] = $newEmail;
        $updateFields[] = "pending_email_token = :pending_email_token";
        $params[':pending_email_token'] = $verificationTokenHash;
        $updateFields[] = "pending_email_expires = :pending_email_expires";
        $params[':pending_email_expires'] = $verificationExpiresAt;
    }
    
    if (isset($input['phone_number'])) {
        $updateFields[] = "phone_number = :phone_number";
        $params[':phone_number'] = $input['phone_number'];
    }
    
    if (isset($input['loyalty_level'])) {
        $updateFields[] = "loyalty_level = :loyalty_level";
        $params[':loyalty_level'] = $input['loyalty_level'];
    }

    if (empty($updateFields)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'No fields to update'
        ]);
        exit;
    }

    // Add updated_at timestamp
    $updateFields[] = "updated_at = NOW()";

    $query = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE user_id = :user_id";
    $stmt = $conn->prepare($query);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    if ($stmt->execute()) {
        $message = 'User updated successfully';
        $emailSent = false;
        
        // Send verification email if email was changed
        if ($emailChanged && $verificationRequired) {
            try {
                $mailer = new Mailer();
                $recipientName = isset($input['full_name']) ? $input['full_name'] : $currentUser['full_name'];
                $givenName = isset($params[':given_name']) ? $params[':given_name'] : $currentUser['given_name'];
                
                $verificationPath = "/user/verify_email_change.php?token={$verificationToken}";
                $verificationLink = buildVerificationUrl($verificationPath);
                
                $emailSent = $mailer->sendEmailChangeVerificationEmail(
                    $newEmail,
                    $givenName,
                    $verificationLink,
                    $verificationExpiresAt,
                    $currentUser['email']
                );
                
                if ($emailSent) {
                    $message = 'User updated. A verification email has been sent to the new email address. The email will only change after verification.';
                } else {
                    $message = 'User updated. However, the verification email could not be sent. The user can request a new verification email.';
                }
                
                // Log the verification for development
                $logDir = __DIR__ . '/../user/email_verifications';
                if (!file_exists($logDir)) {
                    mkdir($logDir, 0755, true);
                }
                
                $logFile = $logDir . '/verification_log_' . date('Y-m-d') . '.txt';
                $logMessage = "\n" . str_repeat('=', 80) . "\n";
                $logMessage .= "Email Change Verification Request (Admin)\n";
                $logMessage .= "Time: " . date('Y-m-d H:i:s') . "\n";
                $logMessage .= "User ID: {$userId}\n";
                $logMessage .= "User: {$recipientName}\n";
                $logMessage .= "Old Email: {$currentUser['email']}\n";
                $logMessage .= "New Email: {$newEmail}\n";
                $logMessage .= "Verification Link: {$verificationLink}\n";
                $logMessage .= "Expires: {$verificationExpiresAt}\n";
                $logMessage .= str_repeat('=', 80) . "\n";
                
                file_put_contents($logFile, $logMessage, FILE_APPEND);
                
            } catch (Exception $e) {
                error_log("Failed to send email change verification: " . $e->getMessage());
                $message = 'User updated, but verification email failed to send.';
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'user_id' => $userId,
            'email_changed' => $emailChanged,
            'verification_required' => $verificationRequired,
            'email_sent' => $emailSent ?? false
        ]);
    } else {
        throw new Exception('Failed to update user');
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>

