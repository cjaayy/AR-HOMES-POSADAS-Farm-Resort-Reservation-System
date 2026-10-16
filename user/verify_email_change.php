<?php
/**
 * Email Change Verification Handler
 * AR Homes Posadas Farm Resort Reservation System
 * 
 * Handles verification when admin or user changes their email address
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();

require_once '../config/connection.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Change Verification - AR Homes Posadas Farm Resort</title>
    
    <link rel="icon" type="image/png" sizes="32x32" href="../images/logo/ar-homes-logo.png" />
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css" />
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            background: white;
            border-radius: 20px;
            padding: 50px 40px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            text-align: center;
        }
        .icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        .icon.success { color: #28a745; }
        .icon.error { color: #dc3545; }
        .icon.warning { color: #ffc107; }
        .icon.loading { color: #667eea; }
        
        h1 {
            color: #333;
            font-size: 2rem;
            margin-bottom: 15px;
        }
        p {
            color: #666;
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        .btn {
            display: inline-block;
            padding: 15px 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            margin-top: 20px;
            transition: all 0.3s ease;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
            border-radius: 5px;
        }
        .info-box p {
            font-size: 0.95rem;
            margin-bottom: 10px;
        }
        .email-display {
            font-weight: 600;
            color: #667eea;
            background: #f0f0ff;
            padding: 5px 15px;
            border-radius: 5px;
            display: inline-block;
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php
        // Check if token is provided
        if (!isset($_GET['token']) || empty($_GET['token'])) {
            echo '<div class="icon error"><i class="fas fa-exclamation-circle"></i></div>';
            echo '<h1>Invalid Verification Link</h1>';
            echo '<p>No verification token provided.</p>';
            echo '<a href="../index.html" class="btn"><i class="fas fa-home"></i> Go Home</a>';
            exit;
        }

        $token = $_GET['token'];
        $tokenHash = hash('sha256', $token);

        try {
            // Create database connection
            $database = new Database();
            $conn = $database->getConnection();

            // Find user with valid pending email verification token
            $sql = "SELECT user_id, username, email, full_name, pending_email, pending_email_expires 
                    FROM users 
                    WHERE pending_email_token = :token 
                    AND pending_email IS NOT NULL
                    LIMIT 1";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':token', $tokenHash, PDO::PARAM_STR);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                echo '<div class="icon error"><i class="fas fa-times-circle"></i></div>';
                echo '<h1>Invalid Token</h1>';
                echo '<p>This verification link is invalid or has already been used.</p>';
                echo '<div class="info-box">';
                echo '<p><strong>Possible reasons:</strong></p>';
                echo '<p>• The email change has already been verified</p>';
                echo '<p>• The token has expired (24 hours)</p>';
                echo '<p>• The link was incorrect</p>';
                echo '<p>• The email change request was cancelled</p>';
                echo '</div>';
                echo '<a href="../index.html" class="btn"><i class="fas fa-home"></i> Go Home</a>';
                exit;
            }

            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $oldEmail = $user['email'];
            $newEmail = $user['pending_email'];

            // Check if token has expired
            $expiresAt = strtotime($user['pending_email_expires']);
            $currentTime = time();

            if ($currentTime > $expiresAt) {
                // Clear expired pending email
                $clearSql = "UPDATE users SET 
                             pending_email = NULL,
                             pending_email_token = NULL,
                             pending_email_expires = NULL
                             WHERE user_id = :user_id";
                $clearStmt = $conn->prepare($clearSql);
                $clearStmt->bindParam(':user_id', $user['user_id']);
                $clearStmt->execute();

                echo '<div class="icon error"><i class="fas fa-clock"></i></div>';
                echo '<h1>Token Expired</h1>';
                echo '<p>This verification link has expired.</p>';
                echo '<p>Verification links are valid for 24 hours.</p>';
                echo '<div class="info-box">';
                echo '<p><strong>What to do next:</strong></p>';
                echo '<p>Please contact the administrator to request a new email change or update your profile again.</p>';
                echo '</div>';
                echo '<a href="../index.html" class="btn"><i class="fas fa-home"></i> Go Home</a>';
                exit;
            }

            // Update the email address
            $updateSql = "UPDATE users SET 
                          email = :new_email,
                          pending_email = NULL,
                          pending_email_token = NULL,
                          pending_email_expires = NULL,
                          updated_at = NOW()
                          WHERE user_id = :user_id";
            
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bindParam(':new_email', $newEmail);
            $updateStmt->bindParam(':user_id', $user['user_id']);
            $updateStmt->execute();

            // Update session if this is the currently logged in user
            if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true 
                && isset($_SESSION['user_id']) && $_SESSION['user_id'] === $user['user_id']) {
                $_SESSION['user_email'] = $newEmail;
            }

            // Log the email change
            $logDir = __DIR__ . '/email_verifications';
            if (!file_exists($logDir)) {
                mkdir($logDir, 0755, true);
            }
            
            $logFile = $logDir . '/verification_log_' . date('Y-m-d') . '.txt';
            $logMessage = "\n" . str_repeat('=', 80) . "\n";
            $logMessage .= "Email Change Completed\n";
            $logMessage .= "Time: " . date('Y-m-d H:i:s') . "\n";
            $logMessage .= "User: {$user['full_name']} ({$user['username']})\n";
            $logMessage .= "Old Email: {$oldEmail}\n";
            $logMessage .= "New Email: {$newEmail}\n";
            $logMessage .= str_repeat('=', 80) . "\n";
            
            file_put_contents($logFile, $logMessage, FILE_APPEND);

            // Success!
            echo '<div class="icon success"><i class="fas fa-check-circle"></i></div>';
            echo '<h1>Email Changed Successfully! 🎉</h1>';
            echo '<p>Hello <strong>' . htmlspecialchars($user['full_name']) . '</strong>!</p>';
            echo '<p>Your email address has been successfully updated.</p>';
            echo '<div class="info-box">';
            echo '<p><strong>Email Change Details:</strong></p>';
            echo '<p>Old email: <span class="email-display">' . htmlspecialchars($oldEmail) . '</span></p>';
            echo '<p>New email: <span class="email-display">' . htmlspecialchars($newEmail) . '</span></p>';
            echo '</div>';
            echo '<p>You can now use your new email address to log in and receive notifications.</p>';
            echo '<a href="../index.html" class="btn"><i class="fas fa-sign-in-alt"></i> Go to Login</a>';

        } catch (PDOException $e) {
            echo '<div class="icon error"><i class="fas fa-database"></i></div>';
            echo '<h1>Database Error</h1>';
            echo '<p>An error occurred while verifying your email change.</p>';
            echo '<p style="color: #dc3545; font-size: 0.9rem;">Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
            error_log("Email change verification error: " . $e->getMessage());
            echo '<a href="../index.html" class="btn"><i class="fas fa-home"></i> Go Home</a>';
        } catch (Exception $e) {
            echo '<div class="icon error"><i class="fas fa-exclamation-triangle"></i></div>';
            echo '<h1>Error</h1>';
            echo '<p>An unexpected error occurred.</p>';
            echo '<p style="color: #dc3545; font-size: 0.9rem;">Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
            error_log("Email change verification exception: " . $e->getMessage());
            echo '<a href="../index.html" class="btn"><i class="fas fa-home"></i> Go Home</a>';
        }
        ?>
    </div>
</body>
</html>

