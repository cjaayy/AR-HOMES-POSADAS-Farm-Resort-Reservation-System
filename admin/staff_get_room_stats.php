<?php
/**
 * Staff Venue Stats API - Get venue occupancy and booking stats
 * Updated for package-based booking system (not room-based)
 */
session_start();
header('Content-Type: application/json');

// Allow admin, super_admin, and staff roles (both admin session and staff-specific session)
$allowedRoles = ['admin', 'super_admin', 'staff'];
$userRole = $_SESSION['admin_role'] ?? '';

$isAdminSession = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true && in_array($userRole, $allowedRoles);
$isStaffSession = isset($_SESSION['staff_logged_in']) && $_SESSION['staff_logged_in'] === true;

if (!$isAdminSession && !$isStaffSession) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../config/connection.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // For package-based system: Calculate venue utilization
    // Count bookings for today and upcoming days
    
    // Today's bookings
    $todayBookingsSql = "SELECT COUNT(*) as today_bookings 
                         FROM reservations 
                         WHERE status IN ('confirmed', 'checked_in')
                           AND CURDATE() BETWEEN check_in_date AND check_out_date";
    $todayStmt = $conn->query($todayBookingsSql);
    $todayBookings = (int)($todayStmt->fetch(PDO::FETCH_ASSOC)['today_bookings'] ?? 0);
    
    // This month's occupancy rate
    $monthStart = date('Y-m-01');
    $monthEnd = date('Y-m-t');
    
    $bookedDaysSql = "SELECT COUNT(DISTINCT check_in_date) as booked_days 
                      FROM reservations 
                      WHERE status IN ('confirmed', 'checked_in', 'completed')
                        AND check_in_date BETWEEN :start AND :end";
    $bookedStmt = $conn->prepare($bookedDaysSql);
    $bookedStmt->execute([':start' => $monthStart, ':end' => $monthEnd]);
    $bookedDays = (int)($bookedStmt->fetch(PDO::FETCH_ASSOC)['booked_days'] ?? 0);
    
    // Calculate total days in current month
    $totalDaysInMonth = (int)date('t');
    $occupancyRate = $totalDaysInMonth > 0 ? round(($bookedDays / $totalDaysInMonth) * 100) : 0;
    
    // Upcoming bookings (next 30 days)
    $upcomingSql = "SELECT COUNT(*) as upcoming 
                    FROM reservations 
                    WHERE status IN ('confirmed', 'pending_confirmation')
                      AND check_in_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
    $upcomingStmt = $conn->query($upcomingSql);
    $upcomingBookings = (int)($upcomingStmt->fetch(PDO::FETCH_ASSOC)['upcoming'] ?? 0);
    
    // Active guests today
    $guestsSql = "SELECT SUM(number_of_guests) as active_guests 
                  FROM reservations 
                  WHERE status IN ('confirmed', 'checked_in')
                    AND CURDATE() BETWEEN check_in_date AND check_out_date";
    $guestsStmt = $conn->query($guestsSql);
    $activeGuests = (int)($guestsStmt->fetch(PDO::FETCH_ASSOC)['active_guests'] ?? 0);
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_rooms' => $totalDaysInMonth, // Total available days
            'occupied_rooms' => $bookedDays, // Booked days
            'available_rooms' => $totalDaysInMonth - $bookedDays, // Available days
            'maintenance_rooms' => 0,
            'reserved_rooms' => $todayBookings,
            'occupancy_rate' => $occupancyRate,
            'active_guests' => $activeGuests,
            'today_bookings' => $todayBookings,
            'upcoming_bookings' => $upcomingBookings
        ]
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>

