<?php
/**
 * AR Homes Posadas Farm Resort Reservation System
 * Get User Reservations API
 */

session_start();
header('Content-Type: application/json');

require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Please login to view reservations'
    ]);
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
    
    // Get user's reservations
    $stmt = $pdo->prepare("
        SELECT 
            reservation_id,
            booking_type,
            package_type,
            check_in_date,
            check_out_date,
            check_in_time,
            check_out_time,
            number_of_days,
            number_of_nights,
            group_size,
            group_type,
            total_amount,
            downpayment_amount,
            remaining_balance,
            downpayment_paid,
            full_payment_paid,
            payment_method,
            status,
            security_bond_paid,
            security_bond_returned,
            created_at
        FROM reservations
        WHERE user_id = ?
        ORDER BY created_at DESC
    ");
    
    $stmt->execute([$user_id]);
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format reservations for display
    foreach ($reservations as &$reservation) {
        // Format dates
        $reservation['check_in_date_formatted'] = date('M d, Y', strtotime($reservation['check_in_date']));
        $reservation['check_out_date_formatted'] = date('M d, Y', strtotime($reservation['check_out_date']));
        
        // Format times to 12-hour format
        $reservation['check_in_time_formatted'] = date('g:i A', strtotime($reservation['check_in_time']));
        $reservation['check_out_time_formatted'] = date('g:i A', strtotime($reservation['check_out_time']));
        
        // Add booking type label
        $booking_labels = [
            'daytime' => 'Daytime',
            'nighttime' => 'Nighttime',
            '22hours' => '22 Hours'
        ];
        $reservation['booking_type_label'] = $booking_labels[$reservation['booking_type']] ?? $reservation['booking_type'];
        
        // Add status badge class
        $status_classes = [
            'pending_payment' => 'warning',
            'confirmed' => 'success',
            'completed' => 'info',
            'cancelled' => 'danger',
            'forfeited' => 'danger'
        ];
        $reservation['status_class'] = $status_classes[$reservation['status']] ?? 'secondary';
        
        // Format payment status
        $reservation['payment_status'] = 'Pending Payment';
        if ($reservation['downpayment_paid'] && !$reservation['full_payment_paid']) {
            $reservation['payment_status'] = 'Downpayment Paid';
        } elseif ($reservation['full_payment_paid']) {
            $reservation['payment_status'] = 'Fully Paid';
        }
    }
    
    echo json_encode([
        'success' => true,
        'reservations' => $reservations,
        'count' => count($reservations)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

