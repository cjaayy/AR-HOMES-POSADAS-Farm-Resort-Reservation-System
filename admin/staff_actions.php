<?php
/**
 * Staff actions for reservations: create, update_status
 */
session_start();
header('Content-Type: application/json');

// Allow admin, super_admin, and staff roles
$allowedRoles = ['admin', 'super_admin', 'staff'];
$userRole = $_SESSION['admin_role'] ?? '';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || !in_array($userRole, $allowedRoles)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../config/connection.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    $action = $_POST['action'] ?? null;
    if ($action === 'create') {
        $guest_name = trim($_POST['guest_name'] ?? '');
        $guest_phone = trim($_POST['guest_phone'] ?? '');
        $guest_email = trim($_POST['guest_email'] ?? '');
        $room = trim($_POST['room'] ?? 'TBD');
        $check_in = $_POST['check_in_date'] ?? date('Y-m-d');
        $check_out = $_POST['check_out_date'] ?? date('Y-m-d', strtotime('+1 day'));
        $booking_type = $_POST['booking_type'] ?? 'daytime';
        $package_type = $_POST['package_type'] ?? 'basic';

        if ($guest_name === '') {
            echo json_encode(['success' => false, 'message' => 'Guest name required']); exit;
        }

        // Set default values for walk-in reservations
        $check_in_time = '09:00:00';
        $check_out_time = '17:00:00';
        $number_of_days = 1;
        $total_amount = 8000.00; // Default basic package daytime
        $downpayment_amount = 4000.00; // 50% downpayment
        $security_bond = 2000.00;
        
        $stmt = $conn->prepare("INSERT INTO reservations (
            guest_name, guest_phone, guest_email, room, 
            booking_type, package_type,
            check_in_date, check_in_time, check_out_date, check_out_time,
            number_of_days, total_amount, downpayment_amount, 
            remaining_balance, security_bond, payment_method,
            status, created_at
        ) VALUES (
            :g, :p, :e, :r, 
            :bt, :pt,
            :ci, :cit, :co, :cot,
            :days, :total, :down,
            :balance, :bond, 'cash',
            'pending_payment', NOW()
        )");
        $stmt->bindParam(':g', $guest_name);
        $stmt->bindParam(':p', $guest_phone);
        $stmt->bindParam(':e', $guest_email);
        $stmt->bindParam(':r', $room);
        $stmt->bindParam(':bt', $booking_type);
        $stmt->bindParam(':pt', $package_type);
        $stmt->bindParam(':ci', $check_in);
        $stmt->bindParam(':cit', $check_in_time);
        $stmt->bindParam(':co', $check_out);
        $stmt->bindParam(':cot', $check_out_time);
        $stmt->bindParam(':days', $number_of_days, PDO::PARAM_INT);
        $stmt->bindParam(':total', $total_amount);
        $stmt->bindParam(':down', $downpayment_amount);
        $balance = $total_amount - $downpayment_amount;
        $stmt->bindParam(':balance', $balance);
        $stmt->bindParam(':bond', $security_bond);
        $stmt->execute();

        echo json_encode(['success' => true, 'reservation_id' => $conn->lastInsertId()]);
        exit;
    } elseif ($action === 'update_status') {
        $id = trim($_POST['reservation_id'] ?? '');
        $status = trim($_POST['status'] ?? '');
        if ($id === '' || $status === '') { echo json_encode(['success'=>false,'message'=>'Invalid']); exit; }
        
        // Normalize 'canceled' to 'cancelled' to match database ENUM
        if ($status === 'canceled') {
            $status = 'cancelled';
        }
        
        // Get current reservation status first to check if it's a re-approval
        $checkStmt = $conn->prepare("SELECT status FROM reservations WHERE reservation_id = :id");
        $checkStmt->execute([':id' => $id]);
        $currentReservation = $checkStmt->fetch(PDO::FETCH_ASSOC);
        $wasCancel = $currentReservation && in_array($currentReservation['status'], ['canceled', 'cancelled']);
        
        // Update status - if confirming, also verify downpayment
        if ($status === 'confirmed') {
            $admin_id = $_SESSION['admin_id'] ?? 0;
            $stmt = $conn->prepare("
                UPDATE reservations 
                SET status = :s, 
                    downpayment_verified = 1,
                    downpayment_verified_by = :admin_id,
                    downpayment_verified_at = NOW(),
                    date_locked = 1,
                    locked_until = DATE_ADD(check_in_date, INTERVAL 1 DAY),
                    updated_at = NOW() 
                WHERE reservation_id = :id
            ");
            $stmt->bindParam(':s', $status);
            $stmt->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
        } elseif ($status === 'cancelled') {
            // For cancellation, also set cancelled_at timestamp
            $stmt = $conn->prepare("UPDATE reservations SET status = :s, cancelled_at = NOW(), updated_at = NOW() WHERE reservation_id = :id");
            $stmt->bindParam(':s', $status);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            // Send notification for cancellation
            $stmt2 = $conn->prepare("
                SELECT r.*, u.email as user_email, u.full_name as user_name
                FROM reservations r
                JOIN users u ON r.user_id = u.user_id
                WHERE r.reservation_id = :id
            ");
            $stmt2->bindParam(':id', $id);
            $stmt2->execute();
            $reservation = $stmt2->fetch(PDO::FETCH_ASSOC);
            
            if ($reservation) {
                // Create in-app notification for cancellation
                try {
                    $notif_stmt = $conn->prepare("
                        INSERT INTO notifications (user_id, type, title, message, link, created_at)
                        VALUES (:user_id, 'booking_cancelled', 'Reservation Cancelled', :message, :link, NOW())
                    ");
                    $notif_message = "Your reservation #" . $id . " has been cancelled by admin/staff. Payment is refundable - contact us for refund processing or wait for re-approval within 24 hours.";
                    $notif_link = "dashboard.html?section=my-reservations&reservation=" . $id;
                    
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
        } else {
            // For other status changes, just update status
            $stmt = $conn->prepare("UPDATE reservations SET status = :s, updated_at = NOW() WHERE reservation_id = :id");
            $stmt->bindParam(':s', $status);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
        }
        
        // If status is 'confirmed', send notification and email
        if ($status === 'confirmed') {
            // Get reservation and user details
            $stmt = $conn->prepare("
                SELECT r.*, u.email as user_email, u.full_name as user_name
                FROM reservations r
                JOIN users u ON r.user_id = u.user_id
                WHERE r.reservation_id = :id
            ");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($reservation) {
                // Send email notification
                $emailStatus = 'not_sent';
                try {
                    require_once '../config/Mailer.php';
                    $mailer = new Mailer();
                    $emailSent = $mailer->sendBookingConfirmationEmail(
                        $reservation['user_email'],
                        $reservation['user_name'],
                        $reservation
                    );
                    
                    if ($emailSent) {
                        $emailStatus = 'sent';
                    } else {
                        $emailStatus = 'failed';
                        error_log('Failed to send confirmation email to: ' . $reservation['user_email']);
                    }
                } catch (Exception $e) {
                    $emailStatus = 'error: ' . $e->getMessage();
                    error_log('Email error: ' . $e->getMessage());
                }
                
                // Create in-app notification (different message for re-approval)
                $notificationStatus = 'not_created';
                try {
                    $notifTitle = $wasCancel ? 'Reservation Re-Approved!' : 'Booking Confirmed!';
                    $notifType = $wasCancel ? 'booking_reapproved' : 'booking_confirmed';
                    $notifMessage = $wasCancel 
                        ? "Great news! Your reservation #" . $id . " has been re-approved! Check-in: " . date('M j, Y', strtotime($reservation['check_in_date']))
                        : "Your reservation #" . $id . " has been confirmed! Check-in: " . date('M j, Y', strtotime($reservation['check_in_date']));
                    
                    $notif_stmt = $conn->prepare("
                        INSERT INTO notifications (user_id, type, title, message, link, created_at)
                        VALUES (:user_id, :type, :title, :message, :link, NOW())
                    ");
                    $notif_link = "dashboard.html?section=my-reservations&reservation=" . $id;
                    
                    $notif_stmt->execute([
                        ':user_id' => $reservation['user_id'],
                        ':type' => $notifType,
                        ':title' => $notifTitle,
                        ':message' => $notifMessage,
                        ':link' => $notif_link
                    ]);
                    $notificationStatus = 'created';
                } catch (Exception $e) {
                    $notificationStatus = 'error: ' . $e->getMessage();
                    error_log('Notification error: ' . $e->getMessage());
                }
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Status updated. Email: ' . $emailStatus . ', Notification: ' . $notificationStatus
                ]); 
                exit;
            }
        }
        
        echo json_encode(['success' => true]); exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: '.$e->getMessage()]);
}

?>

