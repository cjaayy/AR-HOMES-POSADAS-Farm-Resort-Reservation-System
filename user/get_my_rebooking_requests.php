<?php
/**
 * User: Get My Rebooking Requests
 * Returns all rebooking requests for the logged-in user
 */

session_start();
header('Content-Type: application/json');

require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    $user_id = $_SESSION['user_id'];
    $status_filter = $_GET['status'] ?? 'all'; // all, pending, approved, rejected
    
    // Build query to get rebooking requests
    // Check if rejection columns exist by trying a simple query first
    try {
        $testStmt = $pdo->query("SHOW COLUMNS FROM reservations LIKE 'rebooking_rejected'");
        $hasRejectionColumns = $testStmt->rowCount() > 0;
    } catch (Exception $e) {
        $hasRejectionColumns = false;
    }
    
    // Check if original date columns exist
    try {
        $testStmt = $pdo->query("SHOW COLUMNS FROM reservations LIKE 'rebooking_original_date'");
        $hasOriginalDateColumns = $testStmt->rowCount() > 0;
    } catch (Exception $e) {
        $hasOriginalDateColumns = false;
    }
    
    $sql = "SELECT 
                reservation_id,
                guest_name,
                booking_type,
                package_type,
                check_in_date,
                check_out_date,
                check_in_time,
                check_out_time,
                total_amount,
                downpayment_amount,
                status,
                rebooking_requested,
                rebooking_new_date,
                rebooking_reason,
                rebooking_approved,
                rebooking_approved_at,
                admin_notes";
    
    if ($hasRejectionColumns) {
        $sql .= ",
                rebooking_rejected,
                rebooking_rejected_at,
                rebooking_rejection_reason";
    }
    
    if ($hasOriginalDateColumns) {
        $sql .= ",
                rebooking_original_date,
                rebooking_original_time";
    }
    
    $sql .= ",
                created_at as rebooking_requested_at,
                updated_at
            FROM reservations 
            WHERE user_id = :user_id 
            AND (
                rebooking_requested = 1 
                OR rebooking_approved = 1";
    
    if ($hasRejectionColumns) {
        $sql .= " OR rebooking_rejected = 1";
    } else {
        $sql .= " OR (rebooking_requested = 0 AND rebooking_approved = 0 AND admin_notes LIKE '%Rebooking request rejected%')";
    }
    
    $sql .= ")";
    
    // Apply status filter
    if ($status_filter === 'pending') {
        if ($hasRejectionColumns) {
            $sql .= " AND rebooking_requested = 1 AND (rebooking_approved = 0 OR rebooking_approved IS NULL) AND (rebooking_rejected = 0 OR rebooking_rejected IS NULL)";
        } else {
            $sql .= " AND rebooking_requested = 1 AND (rebooking_approved = 0 OR rebooking_approved IS NULL)";
        }
    } elseif ($status_filter === 'approved') {
        $sql .= " AND rebooking_approved = 1";
    } elseif ($status_filter === 'rejected') {
        if ($hasRejectionColumns) {
            $sql .= " AND rebooking_rejected = 1";
        } else {
            // If rejection columns don't exist, check admin_notes for rejection
            $sql .= " AND rebooking_requested = 0 AND rebooking_approved = 0 AND admin_notes LIKE '%Rebooking request rejected%'";
        }
    }
    // 'all' gets all rebooking requests
    
    $sql .= " ORDER BY updated_at DESC, created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $user_id]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Log first request to see what we're getting
    if (!empty($requests)) {
        error_log("First rebooking request data: " . json_encode($requests[0]));
    }
    
    // Get counts for stats
    $countSql = "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN rebooking_requested = 1 AND (rebooking_approved = 0 OR rebooking_approved IS NULL)";
    if ($hasRejectionColumns) {
        $countSql .= " AND (rebooking_rejected = 0 OR rebooking_rejected IS NULL)";
    }
    $countSql .= " THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN rebooking_approved = 1 THEN 1 ELSE 0 END) as approved";
    if ($hasRejectionColumns) {
        $countSql .= ",
            SUM(CASE WHEN rebooking_rejected = 1 THEN 1 ELSE 0 END) as rejected";
    } else {
        $countSql .= ",
            SUM(CASE WHEN rebooking_requested = 0 AND rebooking_approved = 0 AND admin_notes LIKE '%Rebooking request rejected%' THEN 1 ELSE 0 END) as rejected";
    }
    $countSql .= "
        FROM reservations 
        WHERE user_id = :user_id 
        AND (
            rebooking_requested = 1 
            OR rebooking_approved = 1";
    if ($hasRejectionColumns) {
        $countSql .= " OR rebooking_rejected = 1";
    } else {
        $countSql .= " OR (rebooking_requested = 0 AND rebooking_approved = 0 AND admin_notes LIKE '%Rebooking request rejected%')";
    }
    $countSql .= "
        )
    ";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute([':user_id' => $user_id]);
    $counts = $countStmt->fetch(PDO::FETCH_ASSOC);
    
    // Format requests for display
    foreach ($requests as &$request) {
        // Debug: Log the raw data for first request
        if (!isset($debug_logged)) {
            error_log("Processing rebooking request - check_in_date: " . ($request['check_in_date'] ?? 'NULL') . ", rebooking_approved: " . ($request['rebooking_approved'] ?? 'NULL'));
            $debug_logged = true;
        }
        // Determine status
        if ($request['rebooking_approved'] == 1) {
            $request['rebooking_status'] = 'approved';
            $request['rebooking_status_label'] = 'Approved';
        } elseif ($hasRejectionColumns && isset($request['rebooking_rejected']) && $request['rebooking_rejected'] == 1) {
            $request['rebooking_status'] = 'rejected';
            $request['rebooking_status_label'] = 'Rejected';
        } elseif (!$hasRejectionColumns && $request['rebooking_requested'] == 0 && $request['rebooking_approved'] == 0) {
            // Check admin_notes for rejection if columns don't exist
            $admin_notes = $request['admin_notes'] ?? '';
            if (stripos($admin_notes, 'Rebooking request rejected') !== false) {
                $request['rebooking_status'] = 'rejected';
                $request['rebooking_status_label'] = 'Rejected';
            } else {
                $request['rebooking_status'] = 'pending';
                $request['rebooking_status_label'] = 'Pending Approval';
            }
        } else {
            $request['rebooking_status'] = 'pending';
            $request['rebooking_status_label'] = 'Pending Approval';
        }
        
        // Determine original date - use stored original date if available, otherwise use current check_in_date for pending requests
        // For pending requests, check_in_date is still the original
        // For approved requests, we need to get it from rebooking_original_date or admin_notes
        
        // Check if approved (handle both string and integer, null and 0)
        // Convert to int for consistent comparison
        $rebooking_approved = isset($request['rebooking_approved']) ? (int)$request['rebooking_approved'] : 0;
        $is_approved = $rebooking_approved === 1;
        
        // For pending requests, check_in_date IS the original date (it hasn't been changed yet)
        // For approved requests, check_in_date has been updated to the new date, so we need the stored original
        if (!$is_approved) {
            // Pending request - check_in_date is still the original date
            // ALWAYS use check_in_date for pending requests
            $check_in = trim($request['check_in_date'] ?? '');
            $request['original_check_in_date'] = !empty($check_in) ? $check_in : null;
            $request['original_check_in_time'] = !empty($request['check_in_time']) ? trim($request['check_in_time']) : null;
            
            // Debug log for pending requests
            if (empty($request['original_check_in_date'])) {
                error_log("WARNING: Pending rebooking request #{$request['reservation_id']} has empty check_in_date! Raw value: '" . ($request['check_in_date'] ?? 'NULL') . "'");
            }
        } elseif ($hasOriginalDateColumns && isset($request['rebooking_original_date']) && $request['rebooking_original_date']) {
            // Use stored original date from column
            $request['original_check_in_date'] = $request['rebooking_original_date'];
            $request['original_check_in_time'] = $request['rebooking_original_time'] ?? $request['check_in_time'];
        } else {
            // For approved requests, we need to extract from admin_notes or use a workaround
            // If approved but no original date column, try to extract from admin_notes
            $admin_notes = $request['admin_notes'] ?? '';
            
            // Try multiple patterns to extract original date from admin_notes
            $original_date_found = false;
            
            // Pattern 1: [Original Date: YYYY-MM-DD HH:MM:SS] or [Original Date: YYYY-MM-DD]
            // Make regex more flexible to handle variations
            if (preg_match('/\[Original Date:\s*([0-9]{4}-[0-9]{2}-[0-9]{2})(?:\s+([0-9]{2}:[0-9]{2}(?::[0-9]{2})?))?\]/i', $admin_notes, $matches)) {
                $request['original_check_in_date'] = $matches[1];
                $request['original_check_in_time'] = isset($matches[2]) && $matches[2] ? $matches[2] : ($request['check_in_time'] ?? null);
                $original_date_found = true;
            }
            // Pattern 2: Original Date: YYYY-MM-DD (without brackets, case insensitive)
            elseif (preg_match('/Original\s+Date:\s*([0-9]{4}-[0-9]{2}-[0-9]{2})/i', $admin_notes, $matches)) {
                $request['original_check_in_date'] = $matches[1];
                $request['original_check_in_time'] = $request['check_in_time'] ?? null;
                $original_date_found = true;
            }
            // Pattern 3: Try to find any date pattern in admin_notes that might be the original date
            // This is a fallback - look for dates before "Rebooking approved" or similar text
            elseif (preg_match('/([0-9]{4}-[0-9]{2}-[0-9]{2})/', $admin_notes, $matches)) {
                // This is less reliable, but better than nothing
                // Only use if it's a reasonable date (not too far in past/future)
                $potential_date = $matches[1];
                $date_obj = DateTime::createFromFormat('Y-m-d', $potential_date);
                if ($date_obj && $date_obj->format('Y-m-d') === $potential_date) {
                    // Check if date is reasonable (within last 2 years and not in far future)
                    $now = new DateTime();
                    $two_years_ago = clone $now;
                    $two_years_ago->modify('-2 years');
                    $one_year_future = clone $now;
                    $one_year_future->modify('+1 year');
                    
                    if ($date_obj >= $two_years_ago && $date_obj <= $one_year_future) {
                        $request['original_check_in_date'] = $potential_date;
                        $request['original_check_in_time'] = $request['check_in_time'] ?? null;
                        $original_date_found = true;
                    }
                }
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
                error_log("ERROR: Pending request #{$request['reservation_id']} - no date found! check_in_date: '" . ($request['check_in_date'] ?? 'NULL') . "', original_check_in_date: '" . ($request['original_check_in_date'] ?? 'NULL') . "'");
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
                    error_log("Date formatting failed for: $date_to_format - " . $e->getMessage());
                    $request['original_check_in_date_formatted'] = $date_to_format ?: 'N/A';
                }
            }
        } else {
            // No date available
            $request['original_check_in_date_formatted'] = 'N/A';
        }
        
        $request['check_in_date_formatted'] = date('M d, Y', strtotime($request['check_in_date']));
        
        // For approved requests, rebooking_new_date might be NULL (already applied), so use check_in_date
        // For pending requests, rebooking_new_date is the requested date
        if ($request['rebooking_approved'] == 1) {
            // For approved, the new date is now in check_in_date, and rebooking_new_date might be cleared
            $request['rebooking_new_date_formatted'] = $request['rebooking_new_date'] ? date('M d, Y', strtotime($request['rebooking_new_date'])) : date('M d, Y', strtotime($request['check_in_date']));
        } else {
            $request['rebooking_new_date_formatted'] = $request['rebooking_new_date'] ? date('M d, Y', strtotime($request['rebooking_new_date'])) : null;
        }
        
        // Format times
        if ($request['original_check_in_time']) {
            try {
                $request['original_check_in_time_formatted'] = date('g:i A', strtotime($request['original_check_in_time']));
            } catch (Exception $e) {
                $request['original_check_in_time_formatted'] = $request['original_check_in_time'];
            }
        } else {
            // For pending requests, use check_in_time
            if ($request['rebooking_approved'] != 1) {
                $request['original_check_in_time_formatted'] = $request['check_in_time'] ? date('g:i A', strtotime($request['check_in_time'])) : '';
            } else {
                $request['original_check_in_time_formatted'] = '';
            }
        }
        $request['check_in_time_formatted'] = $request['check_in_time'] ? date('g:i A', strtotime($request['check_in_time'])) : 'N/A';
    }
    
    // Debug: Include raw check_in_date in response for debugging
    if (!empty($requests)) {
        foreach ($requests as &$req) {
            $req['_debug_check_in_date'] = $req['check_in_date'] ?? 'NULL';
            $req['_debug_original_check_in_date'] = $req['original_check_in_date'] ?? 'NULL';
            $req['_debug_rebooking_approved'] = $req['rebooking_approved'] ?? 'NULL';
        }
        unset($req);
    }
    
    echo json_encode([
        'success' => true,
        'requests' => $requests,
        'counts' => [
            'total' => (int)($counts['total'] ?? 0),
            'pending' => (int)($counts['pending'] ?? 0),
            'approved' => (int)($counts['approved'] ?? 0),
            'rejected' => (int)($counts['rejected'] ?? 0)
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>


