<?php
/**
 * Admin: Get Rebooking Requests
 * Returns all reservations with pending rebooking requests
 */

// Start output buffering to catch any unexpected output
ob_start();

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();

// Set JSON header early
header('Content-Type: application/json');

// Check for admin session
$isAdminLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$isStaffLoggedIn = isset($_SESSION['staff_logged_in']) && $_SESSION['staff_logged_in'] === true;

if (!$isAdminLoggedIn && !$isStaffLoggedIn) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Try to require connection file
if (!file_exists('../config/connection.php')) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database configuration file not found'
    ]);
    exit;
}

try {
    require_once '../config/connection.php';
    
    // Check if Database class exists
    if (!class_exists('Database')) {
        throw new Exception('Database class not found in connection.php');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load database connection: ' . $e->getMessage()
    ]);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    if (!$conn) {
        throw new Exception('Database connection is null');
    }

    $status = $_GET['status'] ?? 'pending'; // pending, approved, all
    
    // Log the request (for debugging)
    error_log("Rebooking requests API called with status: $status");
    
    // Check if original date columns exist
    try {
        $testStmt = $conn->query("SHOW COLUMNS FROM reservations LIKE 'rebooking_original_date'");
        $hasOriginalDateColumns = $testStmt->rowCount() > 0;
    } catch (Exception $e) {
        $hasOriginalDateColumns = false;
    }
    
    // Build query - include original date columns if they exist
    $sql = "SELECT 
                r.reservation_id,
                r.user_id,
                r.guest_name,
                r.guest_email,
                r.guest_phone,
                r.room,
                r.booking_type,
                r.package_type,
                r.check_in_date,
                r.check_out_date,
                r.check_in_time,
                r.check_out_time,
                r.total_amount,
                r.downpayment_amount,
                r.downpayment_verified,
                r.status,
                r.rebooking_requested,
                r.rebooking_new_date,
                r.rebooking_reason,
                r.created_at as rebooking_requested_at,
                r.rebooking_approved,
                r.rebooking_approved_by,
                r.rebooking_approved_at,
                r.admin_notes";
    
    if ($hasOriginalDateColumns) {
        $sql .= ",
                r.rebooking_original_date,
                r.rebooking_original_time";
    }
    
    $sql .= ",
                '' as user_full_name,
                '' as user_email_verified
            FROM reservations r
            WHERE r.rebooking_requested = 1";
    
    if ($status === 'pending') {
        $sql .= " AND (r.rebooking_approved = 0 OR r.rebooking_approved IS NULL)";
    } elseif ($status === 'approved') {
        $sql .= " AND r.rebooking_approved = 1";
    }
    // 'all' gets all rebooking requests
    
    // Order by created_at (which always exists)
    $sql .= " ORDER BY r.created_at DESC";
    
    // Execute query with error handling
    try {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Failed to prepare SQL statement');
        }
        
        $stmt->execute();
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Ensure requests is an array
        if (!is_array($requests)) {
            $requests = [];
        }
        
        // Process each request to determine original date
        foreach ($requests as &$request) {
            // Check if approved (handle both string and integer, null and 0)
            // Convert to int for consistent comparison
            $rebooking_approved = isset($request['rebooking_approved']) ? (int)$request['rebooking_approved'] : 0;
            $is_approved = $rebooking_approved === 1;
            
            // For ALL requests, start with check_in_date as the original (it's the original until approval changes it)
            $original_date = $request['check_in_date'] ?? null;
            $original_time = $request['check_in_time'] ?? null;
            
            // Determine original date - prioritize pending requests first
            if (!$is_approved) {
                // Pending request - check_in_date is still the original date
                // Trim and validate
                $check_in = trim($original_date ?? '');
                $request['original_check_in_date'] = !empty($check_in) ? $check_in : null;
                $request['original_check_in_time'] = !empty($original_time) ? trim($original_time) : null;
                
                // Debug log for pending requests
                if (empty($request['original_check_in_date'])) {
                    error_log("WARNING: Admin - Pending rebooking request #{$request['reservation_id']} has empty check_in_date! Raw value: '" . ($request['check_in_date'] ?? 'NULL') . "'");
                }
            } elseif ($hasOriginalDateColumns && isset($request['rebooking_original_date']) && $request['rebooking_original_date']) {
                // Use stored original date from column
                $request['original_check_in_date'] = $request['rebooking_original_date'];
                $request['original_check_in_time'] = $request['rebooking_original_time'] ?? $request['check_in_time'];
            } else {
                // For approved requests, try to extract from admin_notes
                $admin_notes = $request['admin_notes'] ?? '';
                $original_date_found = false;
                
                // Pattern 1: [Original Date: YYYY-MM-DD HH:MM:SS]
                if (preg_match('/\[Original Date:\s*([0-9]{4}-[0-9]{2}-[0-9]{2})(?:\s+([0-9]{2}:[0-9]{2}(?::[0-9]{2})?))?\]/i', $admin_notes, $matches)) {
                    $request['original_check_in_date'] = $matches[1];
                    $request['original_check_in_time'] = isset($matches[2]) && $matches[2] ? $matches[2] : ($request['check_in_time'] ?? null);
                    $original_date_found = true;
                }
                // Pattern 2: Original Date: YYYY-MM-DD (without brackets)
                elseif (preg_match('/Original\s+Date:\s*([0-9]{4}-[0-9]{2}-[0-9]{2})/i', $admin_notes, $matches)) {
                    $request['original_check_in_date'] = $matches[1];
                    $request['original_check_in_time'] = $request['check_in_time'] ?? null;
                    $original_date_found = true;
                }
                
                if (!$original_date_found) {
                    // Can't determine original date - for approved requests, check_in_date is now the NEW date
                    $request['original_check_in_date'] = null;
                    $request['original_check_in_time'] = null;
                }
            }
            
            // Format dates - SIMPLIFIED: For pending, always use check_in_date
            // Format the original date
            $date_to_format = null;
            
            if (!$is_approved) {
                // Pending request - use check_in_date directly (it IS the original)
                // Try multiple sources in order of preference, with trimming
                $date_to_format = null;
                if (!empty($request['original_check_in_date'])) {
                    $date_to_format = trim($request['original_check_in_date']);
                } elseif (!empty($request['check_in_date'])) {
                    $date_to_format = trim($request['check_in_date']);
                    // Also set original_check_in_date for consistency
                    $request['original_check_in_date'] = $date_to_format;
                }
                
                // If still empty, log it
                if (empty($date_to_format)) {
                    error_log("ERROR: Admin - Pending request #{$request['reservation_id']} - no date found! check_in_date: '" . ($request['check_in_date'] ?? 'NULL') . "', original_check_in_date: '" . ($request['original_check_in_date'] ?? 'NULL') . "'");
                }
            } else {
                // Approved request - use stored original_check_in_date
                $date_to_format = $request['original_check_in_date'] ?? null;
            }
            
            // Format the date
            if (!empty($date_to_format) && $date_to_format !== '0000-00-00') {
                try {
                    $date_obj = new DateTime($date_to_format);
                    $request['original_check_in_date_formatted'] = $date_obj->format('M d, Y');
                } catch (Exception $e) {
                    // If date parsing fails, try strtotime
                    $timestamp = strtotime($date_to_format);
                    if ($timestamp !== false && $timestamp > 0) {
                        $request['original_check_in_date_formatted'] = date('M d, Y', $timestamp);
                    } else {
                        error_log("Admin - Date formatting failed for: $date_to_format - " . $e->getMessage());
                        $request['original_check_in_date_formatted'] = $date_to_format ?: 'N/A';
                    }
                }
            } else {
                // No date available
                $request['original_check_in_date_formatted'] = 'N/A';
            }
            
            // Format check_in_date and rebooking_new_date
            $request['check_in_date_formatted'] = date('M d, Y', strtotime($request['check_in_date']));
            $request['rebooking_new_date_formatted'] = $request['rebooking_new_date'] ? date('M d, Y', strtotime($request['rebooking_new_date'])) : null;
        }
        unset($request); // Break reference
        
    } catch (PDOException $e) {
        error_log('Query execution error: ' . $e->getMessage());
        throw new Exception('Query failed: ' . $e->getMessage());
    }
    
    // Get counts for stats
    try {
        $countStmt = $conn->prepare("
            SELECT 
                COUNT(*) as total,
                COALESCE(SUM(CASE WHEN rebooking_approved = 0 OR rebooking_approved IS NULL THEN 1 ELSE 0 END), 0) as pending,
                COALESCE(SUM(CASE WHEN rebooking_approved = 1 THEN 1 ELSE 0 END), 0) as approved
            FROM reservations 
            WHERE rebooking_requested = 1
        ");
        
        if (!$countStmt) {
            throw new Exception('Failed to prepare count statement');
        }
        
        $countStmt->execute();
        $counts = $countStmt->fetch(PDO::FETCH_ASSOC);
        
        // Ensure counts are integers
        if (!$counts) {
            $counts = ['total' => 0, 'pending' => 0, 'approved' => 0];
        }
    } catch (PDOException $e) {
        error_log('Count query error: ' . $e->getMessage());
        // Use default counts if count query fails
        $counts = ['total' => count($requests), 'pending' => 0, 'approved' => 0];
    }
    
    // Clear any output buffer
    ob_clean();
    
    // Ensure we always return valid JSON
    $response = [
        'success' => true,
        'requests' => $requests,
        'counts' => [
            'total' => (int)($counts['total'] ?? 0),
            'pending' => (int)($counts['pending'] ?? 0),
            'approved' => (int)($counts['approved'] ?? 0)
        ]
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
    // Flush output to ensure it's sent
    if (ob_get_level() > 0) {
        ob_end_flush();
    }
    
} catch (PDOException $e) {
    ob_clean();
    http_response_code(500);
    // Log the error
    error_log('Rebooking Requests PDO Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'error_code' => $e->getCode()
    ]);
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    // Log the error
    error_log('Rebooking Requests Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
} catch (Error $e) {
    // Catch fatal errors
    ob_clean();
    http_response_code(500);
    error_log('Rebooking Requests Fatal Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Fatal error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
ob_end_flush();
?>


