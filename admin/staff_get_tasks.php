<?php
/**
 * Staff Tasks API - Get all tasks for the logged-in staff member
 */
session_start();
header('Content-Type: application/json');

// Accept both admin session (with staff role) OR staff-specific session
$isAdminAsStaff = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true && ($_SESSION['admin_role'] ?? '') === 'staff';
$isStaffSession = isset($_SESSION['staff_logged_in']) && $_SESSION['staff_logged_in'] === true;

if (!$isAdminAsStaff && !$isStaffSession) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../config/connection.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get staff ID from appropriate session
    $staffId = $isStaffSession ? ($_SESSION['staff_id'] ?? 0) : ($_SESSION['admin_id'] ?? 0);
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
    
    // Check if table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'staff_tasks'");
    if ($tableCheck->rowCount() === 0) {
        echo json_encode(['success' => true, 'tasks' => [], 'message' => 'Tasks table not initialized']);
        exit;
    }
    
    // Build query based on filter
    $sql = "SELECT * FROM staff_tasks WHERE staff_id = :staff_id";
    $params = [':staff_id' => $staffId];
    
    switch ($filter) {
        case 'todo':
            $sql .= " AND status = 'todo'";
            break;
        case 'in-progress':
            $sql .= " AND status = 'in-progress'";
            break;
        case 'completed':
            $sql .= " AND status = 'completed'";
            break;
        case 'high':
            $sql .= " AND priority = 'high'";
            break;
    }
    
    $sql .= " ORDER BY 
        CASE priority 
            WHEN 'high' THEN 1 
            WHEN 'medium' THEN 2 
            WHEN 'low' THEN 3 
        END,
        due_date ASC,
        created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format dates
    foreach ($tasks as &$task) {
        $task['task_id'] = (int)$task['task_id'];
        $task['staff_id'] = (int)$task['staff_id'];
    }
    
    echo json_encode([
        'success' => true,
        'tasks' => $tasks,
        'total' => count($tasks)
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>

