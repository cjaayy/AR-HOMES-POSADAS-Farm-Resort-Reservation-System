<?php
/**
 * Staff Task Actions API - Create, Update, Delete tasks
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
    
    // Get staff ID and name from appropriate session
    $staffId = $isStaffSession ? ($_SESSION['staff_id'] ?? 0) : ($_SESSION['admin_id'] ?? 0);
    $staffName = $isStaffSession ? ($_SESSION['staff_full_name'] ?? 'Staff Member') : ($_SESSION['admin_full_name'] ?? 'Staff Member');
    $action = $_POST['action'] ?? '';
    
    // Check if table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'staff_tasks'");
    if ($tableCheck->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Tasks table not initialized. Please run init_staff_tables.php']);
        exit;
    }
    
    switch ($action) {
        case 'create':
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $priority = $_POST['priority'] ?? 'medium';
            $status = $_POST['status'] ?? 'todo';
            $dueDate = $_POST['dueDate'] ?? null;
            
            if (empty($title)) {
                echo json_encode(['success' => false, 'message' => 'Title is required']);
                exit;
            }
            
            $sql = "INSERT INTO staff_tasks (staff_id, staff_name, title, description, priority, status, due_date) 
                    VALUES (:staff_id, :staff_name, :title, :description, :priority, :status, :due_date)";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':staff_id' => $staffId,
                ':staff_name' => $staffName,
                ':title' => $title,
                ':description' => $description,
                ':priority' => $priority,
                ':status' => $status,
                ':due_date' => $dueDate
            ]);
            
            $taskId = $conn->lastInsertId();
            
            // Log activity
            logActivity($conn, $staffId, $staffName, 'task_created', "Created task: {$title}", $taskId, 'task');
            
            echo json_encode([
                'success' => true,
                'message' => 'Task created successfully',
                'task_id' => $taskId
            ]);
            break;
            
        case 'update':
            $taskId = (int)($_POST['taskId'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $priority = $_POST['priority'] ?? 'medium';
            $status = $_POST['status'] ?? 'todo';
            $dueDate = $_POST['dueDate'] ?? null;
            
            if ($taskId <= 0 || empty($title)) {
                echo json_encode(['success' => false, 'message' => 'Invalid task data']);
                exit;
            }
            
            // Check ownership
            $check = $conn->prepare("SELECT task_id FROM staff_tasks WHERE task_id = :task_id AND staff_id = :staff_id");
            $check->execute([':task_id' => $taskId, ':staff_id' => $staffId]);
            if ($check->rowCount() === 0) {
                echo json_encode(['success' => false, 'message' => 'Task not found or access denied']);
                exit;
            }
            
            $completedAt = ($status === 'completed') ? date('Y-m-d H:i:s') : null;
            
            $sql = "UPDATE staff_tasks 
                    SET title = :title, description = :description, priority = :priority, 
                        status = :status, due_date = :due_date, completed_at = :completed_at
                    WHERE task_id = :task_id AND staff_id = :staff_id";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':title' => $title,
                ':description' => $description,
                ':priority' => $priority,
                ':status' => $status,
                ':due_date' => $dueDate,
                ':completed_at' => $completedAt,
                ':task_id' => $taskId,
                ':staff_id' => $staffId
            ]);
            
            // Log activity
            logActivity($conn, $staffId, $staffName, 'task_updated', "Updated task: {$title}", $taskId, 'task');
            
            echo json_encode(['success' => true, 'message' => 'Task updated successfully']);
            break;
            
        case 'delete':
            $taskId = (int)($_POST['taskId'] ?? 0);
            
            if ($taskId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid task ID']);
                exit;
            }
            
            // Check ownership
            $check = $conn->prepare("SELECT title FROM staff_tasks WHERE task_id = :task_id AND staff_id = :staff_id");
            $check->execute([':task_id' => $taskId, ':staff_id' => $staffId]);
            $task = $check->fetch(PDO::FETCH_ASSOC);
            
            if (!$task) {
                echo json_encode(['success' => false, 'message' => 'Task not found or access denied']);
                exit;
            }
            
            $sql = "DELETE FROM staff_tasks WHERE task_id = :task_id AND staff_id = :staff_id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':task_id' => $taskId, ':staff_id' => $staffId]);
            
            // Log activity
            logActivity($conn, $staffId, $staffName, 'task_deleted', "Deleted task: {$task['title']}", $taskId, 'task');
            
            echo json_encode(['success' => true, 'message' => 'Task deleted successfully']);
            break;
            
        case 'complete':
            $taskId = (int)($_POST['taskId'] ?? 0);
            
            if ($taskId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid task ID']);
                exit;
            }
            
            // Check ownership
            $check = $conn->prepare("SELECT title FROM staff_tasks WHERE task_id = :task_id AND staff_id = :staff_id");
            $check->execute([':task_id' => $taskId, ':staff_id' => $staffId]);
            $task = $check->fetch(PDO::FETCH_ASSOC);
            
            if (!$task) {
                echo json_encode(['success' => false, 'message' => 'Task not found or access denied']);
                exit;
            }
            
            $sql = "UPDATE staff_tasks SET status = 'completed', completed_at = NOW() 
                    WHERE task_id = :task_id AND staff_id = :staff_id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':task_id' => $taskId, ':staff_id' => $staffId]);
            
            // Log activity
            logActivity($conn, $staffId, $staffName, 'task_completed', "Completed task: {$task['title']}", $taskId, 'task');
            
            echo json_encode(['success' => true, 'message' => 'Task marked as completed']);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

function logActivity($conn, $userId, $userName, $actionType, $description, $relatedId = null, $relatedType = null) {
    try {
        $sql = "INSERT INTO activity_log (user_id, user_name, user_role, action_type, action_description, related_id, related_type, ip_address) 
                VALUES (:user_id, :user_name, 'staff', :action_type, :description, :related_id, :related_type, :ip)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':user_name' => $userName,
            ':action_type' => $actionType,
            ':description' => $description,
            ':related_id' => $relatedId,
            ':related_type' => $relatedType,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    } catch (PDOException $e) {
        // Log error but don't fail the main operation
        error_log("Failed to log activity: " . $e->getMessage());
    }
}
?>

