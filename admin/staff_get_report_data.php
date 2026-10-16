<?php
/**
 * Staff Reports Data API - Get report statistics for various periods
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
    
    $period = isset($_GET['period']) ? $_GET['period'] : 'week';
    $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : null;
    $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : null;
    
    // Determine date range based on period
    switch ($period) {
        case 'today':
            $start = date('Y-m-d');
            $end = date('Y-m-d');
            break;
        case 'week':
            $start = date('Y-m-d', strtotime('-7 days'));
            $end = date('Y-m-d');
            break;
        case 'month':
            $start = date('Y-m-d', strtotime('-30 days'));
            $end = date('Y-m-d');
            break;
        case 'year':
            $start = date('Y-m-d', strtotime('-365 days'));
            $end = date('Y-m-d');
            break;
        case 'custom':
            $start = $startDate ?: date('Y-m-d', strtotime('-7 days'));
            $end = $endDate ?: date('Y-m-d');
            break;
        default:
            $start = date('Y-m-d', strtotime('-7 days'));
            $end = date('Y-m-d');
    }
    
    // Check if tables exist
    $tableCheck = $conn->query("SHOW TABLES LIKE 'reservations'");
    if ($tableCheck->rowCount() === 0) {
        // Return sample data if no tables
        echo json_encode([
            'success' => true,
            'period' => $period,
            'start_date' => $start,
            'end_date' => $end,
            'metrics' => [
                'total_reservations' => 127,
                'total_revenue' => 45230,
                'occupancy_rate' => 78,
                'cancellations' => 8
            ],
            'trend_data' => [
                'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                'values' => [12, 19, 15, 25, 22, 30, 28]
            ],
            'revenue_data' => [
                'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                'values' => [4200, 5800, 4500, 7200, 6800, 9500, 8600]
            ]
        ]);
        exit;
    }
    
    // Check which price column exists (do this once at the start)
    $columnsQuery = $conn->query("SHOW COLUMNS FROM reservations");
    $columns = $columnsQuery->fetchAll(PDO::FETCH_COLUMN);
    
    $priceColumn = null;
    $possiblePriceColumns = ['total_price', 'total_amount', 'amount', 'price', 'total_cost'];
    foreach ($possiblePriceColumns as $col) {
        if (in_array($col, $columns)) {
            $priceColumn = $col;
            break;
        }
    }
    
    // Get reservations metrics (only FULLY PAID reservations - full_payment_verified = 1)
    $sql = "SELECT 
                COUNT(*) as total_reservations,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancellations,
                SUM(CASE WHEN status = 'confirmed' OR status = 'completed' THEN 1 ELSE 0 END) as confirmed_reservations
            FROM reservations
            WHERE DATE(created_at) BETWEEN :start AND :end
            AND full_payment_verified = 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([':start' => $start, ':end' => $end]);
    $metrics = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Calculate total revenue from all non-cancelled reservations
    $totalRevenue = 0;
    try {
        if ($priceColumn) {
            $revSql = "SELECT SUM($priceColumn) as revenue FROM reservations 
                       WHERE DATE(created_at) BETWEEN :start AND :end 
                       AND status NOT IN ('cancelled', 'rejected')
                       AND full_payment_verified = 1
                       AND $priceColumn IS NOT NULL AND $priceColumn > 0";
            $revStmt = $conn->prepare($revSql);
            $revStmt->execute([':start' => $start, ':end' => $end]);
            $revData = $revStmt->fetch(PDO::FETCH_ASSOC);
            $totalRevenue = $revData['revenue'] ?? 0;
        } else {
            // No price column found, use estimated value
            $totalRevenue = ($metrics['confirmed_reservations'] ?? 0) * 350;
        }
    } catch (PDOException $e) {
        // Fallback to estimated value
        $totalRevenue = ($metrics['confirmed_reservations'] ?? 0) * 350;
    }
    
    // Get venue occupancy rate (package-based system)
    // Calculate occupancy based on booked days vs available days in the period
    $occupancyRate = 0;
    
    // Count distinct dates with confirmed/checked-in reservations
    $bookedDaysSql = "SELECT COUNT(DISTINCT check_in_date) as booked_days 
                      FROM reservations 
                      WHERE status IN ('confirmed', 'checked_in', 'completed')
                        AND full_payment_verified = 1
                        AND check_in_date BETWEEN :start AND :end";
    $bookedStmt = $conn->prepare($bookedDaysSql);
    $bookedStmt->execute([':start' => $start, ':end' => $end]);
    $bookedDays = (int)($bookedStmt->fetch(PDO::FETCH_ASSOC)['booked_days'] ?? 0);
    
    // Calculate total days in period
    $startDate = new DateTime($start);
    $endDate = new DateTime($end);
    $interval = $startDate->diff($endDate);
    $totalDays = $interval->days + 1;
    
    $occupancyRate = $totalDays > 0 ? round(($bookedDays / $totalDays) * 100) : 0;
    
    // Get daily trend data (only verified paid bookings) - by created_at (booking date)
    $trendSql = "SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as count,
                    SUM($priceColumn) as revenue
                 FROM reservations
                 WHERE DATE(created_at) BETWEEN :start AND :end
                 AND status IN ('confirmed', 'checked_in', 'completed')
                 AND full_payment_verified = 1
                 GROUP BY DATE(created_at)
                 ORDER BY date ASC";
    
    $trendStmt = $conn->prepare($trendSql);
    $trendStmt->execute([':start' => $start, ':end' => $end]);
    $trendData = $trendStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $trendLabels = [];
    $trendValues = [];
    $revenueValues = [];
    
    foreach ($trendData as $row) {
        $trendLabels[] = date('M d', strtotime($row['date']));
        $trendValues[] = (int)$row['count'];
        $revenueValues[] = (float)($row['revenue'] ?? 0);
    }
    
    // If no data, generate sample for display
    if (empty($trendLabels)) {
        $trendLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $trendValues = [0, 0, 0, 0, 0, 0, 0];
        $revenueValues = [0, 0, 0, 0, 0, 0, 0];
    }
    
    // Get package type distribution (not room-based) - by created_at date
    $roomTypeData = [];
    try {
        if ($priceColumn) {
            $roomSql = "SELECT 
                            COALESCE(package_type, 'N/A') as room_type,
                            COUNT(*) as bookings,
                            SUM($priceColumn) as revenue
                        FROM reservations
                        WHERE DATE(created_at) BETWEEN :start AND :end
                        AND status IN ('confirmed', 'checked_in', 'completed')
                        AND full_payment_verified = 1
                        GROUP BY package_type
                        ORDER BY bookings DESC";
            $roomStmt = $conn->prepare($roomSql);
            $roomStmt->execute([':start' => $start, ':end' => $end]);
            $roomTypeData = $roomStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        // Silently fail if package type query fails
    }
    
    // Get guest statistics (compare current week vs previous week)
    $guestStats = [];
    try {
        // Previous period: same duration, ending 1 day before current period starts
        $prevEnd = date('Y-m-d', strtotime($start . ' -1 day'));
        $prevStart = date('Y-m-d', strtotime($prevEnd . ' -6 days'));
        
        // Current period stats (only FULLY PAID bookings) - by created_at date
        $currentSql = "SELECT 
                        COUNT(DISTINCT user_id) as unique_guests,
                        COUNT(*) as total_bookings,
                        SUM(number_of_guests) as total_guests,
                        AVG($priceColumn) as avg_booking_value
                      FROM reservations
                      WHERE DATE(created_at) BETWEEN :start AND :end
                      AND status IN ('confirmed', 'checked_in', 'completed')
                      AND full_payment_verified = 1";
        $currentStmt = $conn->prepare($currentSql);
        $currentStmt->execute([':start' => $start, ':end' => $end]);
        $currentStats = $currentStmt->fetch(PDO::FETCH_ASSOC);
        
        // Previous period stats
        $prevStmt = $conn->prepare($currentSql);
        $prevStmt->execute([':start' => $prevStart, ':end' => $prevEnd]);
        $prevStats = $prevStmt->fetch(PDO::FETCH_ASSOC);
        
        $guestStats = [
            'unique_guests' => [
                'current' => (int)($currentStats['unique_guests'] ?? 0),
                'previous' => (int)($prevStats['unique_guests'] ?? 0)
            ],
            'total_bookings' => [
                'current' => (int)($currentStats['total_bookings'] ?? 0),
                'previous' => (int)($prevStats['total_bookings'] ?? 0)
            ],
            'total_guests' => [
                'current' => (int)($currentStats['total_guests'] ?? 0),
                'previous' => (int)($prevStats['total_guests'] ?? 0)
            ],
            'avg_booking_value' => [
                'current' => (float)($currentStats['avg_booking_value'] ?? 0),
                'previous' => (float)($prevStats['avg_booking_value'] ?? 0)
            ]
        ];
    } catch (PDOException $e) {
        // Return empty stats if query fails
    }
    
    // Get performance metrics
    $performanceMetrics = [];
    try {
        // Check-in efficiency: Average time between confirmed and checked_in
        $checkinSql = "SELECT 
                        AVG(TIMESTAMPDIFF(MINUTE, downpayment_verified_at, checked_in_at)) as avg_checkin_time
                      FROM reservations
                      WHERE checked_in = 1 
                        AND checked_in_at IS NOT NULL
                        AND downpayment_verified_at IS NOT NULL
                        AND DATE(created_at) BETWEEN :start AND :end";
        $checkinStmt = $conn->prepare($checkinSql);
        $checkinStmt->execute([':start' => $start, ':end' => $end]);
        $checkinData = $checkinStmt->fetch(PDO::FETCH_ASSOC);
        
        // Response time: Average time between reservation creation and admin verification
        $responseSql = "SELECT 
                        AVG(TIMESTAMPDIFF(MINUTE, created_at, downpayment_verified_at)) as avg_response_time
                      FROM reservations
                      WHERE downpayment_verified = 1
                        AND downpayment_verified_at IS NOT NULL
                        AND DATE(created_at) BETWEEN :start AND :end";
        $responseStmt = $conn->prepare($responseSql);
        $responseStmt->execute([':start' => $start, ':end' => $end]);
        $responseData = $responseStmt->fetch(PDO::FETCH_ASSOC);
        
        $performanceMetrics = [
            'avg_checkin_time' => round((float)($checkinData['avg_checkin_time'] ?? 0)),
            'avg_response_time' => round((float)($responseData['avg_response_time'] ?? 0))
        ];
    } catch (PDOException $e) {
        // Return default values if query fails
        $performanceMetrics = [
            'avg_checkin_time' => 0,
            'avg_response_time' => 0
        ];
    }
    
    echo json_encode([
        'success' => true,
        'period' => $period,
        'start_date' => $start,
        'end_date' => $end,
        'metrics' => [
            'total_reservations' => (int)($metrics['total_reservations'] ?? 0),
            'total_revenue' => $totalRevenue,
            'occupancy_rate' => $occupancyRate,
            'cancellations' => (int)($metrics['cancellations'] ?? 0)
        ],
        'trend_data' => [
            'labels' => $trendLabels,
            'values' => $trendValues
        ],
        'revenue_data' => [
            'labels' => $trendLabels,
            'values' => $revenueValues
        ],
        'room_type_data' => $roomTypeData,
        'guest_stats' => $guestStats,
        'performance_metrics' => $performanceMetrics
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage(), 'trace' => $e->getTraceAsString()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage(), 'trace' => $e->getTraceAsString()]);
}
?>

