<?php
/**
 * Staff Password Change - For Staff Members Only
 * Allows staff to change their own password
 */
session_start();
require_once '../config/connection.php';

// Check if staff is logged in using staff-specific session
if (!isset($_SESSION['staff_logged_in']) || $_SESSION['staff_logged_in'] !== true) {
    header('Location: ../index.html');
    exit;
}

$staffId = $_SESSION['staff_id'] ?? 0;
$staffFullName = $_SESSION['staff_full_name'] ?? 'Staff Member';
$staffUsername = $_SESSION['staff_username'] ?? '';
$staffEmail = $_SESSION['staff_email'] ?? '';

$errors = [];
$successMessage = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validation
    if ($currentPassword === '') {
        $errors[] = 'Current password is required.';
    }
    if ($newPassword === '') {
        $errors[] = 'New password is required.';
    } elseif (strlen($newPassword) < 6) {
        $errors[] = 'New password must be at least 6 characters.';
    }
    if ($newPassword !== $confirmPassword) {
        $errors[] = 'New passwords do not match.';
    }

    if (empty($errors)) {
        try {
            $db = new Database();
            $conn = $db->getConnection();

            // Verify current password
            $stmt = $conn->prepare("SELECT password_hash FROM admin_users WHERE admin_id = :id");
            $stmt->bindParam(':id', $staffId, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result || !password_verify($currentPassword, $result['password_hash'])) {
                $errors[] = 'Current password is incorrect.';
            } else {
                // Update password
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $updateStmt = $conn->prepare("UPDATE admin_users SET password_hash = :password_hash WHERE admin_id = :id");
                $updateStmt->bindParam(':password_hash', $newHash);
                $updateStmt->bindParam(':id', $staffId, PDO::PARAM_INT);
                $updateStmt->execute();

                $successMessage = 'Password changed successfully!';
            }

            $db->closeConnection();
        } catch (Exception $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Change Password - Staff Settings</title>
    <link rel="icon" type="image/png" sizes="32x32" href="../images/logo/ar-homes-logo.png" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 50%, #3a7ca5 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .settings-container {
            width: 100%;
            max-width: 500px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 20px;
            padding: 10px 16px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .back-link:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(-4px);
        }

        .back-link i {
            font-size: 14px;
        }

        .settings-card {
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.25);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%);
            padding: 32px 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .card-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%);
            pointer-events: none;
        }

        .header-icon {
            width: 70px;
            height: 70px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            backdrop-filter: blur(10px);
        }

        .header-icon i {
            font-size: 32px;
            color: #fff;
        }

        .card-header h1 {
            color: #ffffff;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .card-header p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-top: 16px;
            padding: 12px 20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            backdrop-filter: blur(10px);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 16px;
        }

        .user-details {
            text-align: left;
        }

        .user-details .name {
            color: #ffffff;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .user-details .username {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.8rem;
        }

        .card-body {
            padding: 36px 40px 40px;
        }

        .alert {
            padding: 16px 20px;
            border-radius: 14px;
            margin-bottom: 24px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .alert-error {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border: 1px solid #f87171;
        }

        .alert-error i {
            color: #dc2626;
            font-size: 20px;
            margin-top: 2px;
        }

        .alert-error ul {
            list-style: none;
            color: #b91c1c;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .alert-error li {
            margin-bottom: 4px;
        }

        .alert-success {
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            border: 1px solid #4ade80;
            color: #166534;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .alert-success i {
            color: #16a34a;
            font-size: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #475569;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .form-group label .required {
            color: #ef4444;
            margin-left: 2px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper > i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 16px;
            pointer-events: none;
            transition: color 0.3s ease;
            z-index: 1;
        }

        .form-group input {
            width: 100%;
            padding: 14px 46px 14px 46px;
            font-size: 0.95rem;
            font-family: inherit;
            color: #1e293b;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #3a7ca5;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(58, 124, 165, 0.1);
        }

        .form-group input::placeholder {
            color: #94a3b8;
        }

        .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 8px;
            transition: color 0.3s ease;
            z-index: 2;
        }

        .toggle-password:hover {
            color: #3a7ca5;
        }

        .toggle-password i {
            pointer-events: none;
        }

        .password-strength {
            margin-top: 8px;
            height: 4px;
            background: #e2e8f0;
            border-radius: 2px;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
            border-radius: 2px;
        }

        .password-requirements {
            margin-top: 12px;
            padding: 12px 16px;
            background: #f8fafc;
            border-radius: 10px;
            border-left: 3px solid #3a7ca5;
        }

        .password-requirements h4 {
            font-size: 0.8rem;
            color: #64748b;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .password-requirements ul {
            list-style: none;
            font-size: 0.8rem;
            color: #64748b;
        }

        .password-requirements li {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 4px;
        }

        .password-requirements li i {
            font-size: 10px;
        }

        .password-requirements li.valid {
            color: #16a34a;
        }

        .password-requirements li.valid i {
            color: #16a34a;
        }

        .form-actions {
            display: flex;
            gap: 14px;
            margin-top: 28px;
            padding-top: 24px;
            border-top: 2px solid #f1f5f9;
        }

        .btn {
            flex: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 16px 24px;
            font-size: 1rem;
            font-weight: 600;
            font-family: inherit;
            border-radius: 14px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn i {
            font-size: 16px;
        }

        .btn-cancel {
            background: #f1f5f9;
            color: #64748b;
        }

        .btn-cancel:hover {
            background: #e2e8f0;
            color: #475569;
        }

        .btn-save {
            background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%);
            color: #ffffff;
            box-shadow: 0 4px 15px rgba(30, 58, 95, 0.3);
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(30, 58, 95, 0.4);
        }

        @media (max-width: 500px) {
            .card-header {
                padding: 24px 20px;
            }

            .card-body {
                padding: 24px 20px 28px;
            }

            .form-actions {
                flex-direction: column-reverse;
            }
        }
    </style>
