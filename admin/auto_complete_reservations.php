<?php
/**
 * Auto-Complete Past Reservations
 * AR Homes Posadas Farm Resort Reservation System
 * 
 * PAYMENT REQUIREMENT:
 * - Reservations can ONLY be auto-completed if FULL PAYMENT is verified
 * - Partially paid reservations (only downpayment) require admin action
 * 
 * Flow:
 * 1. FULLY PAID (full_payment_verified = 1):
 *    - Auto-complete when check-in date passes → status = 'completed'
 * 
 * 2. ONLY DOWNPAYMENT PAID:
 *    - Keep as 'confirmed' - CANNOT auto-complete
 *    - Log for admin to handle (collect balance or mark no-show)
 * 
 * 3. CHECKED-IN guests:
 *    - Auto checkout ONLY if fully paid
 * 
 * Recommended cron schedule: Daily at midnight
 * 0 0 * * * php /path/to/auto_complete_reservations.php
 */

// Can be run from CLI or web
if (php_sapi_name() !== 'cli') {
    session_start();
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? '';
        if ($apiKey !== 'ar_homes_cron_key_2026') {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
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
    
    $results = [
        'completed' => [],
        'awaiting_balance' => [],
        'checked_out' => [],
        'errors' => []
    ];
    
    // ============================================================
    // PART 1: Auto-complete FULLY PAID reservations only
    // ============================================================
    // Condition: status='confirmed' AND check_in_date < TODAY 
    //            AND downpayment_verified=1 AND full_payment_verified=1
    
    $stmt = $pdo->prepare("
        SELECT 
            reservation_id,
            user_id,
            guest_name,
            guest_email,
            check_in_date,
            booking_type,
            total_amount,
            downpayment_amount,
            downpayment_verified,
            full_payment_verified
        FROM reservations
        WHERE status = 'confirmed'
        AND check_in_date < CURDATE()
        AND downpayment_verified = 1
        AND full_payment_verified = 1
    ");
    
    $stmt->execute();
    $fullyPaidReservations = $stmt->fetchAll();
    
    if (count($fullyPaidReservations) > 0) {
        $pdo->beginTransaction();
        
        try {
            foreach ($fullyPaidReservations as $reservation) {
                $updateStmt = $pdo->prepare("
                    UPDATE reservations
                    SET 
                        status = 'completed',
                        date_locked = 0,
                        admin_notes = CONCAT(
                            COALESCE(admin_notes, ''),
                            '\n[', NOW(), '] Auto-completed: Fully paid, check-in date passed.'
                        ),
                        updated_at = NOW()
                    WHERE reservation_id = :id
                    AND status = 'confirmed'
                ");
                
                $updateStmt->execute([':id' => $reservation['reservation_id']]);
                
                if ($updateStmt->rowCount() > 0) {
                    $results['completed'][] = [
                        'reservation_id' => $reservation['reservation_id'],
                        'guest_name' => $reservation['guest_name'],
                        'check_in_date' => $reservation['check_in_date'],
                        'reason' => 'Fully paid'
                    ];
                    
                    error_log(sprintf(
                        "[RESERVATION_AUTO_COMPLETED] ID: %s, Guest: %s, Check-in: %s (Fully Paid)",
                        $reservation['reservation_id'],
                        $reservation['guest_name'],
                        $reservation['check_in_date']
                    ));
                }
            }
            
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
    
    // ============================================================
    // PART 2: Flag PARTIALLY PAID reservations - CANNOT auto-complete
    // These require admin to either collect balance or mark as no-show
    // ============================================================
    
    $stmt2 = $pdo->prepare("
        SELECT 
            reservation_id,
            user_id,
            guest_name,
            guest_email,
            check_in_date,
            total_amount,
            downpayment_amount,
            (total_amount - downpayment_amount) as remaining_balance
        FROM reservations
        WHERE status = 'confirmed'
        AND check_in_date < CURDATE()
        AND downpayment_verified = 1
        AND (full_payment_verified = 0 OR full_payment_verified IS NULL)
    ");
    
    $stmt2->execute();
    $partiallyPaidReservations = $stmt2->fetchAll();
    
    if (count($partiallyPaidReservations) > 0) {
        foreach ($partiallyPaidReservations as $reservation) {
            // DO NOT change status - keep as 'confirmed'
            // This requires manual admin action
            $results['awaiting_balance'][] = [
                'reservation_id' => $reservation['reservation_id'],
                'guest_name' => $reservation['guest_name'],
                'guest_email' => $reservation['guest_email'],
                'check_in_date' => $reservation['check_in_date'],
                'remaining_balance' => $reservation['remaining_balance'],
                'action_required' => 'Admin must collect balance or mark as no-show'
            ];
            
            error_log(sprintf(
                "[AWAITING_BALANCE] ID: %s, Guest: %s, Balance: PHP %s - Cannot auto-complete, requires admin action",
                $reservation['reservation_id'],
                $reservation['guest_name'],
                number_format($reservation['remaining_balance'], 2)
            ));
        }
    }
    
    // ============================================================
    // PART 3: Auto-checkout CHECKED_IN guests ONLY if fully paid
    // ============================================================
    
    $stmt3 = $pdo->prepare("
        SELECT 
            reservation_id,
            user_id,
            guest_name,
            check_in_date,
            full_payment_verified
        FROM reservations
        WHERE status = 'checked_in'
        AND check_in_date < CURDATE()
        AND full_payment_verified = 1
    ");
    
    $stmt3->execute();
    $checkedInReservations = $stmt3->fetchAll();
    
    if (count($checkedInReservations) > 0) {
        $pdo->beginTransaction();
        
        try {
            foreach ($checkedInReservations as $reservation) {
                $updateStmt = $pdo->prepare("
                    UPDATE reservations
                    SET 
                        status = 'checked_out',
                        check_out_time = NOW(),
                        admin_notes = CONCAT(
                            COALESCE(admin_notes, ''),
                            '\n[', NOW(), '] Auto checked-out: Booking period ended, fully paid.'
                        ),
                        updated_at = NOW()
                    WHERE reservation_id = :id
                    AND status = 'checked_in'
                ");
                
                $updateStmt->execute([':id' => $reservation['reservation_id']]);
                
                if ($updateStmt->rowCount() > 0) {
                    $results['checked_out'][] = [
                        'reservation_id' => $reservation['reservation_id'],
                        'guest_name' => $reservation['guest_name']
                    ];
                    
                    error_log(sprintf(
                        "[RESERVATION_AUTO_CHECKED_OUT] ID: %s, Guest: %s",
                        $reservation['reservation_id'],
                        $reservation['guest_name']
                    ));
                }
            }
            
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
    
    // ============================================================
    // PART 4: Flag CHECKED_IN guests with unpaid balance
    // ============================================================
    
    $stmt4 = $pdo->prepare("
        SELECT 
            reservation_id,
            guest_name,
            check_in_date,
            (total_amount - downpayment_amount) as remaining_balance
        FROM reservations
        WHERE status = 'checked_in'
        AND check_in_date < CURDATE()
        AND (full_payment_verified = 0 OR full_payment_verified IS NULL)
    ");
    
    $stmt4->execute();
    $unpaidCheckedIn = $stmt4->fetchAll();
    
    foreach ($unpaidCheckedIn as $reservation) {
        $results['awaiting_balance'][] = [
            'reservation_id' => $reservation['reservation_id'],
            'guest_name' => $reservation['guest_name'],
            'remaining_balance' => $reservation['remaining_balance'],
            'action_required' => 'Guest checked-in but balance unpaid - collect before checkout'
        ];
        
        error_log(sprintf(
            "[CHECKED_IN_UNPAID] ID: %s, Guest: %s, Balance: PHP %s - Cannot auto checkout",
            $reservation['reservation_id'],
            $reservation['guest_name'],
            number_format($reservation['remaining_balance'], 2)
        ));
    }
    
    // ============================================================
    // Summary
    // ============================================================
    
    $summary = sprintf(
        "Results: %d auto-completed (fully paid), %d auto-checked-out, %d awaiting balance (manual action needed)",
        count($results['completed']),
        count($results['checked_out']),
        count($results['awaiting_balance'])
    );
    
    error_log("[AUTO_COMPLETE_RESERVATIONS] " . $summary);
    
    if (php_sapi_name() === 'cli') {
        echo "=== Auto-Complete Reservations ===\n";
        echo $summary . "\n";
        
        if (count($results['completed']) > 0) {
            echo "\n✅ COMPLETED (Fully Paid):\n";
            foreach ($results['completed'] as $r) {
                echo "   • {$r['reservation_id']}: {$r['guest_name']} (Check-in: {$r['check_in_date']})\n";
            }
        }
        
        if (count($results['checked_out']) > 0) {
            echo "\n✅ AUTO CHECKED-OUT:\n";
            foreach ($results['checked_out'] as $r) {
                echo "   • {$r['reservation_id']}: {$r['guest_name']}\n";
            }
        }
        
        if (count($results['awaiting_balance']) > 0) {
            echo "\n⚠️  AWAITING BALANCE (Requires Admin Action):\n";
            foreach ($results['awaiting_balance'] as $r) {
                $balance = isset($r['remaining_balance']) ? 'PHP ' . number_format($r['remaining_balance'], 2) : 'N/A';
                echo "   • {$r['reservation_id']}: {$r['guest_name']}\n";
                echo "     Balance: {$balance}\n";
                echo "     Action: {$r['action_required']}\n";
            }
            echo "\n💡 Tip: These reservations need admin to either:\n";
            echo "   1. Verify the remaining balance payment, OR\n";
            echo "   2. Mark as 'no_show' if guest didn't arrive\n";
        }
        
        if (empty($results['completed']) && empty($results['checked_out']) && empty($results['awaiting_balance'])) {
            echo "\n✓ No reservations needed processing.\n";
        }
    } else {
        echo json_encode([
            'success' => true,
            'message' => $summary,
            'results' => $results
        ], JSON_PRETTY_PRINT);
    }
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    error_log("[AUTO_COMPLETE_ERROR] " . $error);
    
    if (php_sapi_name() === 'cli') {
        echo "ERROR: " . $error . "\n";
        exit(1);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $error]);
    }
} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
    error_log("[AUTO_COMPLETE_ERROR] " . $error);
    
    if (php_sapi_name() === 'cli') {
        echo "ERROR: " . $error . "\n";
        exit(1);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $error]);
    }
}

