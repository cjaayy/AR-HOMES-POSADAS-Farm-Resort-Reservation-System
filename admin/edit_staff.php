<?php
session_start();
require_once '../config/connection.php';

// Simple admin session check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../index.html');
    exit;
}

// Check if user is admin (not staff) - accepts admin or super_admin roles
$userRole = strtolower($_SESSION['admin_role'] ?? '');
if ($userRole !== 'admin' && $userRole !== 'super_admin') {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$successMessage = '';
$staff = null;

// Get staff ID
$staffId = (int)($_GET['id'] ?? $_POST['admin_id'] ?? 0);

if ($staffId <= 0) {
    header('Location: dashboard.php#staff');
    exit;
}

// Database connection
try {
    $db = new Database();
    $conn = $db->getConnection();

    // Fetch staff data
    $stmt = $conn->prepare("SELECT * FROM admin_users WHERE admin_id = :id AND role = 'staff'");
    $stmt->bindParam(':id', $staffId, PDO::PARAM_INT);
    $stmt->execute();
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$staff) {
        $_SESSION['flash_error'] = 'Staff member not found.';
        header('Location: dashboard.php#staff');
        exit;
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $fullName = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $status = ($_POST['status'] ?? 'inactive') === 'active' ? 1 : 0;
        $newPassword = $_POST['new_password'] ?? '';

        // Basic validation
        if ($fullName === '') { $errors[] = 'Full Name is required.'; }
        if ($username === '') { $errors[] = 'Username is required.'; }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'A valid Email Address is required.'; }
        if ($newPassword !== '' && strlen($newPassword) < 6) { $errors[] = 'Password must be at least 6 characters.'; }

        if (empty($errors)) {
            // Check username/email uniqueness (excluding current staff)
            $stmt = $conn->prepare("SELECT admin_id FROM admin_users WHERE (username = :username OR email = :email) AND admin_id != :id LIMIT 1");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':id', $staffId, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $errors[] = 'Username or email already exists for another account.';
            } else {
                // Build update query
                $updateFields = "full_name = :full_name, username = :username, email = :email, is_active = :is_active";
                $params = [
                    ':full_name' => $fullName,
                    ':username' => $username,
                    ':email' => $email,
                    ':is_active' => $status,
                    ':id' => $staffId
                ];

                // Add password if provided
                if ($newPassword !== '') {
                    $updateFields .= ", password_hash = :password_hash";
                    $params[':password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
                }

                $updateSql = "UPDATE admin_users SET $updateFields WHERE admin_id = :id";
                $updateStmt = $conn->prepare($updateSql);
                
                foreach ($params as $key => $value) {
                    $updateStmt->bindValue($key, $value);
                }
                
                $updateStmt->execute();

                $_SESSION['flash_success'] = '✅ Staff account updated successfully!';
                header('Location: dashboard.php#staff');
                exit;
            }
        }

        // Update staff array with posted values for form repopulation
        $staff['full_name'] = $fullName;
        $staff['username'] = $username;
        $staff['email'] = $email;
        $staff['is_active'] = $status;
    }

    $db->closeConnection();

} catch (Exception $e) {
    $errors[] = 'Database error: ' . $e->getMessage();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Staff Account - AR Homes Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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

        .edit-staff-container {
            width: 100%;
            max-width: 580px;
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

        .back-link svg {
            width: 18px;
            height: 18px;
        }

        .staff-card {
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

        .header-icon svg {
            width: 36px;
            height: 36px;
            color: #fff;
        }

        .card-header h1 {
            color: #ffffff;
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .card-header p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.95rem;
            font-weight: 400;
        }

        .staff-id-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.2);
            padding: 10px 20px;
            border-radius: 50px;
            margin-top: 16px;
            backdrop-filter: blur(10px);
        }

        .staff-id-badge svg {
            width: 18px;
            height: 18px;
            color: #ffd700;
        }

        .staff-id-badge span {
            color: #ffffff;
            font-size: 0.9rem;
            font-weight: 600;
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

        .alert-error svg {
            width: 22px;
            height: 22px;
            color: #dc2626;
            flex-shrink: 0;
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

        .alert-error li:last-child {
            margin-bottom: 0;
        }

        .form-section {
            margin-bottom: 28px;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #1e3a5f;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 18px;
            padding-bottom: 12px;
            border-bottom: 2px solid #e2e8f0;
        }

        .section-title svg {
            width: 18px;
            height: 18px;
            color: #3a7ca5;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
        }

        .form-row.single {
            grid-template-columns: 1fr;
        }

        .form-group {
            position: relative;
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

        .input-wrapper svg {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            color: #94a3b8;
            pointer-events: none;
            transition: color 0.3s ease;
        }

        .form-group input {
            width: 100%;
            padding: 14px 16px 14px 46px;
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

        .password-note {
            font-size: 0.8rem;
            color: #64748b;
            margin-top: 6px;
            font-style: italic;
        }

        .status-toggle-group {
            display: flex;
            background: #f1f5f9;
            border-radius: 12px;
            padding: 4px;
            gap: 4px;
        }

        .status-option {
            flex: 1;
            position: relative;
        }

        .status-option input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .status-option label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 16px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            color: #64748b;
            transition: all 0.3s ease;
            margin-bottom: 0;
        }

        .status-option input:checked + label {
            background: #ffffff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .status-option input[value="active"]:checked + label {
            color: #16a34a;
        }

        .status-option input[value="inactive"]:checked + label {
            color: #dc2626;
        }

        .status-option label svg {
            width: 18px;
            height: 18px;
        }

        .form-actions {
            display: flex;
            gap: 14px;
            margin-top: 32px;
            padding-top: 28px;
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

        .btn svg {
            width: 20px;
            height: 20px;
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

        .btn-save:active {
            transform: translateY(0);
        }

        @media (max-width: 600px) {
            .card-header {
                padding: 28px 24px;
            }

            .card-body {
                padding: 28px 24px 32px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column-reverse;
            }

            .card-header h1 {
                font-size: 1.4rem;
            }
        }
    </style>
</head>
<body>

<div class="edit-staff-container">
    <a href="dashboard.php#staff" class="back-link">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Back to Manage Staff
    </a>

    <div class="staff-card">
        <div class="card-header">
            <div class="header-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
            </div>
            <h1>Edit Staff Account</h1>
            <p>Update staff member information</p>
            
            <div class="staff-id-badge">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/>
                </svg>
                <span>Staff ID: #<?php echo $staffId; ?></span>
            </div>
        </div>

        <div class="card-body">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <ul>
                        <?php foreach ($errors as $err): ?>
                            <li><?php echo htmlspecialchars($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" id="editStaffForm" novalidate autocomplete="off">
                <input type="hidden" name="admin_id" value="<?php echo $staffId; ?>">
                
                <!-- Personal Information -->
                <div class="form-section">
                    <div class="section-title">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Personal Information
                    </div>
                    
                    <div class="form-row single">
                        <div class="form-group">
                            <label>Full Name <span class="required">*</span></label>
                            <div class="input-wrapper">
                                <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($staff['full_name'] ?? ''); ?>" required placeholder="Enter staff member's full name">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="form-row" style="margin-top: 18px;">
                        <div class="form-group">
                            <label>Email Address <span class="required">*</span></label>
                            <div class="input-wrapper">
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($staff['email'] ?? ''); ?>" required placeholder="staff@example.com">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Contact Number</label>
                            <div class="input-wrapper">
                                <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($staff['phone'] ?? ''); ?>" placeholder="09171234567">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Account Credentials -->
                <div class="form-section">
                    <div class="section-title">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                        </svg>
                        Account Credentials
                    </div>
                    
                    <div class="form-row single">
                        <div class="form-group">
                            <label>Username <span class="required">*</span></label>
                            <div class="input-wrapper">
                                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($staff['username'] ?? ''); ?>" required placeholder="Choose a unique username">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="form-row single" style="margin-top: 18px;">
                        <div class="form-group">
                            <label>New Password</label>
                            <div class="input-wrapper">
                                <input type="password" id="new_password" name="new_password" placeholder="Leave empty to keep current password">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <p class="password-note">Leave blank to keep the current password. Minimum 6 characters if changing.</p>
                        </div>
                    </div>
                </div>

                <!-- Account Status -->
                <div class="form-section">
                    <div class="section-title">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Account Status
                    </div>
                    
                    <div class="status-toggle-group">
                        <div class="status-option">
                            <input type="radio" id="status_active" name="status" value="active" <?php echo ($staff['is_active'] == 1) ? 'checked' : ''; ?>>
                            <label for="status_active">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Active
                            </label>
                        </div>
                        <div class="status-option">
                            <input type="radio" id="status_inactive" name="status" value="inactive" <?php echo ($staff['is_active'] == 0) ? 'checked' : ''; ?>>
                            <label for="status_inactive">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                </svg>
                                Inactive
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="button" class="btn btn-cancel" onclick="window.location.href='dashboard.php#staff'">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-save">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Form validation
document.getElementById('editStaffForm').addEventListener('submit', function(e) {
    const fullName = document.getElementById('full_name').value.trim();
    const username = document.getElementById('username').value.trim();
    const email = document.getElementById('email').value.trim();
    const newPassword = document.getElementById('new_password').value;
    
    let errors = [];
    
    if (fullName === '') {
        errors.push('Full Name is required.');
    }
    
    if (username === '') {
        errors.push('Username is required.');
    }
    
    if (email === '' || !email.includes('@')) {
        errors.push('A valid Email Address is required.');
    }
    
    if (newPassword !== '' && newPassword.length < 6) {
        errors.push('Password must be at least 6 characters.');
    }
    
    if (errors.length > 0) {
        alert(errors.join('\n'));
        e.preventDefault();
    }
});

// Auto-format phone number
document.getElementById('phone').addEventListener('input', function() {
    let value = this.value.replace(/\D/g, '');
    if (value.length > 11) value = value.substring(0, 11);
    this.value = value;
});
</script>

</body>
</html>

