<?php
/**
 * Admin: Get Calendar Data
 * Fetch all reservations for calendar display
 */

session_start();
header('Content-Type: application/json');

require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check role
$role = strtolower($_SESSION['admin_role'] ?? '');
if (!in_array($role, ['admin', 'super_admin', 'staff'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get date range parameters (optional)
    $startDate = $_GET['start'] ?? date('Y-m-01'); // First day of current month
    $endDate = $_GET['end'] ?? date('Y-m-t', strtotime('+2 months')); // 2 months ahead
    
    // Clean up date format (FullCalendar sends ISO format with time)
    $startDate = substr($startDate, 0, 10);
    $endDate = substr($endDate, 0, 10);
    
    // Get all reservations within the date range (show all statuses for visibility)
    $sql = "
        SELECT 
            r.reservation_id,
            r.user_id,
            r.guest_name,
            r.guest_phone,
            r.guest_email,
            r.booking_type,
            r.package_type,
            r.check_in_date,
            r.check_out_date,
            r.check_in_time,
            r.check_out_time,
            r.status,
            r.total_amount,
            r.downpayment_amount,
            r.downpayment_verified,
            r.full_payment_verified,
            r.room,
            r.created_at
        FROM reservations r
        WHERE r.check_in_date <= :end_date 
        AND r.check_out_date >= :start_date
        ORDER BY r.check_in_date ASC
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        'start_date' => $startDate,
        'end_date' => $endDate
    ]);
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Build events array for calendar
    $events = [];
    $unavailableDates = [];
    
    foreach ($reservations as $res) {
        // Set color based on status and booking type
        $colors = [
            'pending' => ['bg' => '#f59e0b', 'border' => '#d97706'],
            'confirmed' => ['bg' => '#10b981', 'border' => '#059669'],
            'checked_in' => ['bg' => '#3b82f6', 'border' => '#2563eb'],
            'checked_out' => ['bg' => '#6b7280', 'border' => '#4b5563'],
            'completed' => ['bg' => '#8b5cf6', 'border' => '#7c3aed'],
            'cancelled' => ['bg' => '#ef4444', 'border' => '#dc2626'],
            'expired' => ['bg' => '#9ca3af', 'border' => '#6b7280'],
            'no_show' => ['bg' => '#f87171', 'border' => '#ef4444']
        ];
        
        $color = $colors[$res['status']] ?? ['bg' => '#64748b', 'border' => '#475569'];
        
        // Booking type label
        $bookingTypeLabels = [
            'daytime' => '☀️ DAY',
            'nighttime' => '🌙 NIGHT',
            '22hours' => '⏰ 22HRS'
        ];
        $typeLabel = $bookingTypeLabels[$res['booking_type']] ?? $res['booking_type'];
        
        $events[] = [
            'id' => $res['reservation_id'],
            'title' => $typeLabel . ' - ' . $res['guest_name'],
            'start' => $res['check_in_date'],
            'end' => date('Y-m-d', strtotime($res['check_out_date'] . ' +1 day')), // FullCalendar end is exclusive
            'backgroundColor' => $color['bg'],
            'borderColor' => $color['border'],
            'textColor' => '#ffffff',
            'extendedProps' => [
                'reservation_id' => $res['reservation_id'],
                'guest_name' => $res['guest_name'],
                'guest_phone' => $res['guest_phone'],
                'booking_type' => $res['booking_type'],
                'package_type' => $res['package_type'],
                'status' => $res['status'],
                'check_in_time' => $res['check_in_time'],
                'check_out_time' => $res['check_out_time'],
                'total_amount' => $res['total_amount'],
                'room' => $res['room']
            ]
        ];
        
        // Mark dates as unavailable (for confirmed/checked_in reservations)
        if (in_array($res['status'], ['confirmed', 'checked_in', 'pending'])) {
            $current = new DateTime($res['check_in_date']);
            $end = new DateTime($res['check_out_date']);
            while ($current <= $end) {
                $dateStr = $current->format('Y-m-d');
                if (!in_array($dateStr, $unavailableDates)) {
                    $unavailableDates[] = $dateStr;
                }
                $current->modify('+1 day');
            }
        }
    }
    
    // Get summary stats
    $today = date('Y-m-d');
    $statsSql = "
        SELECT 
            SUM(CASE WHEN check_in_date = :today AND status IN ('confirmed', 'pending') THEN 1 ELSE 0 END) as arrivals_today,
            SUM(CASE WHEN check_out_date = :today AND status = 'checked_in' THEN 1 ELSE 0 END) as departures_today,
            SUM(CASE WHEN status = 'checked_in' THEN 1 ELSE 0 END) as currently_occupied,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_bookings
        FROM reservations
    ";
    $statsStmt = $conn->prepare($statsSql);
    $statsStmt->execute(['today' => $today]);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'events' => $events,
        'unavailable_dates' => $unavailableDates,
        'stats' => $stats,
        'date_range' => [
            'start' => $startDate,
            'end' => $endDate
        ]
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

