<?php
/**
 * Staff management actions: toggle_status, delete_staff
 * Admin-only operations for managing staff members
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

    $action = $_POST['action'] ?? null;

    if ($action === 'toggle_status') {
        $admin_id = (int)($_POST['admin_id'] ?? 0);
        $is_active = (int)($_POST['is_active'] ?? 0);

        if ($admin_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid staff ID']);
            exit;
        }

        // Prevent admin from deactivating themselves
        if ($admin_id == $_SESSION['admin_id']) {
            echo json_encode(['success' => false, 'message' => 'You cannot deactivate your own account']);
            exit;
        }

        // Check if staff member exists
        $checkStmt = $conn->prepare("SELECT admin_id, full_name FROM admin_users WHERE admin_id = :id AND role = 'staff'");
        $checkStmt->bindParam(':id', $admin_id, PDO::PARAM_INT);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() === 0) {
            echo json_encode(['success' => false, 'message' => 'Staff member not found']);
            exit;
        }

        // Update status
        $stmt = $conn->prepare("UPDATE admin_users SET is_active = :status WHERE admin_id = :id");
        $stmt->bindParam(':status', $is_active, PDO::PARAM_INT);
        $stmt->bindParam(':id', $admin_id, PDO::PARAM_INT);
        $stmt->execute();

        echo json_encode([
            'success' => true, 
            'message' => 'Staff status updated successfully'
        ]);
        exit;

    } elseif ($action === 'delete_staff') {
        $admin_id = (int)($_POST['admin_id'] ?? 0);

        if ($admin_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid staff ID']);
            exit;
        }

        // Prevent admin from deleting themselves
        if ($admin_id == $_SESSION['admin_id']) {
            echo json_encode(['success' => false, 'message' => 'You cannot delete your own account']);
            exit;
        }

        // Check if staff member exists
        $checkStmt = $conn->prepare("SELECT admin_id, full_name FROM admin_users WHERE admin_id = :id AND role = 'staff'");
        $checkStmt->bindParam(':id', $admin_id, PDO::PARAM_INT);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() === 0) {
            echo json_encode(['success' => false, 'message' => 'Staff member not found']);
            exit;
        }

        // Delete staff member
        $stmt = $conn->prepare("DELETE FROM admin_users WHERE admin_id = :id AND role = 'staff'");
        $stmt->bindParam(':id', $admin_id, PDO::PARAM_INT);
        $stmt->execute();

        echo json_encode([
            'success' => true, 
            'message' => 'Staff member deleted successfully'
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>

