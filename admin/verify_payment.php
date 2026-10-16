<?php
/**
 * Admin: Verify Payment
 * Approves or rejects payment proof submitted by users
 */

session_start();
header('Content-Type: application/json');

require_once '../config/connection.php';

// Check if admin/staff is logged in
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
    $payment_type = $_POST['payment_type'] ?? 'downpayment';
    $action = $_POST['action'] ?? 'approve'; // 'approve' or 'reject'
    $rejection_reason = $_POST['rejection_reason'] ?? null;
    
    if (!$reservation_id) {
        throw new Exception('Reservation ID is required');
    }
    
    $admin_id = $_SESSION['admin_id'] ?? 0;
    
    if ($action === 'approve') {
        if ($payment_type === 'downpayment') {
            // Approve downpayment: Lock the date, change status to confirmed
            $stmt = $conn->prepare("
                UPDATE reservations 
                SET downpayment_verified = 1,
                    downpayment_verified_by = :admin_id,
                    downpayment_verified_at = NOW(),
                    status = 'confirmed',
                    date_locked = 1,
                    locked_until = DATE_ADD(check_in_date, INTERVAL 1 DAY),
                    updated_at = NOW()
                WHERE reservation_id = :id
            ");
            
            $stmt->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);
            $stmt->bindParam(':id', $reservation_id);
            $stmt->execute();
            
            // Get reservation details for email
            $stmt = $conn->prepare("
                SELECT r.*, u.email as user_email, u.full_name as user_name
                FROM reservations r
                JOIN users u ON r.user_id = u.user_id
                WHERE r.reservation_id = :id
            ");
            $stmt->bindParam(':id', $reservation_id);
            $stmt->execute();
            $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
            
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
            
            // Create in-app notification
            $notificationStatus = 'not_created';
            try {
                $notif_stmt = $conn->prepare("
                    INSERT INTO notifications (user_id, type, title, message, link, created_at)
                    VALUES (:user_id, 'booking_confirmed', 'Booking Confirmed!', :message, :link, NOW())
                ");
                $notif_message = "Your reservation #" . $reservation_id . " has been confirmed! Your date is now locked. Check-in: " . date('M j, Y', strtotime($reservation['check_in_date']));
                $notif_link = "dashboard.html?section=bookings-history&reservation=" . $reservation_id;
                
                $notif_stmt->execute([
                    ':user_id' => $reservation['user_id'],
                    ':message' => $notif_message,
                    ':link' => $notif_link
                ]);
                $notificationStatus = 'created';
            } catch (Exception $e) {
                $notificationStatus = 'error: ' . $e->getMessage();
                error_log('Notification error: ' . $e->getMessage());
            }
            
            $message = 'Downpayment verified! Reservation confirmed and date is now LOCKED. Email: ' . $emailStatus . ', Notification: ' . $notificationStatus;
            
        } else {
            // Approve full payment
            $stmt = $conn->prepare("
                UPDATE reservations 
                SET full_payment_verified = 1,
                    full_payment_verified_by = :admin_id,
                    full_payment_verified_at = NOW(),
                    updated_at = NOW()
                WHERE reservation_id = :id
            ");
            
            $stmt->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);
            $stmt->bindParam(':id', $reservation_id);
            $stmt->execute();
            
            $message = 'Full payment verified successfully!';
        }
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'action' => 'approved'
        ]);
        
    } else if ($action === 'reject') {
        // Reject payment
        if ($payment_type === 'downpayment') {
            $stmt = $conn->prepare("
                UPDATE reservations 
                SET downpayment_paid = 0,
                    downpayment_proof = NULL,
                    downpayment_reference = NULL,
                    downpayment_paid_at = NULL,
                    status = 'pending_payment',
                    admin_notes = CONCAT(COALESCE(admin_notes, ''), '\n[', NOW(), '] Downpayment rejected: ', :reason),
                    updated_at = NOW()
                WHERE reservation_id = :id
            ");
        } else {
            $stmt = $conn->prepare("
                UPDATE reservations 
                SET full_payment_paid = 0,
                    full_payment_proof = NULL,
                    full_payment_reference = NULL,
                    full_payment_paid_at = NULL,
                    admin_notes = CONCAT(COALESCE(admin_notes, ''), '\n[', NOW(), '] Full payment rejected: ', :reason),
                    updated_at = NOW()
                WHERE reservation_id = :id
            ");
        }
        
        $stmt->bindParam(':reason', $rejection_reason);
        $stmt->bindParam(':id', $reservation_id);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Payment rejected. User will be notified to resubmit.',
            'action' => 'rejected'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>

