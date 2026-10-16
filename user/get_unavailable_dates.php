<?php
/**
 * Get Unavailable Dates
 * Returns a list of dates that are already booked and confirmed
 * These dates should be disabled in the date picker
 */

session_start();
header('Content-Type: application/json');

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
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
    
    $data = json_decode(file_get_contents('php://input'), true);
    $booking_type = $data['booking_type'] ?? null;
    
    if (!$booking_type) {
        throw new Exception('Booking type is required');
    }
    
    // Get all reservations that are locked by confirmed/paid bookings
    // Block dates across ALL booking types - if any package is booked for a date,
    // that date is unavailable for all other packages (exclusive resort access)
    $stmt = $pdo->prepare("
        SELECT 
            reservation_id, 
            guest_name, 
            booking_type,
            check_in_date, 
            check_out_date,
            status, 
            downpayment_verified, 
            date_locked
        FROM reservations 
        WHERE status IN ('confirmed', 'checked_in', 'checked_out', 'pending_confirmation')
        AND (downpayment_verified = 1 OR date_locked = 1)
        AND check_out_date >= CURDATE()
        ORDER BY check_in_date ASC
    ");
    
    $stmt->execute();
    
    $unavailable_dates = [];
    
    // Determine if the requested booking type is overnight (needs day-before blocking)
    $is_overnight_booking = in_array($booking_type, ['nighttime', '22hours', 'venue-nighttime', 'venue-22hours']);
    
    // For each reservation, block dates that would conflict
    while ($row = $stmt->fetch()) {
        $check_in = new DateTime($row['check_in_date']);
        $check_out = new DateTime($row['check_out_date']);
        
        // Block all dates from check-in to check-out (inclusive)
        $current = clone $check_in;
        while ($current <= $check_out) {
            $date_str = $current->format('Y-m-d');
            if (!in_array($date_str, $unavailable_dates)) {
                $unavailable_dates[] = $date_str;
            }
            $current->modify('+1 day');
            
            // Break after first iteration if check_in equals check_out (single day booking)
            if ($check_in->format('Y-m-d') === $check_out->format('Y-m-d')) {
                break;
            }
        }
        
        // Also block the day BEFORE check-in for overnight bookings only
        // Because an overnight booking starting that day would have checkout on the blocked check-in date
        if ($is_overnight_booking) {
            $day_before = clone $check_in;
            $day_before->modify('-1 day');
            $day_before_str = $day_before->format('Y-m-d');
            if (!in_array($day_before_str, $unavailable_dates)) {
                $unavailable_dates[] = $day_before_str;
            }
        }
    }
    
    // Sort the dates
    sort($unavailable_dates);
    
    // Debug log
    error_log("DEBUG get_unavailable_dates: " . json_encode($unavailable_dates));
    
    echo json_encode([
        'success' => true,
        'unavailable_dates' => $unavailable_dates,
        'count' => count($unavailable_dates),
        'message' => count($unavailable_dates) > 0 
            ? count($unavailable_dates) . ' date(s) are already booked' 
            : 'All dates are currently available'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
        'unavailable_dates' => []
    ]);
}

