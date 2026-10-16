<?php
/**
 * User Registration Handler
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

// Validate required fields
$requiredFields = ['lastName', 'givenName', 'middleName', 'email', 'phoneNumber', 'username', 'password'];
foreach ($requiredFields as $field) {
    if (!isset($input[$field]) || empty(trim($input[$field]))) {
        $response['message'] = ucfirst($field) . ' is required';
        echo json_encode($response);
        exit;
    }
}

// Sanitize inputs
$lastName = trim($input['lastName']);
$givenName = trim($input['givenName']);
$middleName = trim($input['middleName']);
$email = trim($input['email']);
$phoneNumber = trim($input['phoneNumber']);
$username = trim($input['username']);
$password = $input['password'];

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'Invalid email format';
    echo json_encode($response);
    exit;
}

// Validate password strength (minimum 6 characters)
if (strlen($password) < 6) {
    $response['message'] = 'Password must be at least 6 characters long';
    echo json_encode($response);
    exit;
}

try {
    // Create database connection
    $database = new Database();
    $conn = $database->getConnection();
    
    // Include ID Generator
    require_once '../config/IDGenerator.php';

    // Check if email already exists
    $checkEmail = $conn->prepare("SELECT user_id FROM users WHERE email = :email");
    $checkEmail->bindParam(':email', $email);
    $checkEmail->execute();
    
    if ($checkEmail->rowCount() > 0) {
        $response['message'] = 'Email address is already registered';
        echo json_encode($response);
        exit;
    }

    // Check if username already exists
    $checkUsername = $conn->prepare("SELECT user_id FROM users WHERE username = :username");
    $checkUsername->bindParam(':username', $username);
    $checkUsername->execute();
    
    if ($checkUsername->rowCount() > 0) {
        $response['message'] = 'Username is already taken';
        echo json_encode($response);
        exit;
    }

    // Hash password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Create full name
    $fullName = $lastName . ', ' . $givenName . ' ' . $middleName;

    // Generate new format user ID
    $userId = IDGenerator::generateUserId($conn);
    
    $requireEmailVerification = defined('REQUIRE_EMAIL_VERIFICATION') ? REQUIRE_EMAIL_VERIFICATION : false;

    // Generate email verification token if required
    $emailVerifiedVal = $requireEmailVerification ? 0 : 1;
    $verificationToken = bin2hex(random_bytes(32));
    $verificationTokenHash = $requireEmailVerification ? hash('sha256', $verificationToken) : null;
    $verificationExpiresAt = $requireEmailVerification ? date('Y-m-d H:i:s', strtotime('+24 hours')) : null;

    // Insert new user
    $sql = "INSERT INTO users 
            (user_id, username, email, password_hash, last_name, given_name, middle_name, full_name, phone_number, 
             email_verified, email_verification_token, email_verification_expires, is_active) 
            VALUES 
            (:user_id, :username, :email, :password_hash, :last_name, :given_name, :middle_name, :full_name, :phone_number,
             :email_verified, :verification_token, :verification_expires, 1)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password_hash', $passwordHash);
    $stmt->bindParam(':last_name', $lastName);
    $stmt->bindParam(':given_name', $givenName);
    $stmt->bindParam(':middle_name', $middleName);
    $stmt->bindParam(':full_name', $fullName);
    $stmt->bindParam(':phone_number', $phoneNumber);
    $stmt->bindParam(':email_verified', $emailVerifiedVal, PDO::PARAM_INT);
    $stmt->bindParam(':verification_token', $verificationTokenHash);
    $stmt->bindParam(':verification_expires', $verificationExpiresAt);
    
    $stmt->execute();

    if ($requireEmailVerification) {
        // Create verification link using ngrok if configured
        require_once '../config/cloudflare.php';
        
        $verificationPath = "/user/verify_email.php?token={$verificationToken}";
        $verificationLink = buildVerificationUrl($verificationPath);

        // Send email verification email
        try {
            require_once '../config/Mailer.php';
            $mailer = new Mailer();
            
            $emailSent = $mailer->sendEmailVerificationEmail(
                $email,
                $givenName,
                $verificationLink,
                $verificationExpiresAt
            );
            
            if (!$emailSent) {
                $error = $mailer->getError();
                error_log("Registration email failed for {$email}: {$error}");
            }
        } catch (Exception $e) {
            error_log("Mailer Exception: " . $e->getMessage());
        }

        // Log verification link for development
        $logDir = __DIR__ . '/email_verifications';
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/verification_log_' . date('Y-m-d') . '.txt';
        $logMessage = "\n" . str_repeat('=', 80) . "\n";
        $logMessage .= "Email Verification Request\n";
        $logMessage .= "Time: " . date('Y-m-d H:i:s') . "\n";
        $logMessage .= "User: {$fullName} ({$username})\n";
        $logMessage .= "Email: {$email}\n";
        $logMessage .= "Verification Link: {$verificationLink}\n";
        $logMessage .= "Expires: {$verificationExpiresAt}\n";
        $logMessage .= str_repeat('=', 80) . "\n";
        
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }

    // Prepare success response
    $response['success'] = true;
    $response['message'] = $requireEmailVerification 
        ? 'Registration successful! Please check your email to verify your account before logging in.'
        : 'Registration successful! You can now log in with your credentials.';
    $response['data'] = [
        'user_id' => $userId,
        'username' => $username,
        'email' => $email,
        'full_name' => $fullName,
        'email_verified' => !$requireEmailVerification,
        'verification_required' => $requireEmailVerification
    ];

    // In development mode, include verification link if required
    if ($requireEmailVerification && defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE === true && isset($verificationLink)) {
        $response['data']['verification_link'] = $verificationLink;
    }

    echo json_encode($response);

} catch (PDOException $e) {
    $response['message'] = 'Database error occurred. Please try again.';
    error_log('Registration PDO Error: ' . $e->getMessage());
    echo json_encode($response);
} catch (Exception $e) {
    $response['message'] = 'An error occurred. Please try again.';
    error_log('Registration Exception: ' . $e->getMessage());
    echo json_encode($response);
}
