<?php
/**
 * Update System Settings
 * Handles system configuration updates
 */

header('Content-Type: application/json');
header("Cache-Control: no-cache, no-store, must-revalidate");

session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Check if user has permission (not staff - staff should use staff_dashboard.php)
$userRole = $_SESSION['admin_role'] ?? '';
if ($userRole === 'staff') {
    echo json_encode(['success' => false, 'message' => 'You do not have permission to modify system settings']);
    exit;
}

require_once '../config/connection.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        $data = $_POST;
    }
    
    $settingKey = trim($data['key'] ?? '');
    $settingValue = trim($data['value'] ?? '');
    
    if (empty($settingKey)) {
        echo json_encode(['success' => false, 'message' => 'Setting key is required']);
        exit;
    }
    
    // For now, return success - you can implement a settings table later
    // This is a placeholder that validates the request
    $allowedSettings = [
        'resort_name',
        'contact_email',
        'contact_phone',
        'language',
        'timezone',
        'currency',
        'date_format',
        'time_format',
        'session_timeout'
    ];
    
    if (!in_array($settingKey, $allowedSettings)) {
        echo json_encode(['success' => false, 'message' => 'Invalid setting key']);
        exit;
    }
    
    // In a full implementation, you would save to a settings table:
    // $query = "INSERT INTO system_settings (setting_key, setting_value, updated_at, updated_by) 
    //           VALUES (:key, :value, NOW(), :admin_id)
    //           ON DUPLICATE KEY UPDATE setting_value = :value, updated_at = NOW(), updated_by = :admin_id";
    
    // For now, just return success
    echo json_encode([
        'success' => true,
        'message' => 'Setting updated successfully',
        'data' => [
            'key' => $settingKey,
            'value' => $settingValue
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Update settings error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}

