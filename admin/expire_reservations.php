<?php
/**
 * Auto-Expire Unpaid Reservations
 * AR Homes Posadas Farm Resort Reservation System
 * 
 * This script should be run as a cron job to automatically expire
 * reservations that haven't been paid within 24 hours.
 * 
 * Recommended cron schedule: Every 15 minutes
 * Example: 0,15,30,45 * * * * php /path/to/expire_reservations.php
 */

// Can be run from CLI or web
if (php_sapi_name() !== 'cli') {
    // If run from web, require authentication
    session_start();
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    header('Content-Type: application/json');
}

require_once __DIR__ . '/../config/database.php';

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
    
    $expiredCount = 0;
    $expiredIds = [];
    
    // Start transaction before SELECT FOR UPDATE
    $pdo->beginTransaction();
    
    try {
        // Find expired reservations
        // Reservations that are:
        // - status = 'pending_payment'
        // - downpayment_paid = 0 OR downpayment_paid IS NULL
        // - created more than 24 hours ago OR locked_until has passed
        $stmt = $pdo->prepare("
            SELECT 
                reservation_id,
                user_id,
                guest_name,
                guest_email,
                check_in_date,
                booking_type,
                total_amount,
                created_at,
                locked_until
            FROM reservations
            WHERE status = 'pending_payment'
            AND (downpayment_paid = 0 OR downpayment_paid IS NULL)
            AND (
                created_at <= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                OR (locked_until IS NOT NULL AND locked_until <= NOW())
            )
            FOR UPDATE
        ");
        
        $stmt->execute();
        $expiredReservations = $stmt->fetchAll();
        
        if (count($expiredReservations) > 0) {
            foreach ($expiredReservations as $reservation) {
                // Update status to expired
                $updateStmt = $pdo->prepare("
                    UPDATE reservations
                    SET 
                        status = 'expired',
                        date_locked = 0,
                        locked_until = NULL,
                        admin_notes = CONCAT(
                            COALESCE(admin_notes, ''),
                            '\n[', NOW(), '] Auto-expired: Payment not received within 24 hours.'
                        ),
                        updated_at = NOW()
                    WHERE reservation_id = :id
                    AND status = 'pending_payment'
                ");
                
                $updateStmt->execute([':id' => $reservation['reservation_id']]);
                
                if ($updateStmt->rowCount() > 0) {
                    $expiredCount++;
                    $expiredIds[] = $reservation['reservation_id'];
                    
                    // Log the expiration
                    error_log(sprintf(
                        "[RESERVATION_EXPIRED] ID: %s, Guest: %s, Check-in: %s, Created: %s",
                        $reservation['reservation_id'],
                        $reservation['guest_name'],
                        $reservation['check_in_date'],
                        $reservation['created_at']
                    ));
                    
                    // TODO: Send expiration notification email to guest
                    // Mailer::sendReservationExpiredEmail($reservation);
                }
            }
        }
        
        $pdo->commit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
    $result = [
        'success' => true,
        'message' => $expiredCount > 0 
            ? "Expired {$expiredCount} unpaid reservation(s)."
            : "No reservations to expire.",
        'expired_count' => $expiredCount,
        'expired_ids' => $expiredIds,
        'checked_at' => date('Y-m-d H:i:s')
    ];
    
    if (php_sapi_name() === 'cli') {
        echo "[" . date('Y-m-d H:i:s') . "] " . $result['message'] . "\n";
        if ($expiredCount > 0) {
            echo "Expired IDs: " . implode(', ', $expiredIds) . "\n";
        }
    } else {
        echo json_encode($result);
    }
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    error_log("[EXPIRE_RESERVATIONS_ERROR] " . $error);
    
    if (php_sapi_name() === 'cli') {
        echo "[ERROR] " . $error . "\n";
        exit(1);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $error]);
    }
} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
    error_log("[EXPIRE_RESERVATIONS_ERROR] " . $error);
    
    if (php_sapi_name() === 'cli') {
        echo "[ERROR] " . $error . "\n";
        exit(1);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $error]);
    }
}
?>

