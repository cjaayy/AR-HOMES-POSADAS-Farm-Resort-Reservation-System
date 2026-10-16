<?php
/**
 * Return JSON list of staff members (admin_users with role = 'staff')
 */
session_start();
header('Content-Type: application/json');

// Simple auth check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

require_once '../config/connection.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Check whether the `position` column exists (table may be older schema)
    $colExists = false;
    try {
        $colCheck = $conn->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :db AND TABLE_NAME = 'admin_users' AND COLUMN_NAME = 'position'");
        $colCheck->bindValue(':db', DB_NAME);
        $colCheck->execute();
        $colExists = (int)$colCheck->fetchColumn() > 0;
    } catch (Exception $e) {
        // If INFORMATION_SCHEMA query fails for any reason, continue without position
        $colExists = false;
    }

    $positionSelect = $colExists ? 'position' : "NULL AS position";

    $sql = "SELECT admin_id, username, email, full_name, role, is_active, {$positionSelect}, created_at, last_login FROM admin_users WHERE role = 'staff' ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'staff' => $rows]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

?>

