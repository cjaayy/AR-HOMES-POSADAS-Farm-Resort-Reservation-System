<?php
/**
 * Staff Dashboard Stats API
 * Returns simplified reservation stats for staff users
 * Supports both admin session and staff-specific session
 */

session_start();
header('Content-Type: application/json');

// Check for either admin session or staff-specific session
$isAdminLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$isStaffLoggedIn = isset($_SESSION['staff_logged_in']) && $_SESSION['staff_logged_in'] === true;

if (!$isAdminLoggedIn && !$isStaffLoggedIn) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../config/connection.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Default values
    $todayReservations = 0;
    $arrivalsToday = 0;
    $checkoutsToday = 0;
    $pendingRequests = 0;

    // Only run queries if reservations table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'reservations'");
    if ($tableCheck->rowCount() > 0) {
        // Today's reservations (created today)
        $q = $conn->prepare("SELECT COUNT(*) AS total FROM reservations WHERE DATE(created_at) = CURDATE()");
        $q->execute();
        $todayReservations = (int)($q->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

        // Arrivals (check-in today, confirmed)
        $q = $conn->prepare("SELECT COUNT(*) AS total FROM reservations WHERE DATE(check_in_date) = CURDATE() AND status IN ('confirmed')");
        $q->execute();
        $arrivalsToday = (int)($q->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

        // Check-outs (check_out_date today and status in confirmed/completed)
        $q = $conn->prepare("SELECT COUNT(*) AS total FROM reservations WHERE DATE(check_out_date) = CURDATE() AND status IN ('confirmed','completed')");
        $q->execute();
        $checkoutsToday = (int)($q->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

        // Pending requests
        $q = $conn->prepare("SELECT COUNT(*) AS total FROM reservations WHERE status = 'pending'");
        $q->execute();
        $pendingRequests = (int)($q->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    echo json_encode([
        'success' => true,
        'stats' => [
            'today_reservations' => $todayReservations,
            'arrivals_today' => $arrivalsToday,
            'checkouts_today' => $checkoutsToday,
            'pending_requests' => $pendingRequests
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: '.$e->getMessage()]);
}

?>

