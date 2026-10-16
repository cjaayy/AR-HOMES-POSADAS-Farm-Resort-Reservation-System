<?php
/**
 * Staff: Check-Out Guest
 * Calculate overtime charges, damage charges, return security bond
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
    $actual_checkout_time = $_POST['actual_checkout_time'] ?? date('Y-m-d H:i:s');
    $damage_charges = $_POST['damage_charges'] ?? 0;
    $notes = $_POST['notes'] ?? '';
    
    if (!$reservation_id) {
        throw new Exception('Reservation ID is required');
    }
    
    $staff_id = $_SESSION['admin_id'] ?? 0;
    
    // Get reservation details
    $stmt = $conn->prepare("
        SELECT * FROM reservations 
        WHERE reservation_id = :id 
        AND checked_in = 1
        AND checked_out = 0
    ");
    $stmt->execute([':id' => $reservation_id]);
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reservation) {
        throw new Exception('Reservation not found or not checked in');
    }
    
    // Calculate overtime charges based on booking type
    $overtime_hours = 0;
    $overtime_charges = 0;
    $overtime_rate_per_hour = 500; // ₱500 per hour overtime
    
    $booking_type = $reservation['booking_type'];
    $check_in_time = new DateTime($reservation['check_in_date'] . ' ' . $reservation['check_in_time']);
    $actual_checkout = new DateTime($actual_checkout_time);
    
    // Expected checkout times
    if ($booking_type === 'daytime') {
        // 8 AM - 5 PM (9 hours)
        $expected_checkout = clone $check_in_time;
        $expected_checkout->setTime(17, 0); // 5 PM
    } elseif ($booking_type === 'nighttime') {
        // 6 PM - 6 AM next day (12 hours)
        $expected_checkout = clone $check_in_time;
        $expected_checkout->modify('+1 day');
        $expected_checkout->setTime(6, 0); // 6 AM next day
    } else {
        // 22 hours
        $expected_checkout = clone $check_in_time;
        $expected_checkout->modify('+22 hours');
    }
    
    // Calculate overtime if checked out late
    if ($actual_checkout > $expected_checkout) {
        $diff = $expected_checkout->diff($actual_checkout);
        $overtime_hours = $diff->h + ($diff->days * 24) + ($diff->i / 60);
        $overtime_hours = round($overtime_hours, 2);
        
        // Charge per hour (round up partial hours)
        $overtime_charges = ceil($overtime_hours) * $overtime_rate_per_hour;
    }
    
    // Calculate final amount
    $total_price = $reservation['total_price'];
    $final_amount = $total_price + $overtime_charges + $damage_charges;
    
    // Calculate security bond return
    $security_bond_collected = $reservation['security_bond_collected'] ?? 2000;
    $security_bond_deduction = $damage_charges; // Deduct damage charges from bond
    $security_bond_returned = max(0, $security_bond_collected - $security_bond_deduction);
    
    // Perform checkout
    $stmt = $conn->prepare("
        UPDATE reservations 
        SET checked_out = 1,
            checked_out_at = NOW(),
            checked_out_by = :staff_id,
            actual_checkout_time = :actual_checkout,
            overtime_hours = :overtime_hours,
            overtime_charges = :overtime_charges,
            damage_charges = :damage_charges,
            final_amount = :final_amount,
            security_bond_deduction = :bond_deduction,
            security_bond_returned_at = NOW(),
            status = 'completed',
            staff_notes = CONCAT(COALESCE(staff_notes, ''), '\n[', NOW(), '] Check-out completed. Overtime: ', :overtime_hours, ' hrs (₱', :overtime_charges, '). Damage: ₱', :damage_charges, '. Security bond returned: ₱', :bond_returned, '. ', :notes),
            updated_at = NOW()
        WHERE reservation_id = :id
    ");
    
    $stmt->bindParam(':staff_id', $staff_id, PDO::PARAM_INT);
    $stmt->bindParam(':actual_checkout', $actual_checkout_time, PDO::PARAM_STR);
    $stmt->bindParam(':overtime_hours', $overtime_hours, PDO::PARAM_STR);
    $stmt->bindParam(':overtime_charges', $overtime_charges, PDO::PARAM_STR);
    $stmt->bindParam(':damage_charges', $damage_charges, PDO::PARAM_STR);
    $stmt->bindParam(':final_amount', $final_amount, PDO::PARAM_STR);
    $stmt->bindParam(':bond_deduction', $security_bond_deduction, PDO::PARAM_STR);
    $stmt->bindParam(':bond_returned', $security_bond_returned, PDO::PARAM_STR);
    $stmt->bindParam(':notes', $notes, PDO::PARAM_STR);
    $stmt->bindParam(':id', $reservation_id);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'Guest checked out successfully!',
        'checkout_details' => [
            'expected_checkout' => $expected_checkout->format('Y-m-d H:i:s'),
            'actual_checkout' => $actual_checkout_time,
            'overtime_hours' => $overtime_hours,
            'overtime_charges' => $overtime_charges,
            'damage_charges' => $damage_charges,
            'original_amount' => $total_price,
            'final_amount' => $final_amount,
            'security_bond_collected' => $security_bond_collected,
            'security_bond_deduction' => $security_bond_deduction,
            'security_bond_returned' => $security_bond_returned
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

