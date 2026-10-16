<?php
/**
 * Staff Reservation Actions API - Approve, Cancel reservations
 * Staff can approve and cancel reservations but cannot modify user accounts
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
    $reservationId = $_POST['reservation_id'] ?? '';
    
    if (empty($reservationId)) {
        echo json_encode(['success' => false, 'message' => 'Reservation ID is required']);
        exit;
    }
    
    switch ($action) {
        case 'approve':
            // Check if reservation exists and is in pending or canceled status
            $checkStmt = $conn->prepare("
                SELECT r.*, u.email as user_email, u.full_name as user_name
                FROM reservations r
                LEFT JOIN users u ON r.user_id = u.user_id
                WHERE r.reservation_id = :id
            ");
            $checkStmt->execute([':id' => $reservationId]);
            $reservation = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$reservation) {
                echo json_encode(['success' => false, 'message' => 'Reservation not found']);
                exit;
            }
            
            // Check if this is a re-approval (from cancelled status)
            $wasCancel = in_array($reservation['status'], ['canceled', 'cancelled']);
            
            // Allow approving pending OR canceled reservations (re-approve)
            $allowedStatuses = ['pending', 'pending_payment', 'pending_confirmation', 'canceled', 'cancelled'];
            if (!in_array($reservation['status'], $allowedStatuses)) {
                echo json_encode(['success' => false, 'message' => 'Only pending or canceled reservations can be approved']);
                exit;
            }
            
            // Update reservation status to confirmed
            $updateStmt = $conn->prepare("UPDATE reservations SET status = 'confirmed', updated_at = NOW() WHERE reservation_id = :id");
            $updateStmt->execute([':id' => $reservationId]);
            
            // Send notification and email
            if (!empty($reservation['user_id'])) {
                // Create in-app notification
                try {
                    $notifTitle = $wasCancel ? 'Reservation Re-Approved!' : 'Booking Confirmed!';
                    $notifType = $wasCancel ? 'booking_reapproved' : 'booking_confirmed';
                    $notifMessage = $wasCancel 
                        ? "Great news! Your reservation #{$reservationId} has been re-approved by staff. Check-in: " . date('M j, Y', strtotime($reservation['check_in_date']))
                        : "Your reservation #{$reservationId} has been confirmed! Check-in: " . date('M j, Y', strtotime($reservation['check_in_date']));
                    
                    $notif_stmt = $conn->prepare("
                        INSERT INTO notifications (user_id, type, title, message, link, created_at)
                        VALUES (:user_id, :type, :title, :message, :link, NOW())
                    ");
                    $notif_link = "dashboard.html?section=my-reservations&reservation=" . $reservationId;
                    
                    $notif_stmt->execute([
                        ':user_id' => $reservation['user_id'],
                        ':type' => $notifType,
                        ':title' => $notifTitle,
                        ':message' => $notifMessage,
                        ':link' => $notif_link
                    ]);
                } catch (Exception $e) {
                    error_log('Approval notification error: ' . $e->getMessage());
                }
                
                // Send confirmation email
                try {
                    require_once '../config/Mailer.php';
                    $mailer = new Mailer();
                    $mailer->sendBookingConfirmationEmail(
                        $reservation['user_email'],
                        $reservation['user_name'],
                        $reservation
                    );
                } catch (Exception $e) {
                    error_log('Approval email error: ' . $e->getMessage());
                }
            }
            
            // Log the action
            $logAction = $wasCancel ? 'reservation_reapproved' : 'reservation_approved';
            logStaffActivity($conn, $staffId, $staffName, $logAction, "Approved reservation #{$reservationId}", $reservationId);
            
            echo json_encode([
                'success' => true,
                'message' => $wasCancel ? 'Reservation re-approved successfully' : 'Reservation approved successfully'
            ]);
            break;
            
        case 'cancel':
            // Check if reservation exists
            $checkStmt = $conn->prepare("SELECT * FROM reservations WHERE reservation_id = :id");
            $checkStmt->execute([':id' => $reservationId]);
            $reservation = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$reservation) {
                echo json_encode(['success' => false, 'message' => 'Reservation not found']);
                exit;
            }
            
            $allowedStatuses = ['pending', 'pending_payment', 'pending_confirmation', 'confirmed'];
            if (!in_array($reservation['status'], $allowedStatuses)) {
                echo json_encode(['success' => false, 'message' => 'This reservation cannot be canceled']);
                exit;
            }
            
            // First check if cancelled_at column exists in reservations table
            try {
                $checkCancelledAtCol = $conn->query("SHOW COLUMNS FROM reservations LIKE 'cancelled_at'");
                if ($checkCancelledAtCol->rowCount() == 0) {
                    // Add the column if it doesn't exist
                    $conn->exec("ALTER TABLE reservations ADD COLUMN cancelled_at DATETIME DEFAULT NULL");
                }
            } catch (Exception $e) {
                error_log("Failed to check/add cancelled_at column: " . $e->getMessage());
            }
            
            // Update reservation status to cancelled with cancelled_at timestamp
            // Note: Database ENUM uses 'cancelled' (double 'l')
            $updateStmt = $conn->prepare("UPDATE reservations SET status = 'cancelled', cancelled_at = NOW(), updated_at = NOW() WHERE reservation_id = :id");
            $updateStmt->execute([':id' => $reservationId]);
            
            // Increment user's cancellation count
            if (!empty($reservation['user_id'])) {
                try {
                    // First check if cancellation_count column exists
                    $checkCol = $conn->query("SHOW COLUMNS FROM users LIKE 'cancellation_count'");
                    if ($checkCol->rowCount() == 0) {
                        // Add the column if it doesn't exist
                        $conn->exec("ALTER TABLE users ADD COLUMN cancellation_count INT DEFAULT 0");
                    }
                    
                    $incrStmt = $conn->prepare("UPDATE users SET cancellation_count = COALESCE(cancellation_count, 0) + 1 WHERE user_id = :user_id");
                    $incrStmt->execute([':user_id' => $reservation['user_id']]);
                } catch (Exception $e) {
                    // Log but don't fail the cancellation
                    error_log("Failed to increment cancellation count: " . $e->getMessage());
                }
                
                // Send in-app notification for cancellation
                try {
                    $notif_stmt = $conn->prepare("
                        INSERT INTO notifications (user_id, type, title, message, link, created_at)
                        VALUES (:user_id, 'booking_cancelled', 'Reservation Cancelled', :message, :link, NOW())
                    ");
                    $notif_message = "Your reservation #{$reservationId} has been cancelled by staff. Payment is refundable - contact us for refund processing or wait for re-approval within 24 hours.";
                    $notif_link = "dashboard.html?section=my-reservations&reservation=" . $reservationId;
                    
                    $notif_stmt->execute([
                        ':user_id' => $reservation['user_id'],
                        ':message' => $notif_message,
                        ':link' => $notif_link
                    ]);
                } catch (Exception $e) {
                    error_log('Cancellation notification error: ' . $e->getMessage());
                }
                
                // Send cancellation email
                try {
                    require_once '../config/Mailer.php';
                    $mailer = new Mailer();
                    $mailer->sendCancellationEmail(
                        $reservation['user_email'],
                        $reservation['user_name'],
                        $reservation
                    );
                } catch (Exception $e) {
                    error_log('Cancellation email error: ' . $e->getMessage());
                }
            }
            
            // Log the action
            logStaffActivity($conn, $staffId, $staffName, 'reservation_canceled', "Canceled reservation #{$reservationId}", $reservationId);
            
            echo json_encode([
                'success' => true,
                'message' => 'Reservation canceled successfully. Payment is refundable - please process refund if applicable.',
                'refundable' => true,
                'total_amount' => $reservation['total_amount'] ?? 0
            ]);
            break;
            
        case 'view':
            // Get reservation details (read-only)
            $stmt = $conn->prepare("SELECT r.*, u.full_name as guest_name, u.email as guest_email, u.phone_number as guest_phone 
                                    FROM reservations r 
                                    LEFT JOIN users u ON r.user_id = u.user_id 
                                    WHERE r.reservation_id = :id");
            $stmt->execute([':id' => $reservationId]);
            $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$reservation) {
                echo json_encode(['success' => false, 'message' => 'Reservation not found']);
                exit;
            }
            
            echo json_encode([
                'success' => true,
                'reservation' => $reservation
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (PDOException $e) {
    error_log("Staff reservation action error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}

/**
 * Log staff activity for audit purposes
 */
function logStaffActivity($conn, $staffId, $staffName, $actionType, $description, $referenceId = null) {
    try {
        // Check if staff_activity_log table exists
        $tableCheck = $conn->query("SHOW TABLES LIKE 'staff_activity_log'");
        if ($tableCheck->rowCount() > 0) {
            $sql = "INSERT INTO staff_activity_log (staff_id, staff_name, action_type, description, reference_id, created_at) 
                    VALUES (:staff_id, :staff_name, :action_type, :description, :reference_id, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':staff_id' => $staffId,
                ':staff_name' => $staffName,
                ':action_type' => $actionType,
                ':description' => $description,
                ':reference_id' => $referenceId
            ]);
        }
    } catch (Exception $e) {
        // Silently fail - logging shouldn't break the main action
        error_log("Failed to log staff activity: " . $e->getMessage());
    }
}
?>

