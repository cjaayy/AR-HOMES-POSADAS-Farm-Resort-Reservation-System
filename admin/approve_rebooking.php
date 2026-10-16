<?php
/**
 * Admin: Approve Rebooking
 * Staff approves or rejects rebooking requests
 */

session_start();
header('Content-Type: application/json');

require_once '../config/connection.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $reservation_id = $_POST['reservation_id'] ?? null;
    $action = $_POST['action'] ?? 'approve';
    
    if (!$reservation_id) {
        throw new Exception('Reservation ID is required');
    }
    
    $admin_id = $_SESSION['admin_id'] ?? 0;
    
    if ($action === 'approve') {
        // Get rebooking details
        $stmt = $conn->prepare("SELECT rebooking_new_date, check_in_date, check_in_time, booking_type FROM reservations WHERE reservation_id = :id");
        $stmt->execute([':id' => $reservation_id]);
        $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$reservation) {
            throw new Exception('Reservation not found');
        }
        
        // Store original date before updating
        $original_check_in_date = $reservation['check_in_date'];
        $original_check_in_time = $reservation['check_in_time'];
        
        // Check if new date is still available
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count FROM reservations 
            WHERE check_in_date = :date 
            AND booking_type = :type
            AND status IN ('confirmed', 'checked_in')
            AND date_locked = 1
            AND reservation_id != :id
        ");
        $stmt->execute([
            ':date' => $reservation['rebooking_new_date'],
            ':type' => $reservation['booking_type'],
            ':id' => $reservation_id
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result['count'] > 0) {
            throw new Exception('New date is no longer available');
        }
        
        // Calculate check_out_date based on booking type
        $check_out_date = $reservation['rebooking_new_date'];
        if ($reservation['booking_type'] === 'nighttime' || $reservation['booking_type'] === '22hours') {
            $check_out_date = date('Y-m-d', strtotime($reservation['rebooking_new_date'] . ' +1 day'));
        }
        
        // Approve rebooking: Update dates, keep payment status, lock new date
        // Store original date in rebooking_original_date field AND admin_notes (as backup)
        // Old date automatically becomes available for other users
        
        // Format time properly for admin_notes
        $time_str = '';
        if ($original_check_in_time) {
            // Ensure time is in HH:MM:SS format
            if (strlen($original_check_in_time) == 5) {
                $time_str = $original_check_in_time . ':00'; // Add seconds if missing
            } else {
                $time_str = $original_check_in_time;
            }
        }
        $original_date_note = "\n[Original Date: " . $original_check_in_date . ($time_str ? " " . $time_str : "") . "]";
        
        // Always store original date in admin_notes as backup, even if columns exist
        try {
            // First, try with original date columns
            $stmt = $conn->prepare("
                UPDATE reservations 
                SET check_in_date = rebooking_new_date,
                    check_out_date = :check_out_date,
                    rebooking_original_date = :original_date,
                    rebooking_original_time = :original_time,
                    rebooking_approved = 1,
                    rebooking_approved_by = :admin_id,
                    rebooking_approved_at = NOW(),
                    admin_notes = CONCAT(COALESCE(admin_notes, ''), :original_note),
                    date_locked = 1,
                    locked_until = DATE_ADD(rebooking_new_date, INTERVAL 1 DAY),
                    status = 'confirmed',
                    updated_at = NOW()
                WHERE reservation_id = :id
            ");
            
            $stmt->bindParam(':check_out_date', $check_out_date);
            $stmt->bindParam(':original_date', $original_check_in_date);
            $stmt->bindParam(':original_time', $original_check_in_time);
            $stmt->bindParam(':original_note', $original_date_note);
            $stmt->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);
            $stmt->bindParam(':id', $reservation_id);
            $stmt->execute();
        } catch (PDOException $e) {
            // If original date columns don't exist, store only in admin_notes
            if (strpos($e->getMessage(), 'Unknown column') !== false || strpos($e->getMessage(), 'rebooking_original') !== false) {
                $stmt = $conn->prepare("
                    UPDATE reservations 
                    SET check_in_date = rebooking_new_date,
                        check_out_date = :check_out_date,
                        rebooking_approved = 1,
                        rebooking_approved_by = :admin_id,
                        rebooking_approved_at = NOW(),
                        admin_notes = CONCAT(COALESCE(admin_notes, ''), :original_note),
                        date_locked = 1,
                        locked_until = DATE_ADD(rebooking_new_date, INTERVAL 1 DAY),
                        status = 'confirmed',
                        updated_at = NOW()
                    WHERE reservation_id = :id
                ");
                
                $stmt->bindParam(':check_out_date', $check_out_date);
                $stmt->bindParam(':original_note', $original_date_note);
                $stmt->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);
                $stmt->bindParam(':id', $reservation_id);
                $stmt->execute();
            } else {
                throw $e;
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Rebooking approved! Date updated successfully.',
            'new_date' => $reservation['rebooking_new_date']
        ]);
        
    } else {
        // Reject rebooking
        $rejection_reason = $_POST['reason'] ?? '';
        
        // Mark as rejected but keep the request data for user visibility
        // Try to update with rejection fields, fallback if columns don't exist
        try {
            $stmt = $conn->prepare("
                UPDATE reservations 
                SET rebooking_requested = 0,
                    rebooking_approved = 0,
                    rebooking_rejected = 1,
                    rebooking_rejected_at = NOW(),
                    rebooking_rejected_by = :admin_id,
                    rebooking_rejection_reason = :rejection_reason,
                    admin_notes = CONCAT(COALESCE(admin_notes, ''), '\n[', NOW(), '] Rebooking request rejected', CASE WHEN :rejection_reason != '' THEN CONCAT('. Reason: ', :rejection_reason) ELSE '' END),
                    updated_at = NOW()
                WHERE reservation_id = :id
            ");
            
            $stmt->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);
            $stmt->bindParam(':rejection_reason', $rejection_reason);
            $stmt->bindParam(':id', $reservation_id);
            $stmt->execute();
        } catch (PDOException $e) {
            // If rejection columns don't exist, use simpler update
            if (strpos($e->getMessage(), 'Unknown column') !== false || strpos($e->getMessage(), 'rebooking_rejected') !== false) {
                $rejection_note = $rejection_reason ? "Rebooking request rejected. Reason: " . $rejection_reason : "Rebooking request rejected";
                $stmt = $conn->prepare("
                    UPDATE reservations 
                    SET rebooking_requested = 0,
                        rebooking_approved = 0,
                        admin_notes = CONCAT(COALESCE(admin_notes, ''), '\n[', NOW(), '] ', :note),
                        updated_at = NOW()
                    WHERE reservation_id = :id
                ");
                
                $stmt->bindParam(':note', $rejection_note);
                $stmt->bindParam(':id', $reservation_id);
                $stmt->execute();
            } else {
                throw $e;
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Rebooking request rejected'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