</head>
<body>

<div class="settings-container">
    <a href="staff_dashboard.php" class="back-link">
        <i class="fas fa-arrow-left"></i>
        Back to Dashboard
    </a>

    <div class="settings-card">
        <div class="card-header">
            <div class="header-icon">
                <i class="fas fa-key"></i>
            </div>
            <h1>Change Password</h1>
            <p>Update your account security</p>

            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($staffFullName, 0, 1)); ?>
                </div>
                <div class="user-details">
                    <div class="name"><?php echo htmlspecialchars($staffFullName); ?></div>
                    <div class="username">@<?php echo htmlspecialchars($staffUsername); ?></div>
                </div>
            </div>
        </div>

        <div class="card-body">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <ul>
                        <?php foreach ($errors as $err): ?>
                            <li><?php echo htmlspecialchars($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($successMessage): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo htmlspecialchars($successMessage); ?></span>
                </div>
            <?php endif; ?>

            <form method="post" id="changePasswordForm" novalidate>
                <div class="form-group">
                    <label>Current Password <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="current_password" name="current_password" required placeholder="Enter your current password">
                        <button type="button" class="toggle-password" onclick="togglePassword('current_password', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label>New Password <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-key"></i>
                        <input type="password" id="new_password" name="new_password" required placeholder="Enter your new password">
                        <button type="button" class="toggle-password" onclick="togglePassword('new_password', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="password-strength">
                        <div class="password-strength-bar" id="strengthBar"></div>
                    </div>
                    <div class="password-requirements">
                        <h4>Password Requirements:</h4>
                        <ul>
                            <li id="req-length"><i class="fas fa-circle"></i> At least 6 characters</li>
                            <li id="req-upper"><i class="fas fa-circle"></i> One uppercase letter (recommended)</li>
                            <li id="req-number"><i class="fas fa-circle"></i> One number (recommended)</li>
                        </ul>
                    </div>
                </div>

                <div class="form-group">
                    <label>Confirm New Password <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-shield-alt"></i>
                        <input type="password" id="confirm_password" name="confirm_password" required placeholder="Re-enter your new password">
                        <button type="button" class="toggle-password" onclick="togglePassword('confirm_password', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-cancel" onclick="window.location.href='staff_dashboard.php'">
                        <i class="fas fa-times"></i>
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-save">
                        <i class="fas fa-save"></i>
                        Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Toggle password visibility
function togglePassword(inputId, button) {
    const input = document.getElementById(inputId);
    const icon = button.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Password strength indicator
const newPasswordInput = document.getElementById('new_password');
const strengthBar = document.getElementById('strengthBar');

newPasswordInput.addEventListener('input', function() {
    const password = this.value;
    let strength = 0;
    
    // Check length
    const hasLength = password.length >= 6;
    const hasUpper = /[A-Z]/.test(password);
    const hasNumber = /[0-9]/.test(password);
    
    if (hasLength) strength += 40;
    if (password.length >= 8) strength += 20;
    if (hasUpper) strength += 20;
    if (hasNumber) strength += 20;
    
    strengthBar.style.width = strength + '%';
    
    if (strength <= 40) {
        strengthBar.style.background = '#ef4444';
    } else if (strength <= 60) {
        strengthBar.style.background = '#f59e0b';
    } else if (strength <= 80) {
        strengthBar.style.background = '#3b82f6';
    } else {
        strengthBar.style.background = '#22c55e';
    }

    // Update requirements
    document.getElementById('req-length').className = hasLength ? 'valid' : '';
    document.getElementById('req-upper').className = hasUpper ? 'valid' : '';
    document.getElementById('req-number').className = hasNumber ? 'valid' : '';
});

// Form validation
document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
    const currentPw = document.getElementById('current_password').value;
    const newPw = document.getElementById('new_password').value;
    const confirmPw = document.getElementById('confirm_password').value;
    
    let errors = [];
    
    if (currentPw === '') {
        errors.push('Current password is required.');
    }
    
    if (newPw === '') {
        errors.push('New password is required.');
    } else if (newPw.length < 6) {
        errors.push('New password must be at least 6 characters.');
    }
    
    if (newPw !== confirmPw) {
        errors.push('New passwords do not match.');
    }
    
    if (errors.length > 0) {
        alert(errors.join('\n'));
        e.preventDefault();
    }
});
</script>

</body>
</html>

