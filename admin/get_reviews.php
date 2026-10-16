<?php
/**
 * Admin: Get All Reviews
 * Fetch all reviews with user and reservation details
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
    
    // Get filter parameters
    $status = $_GET['status'] ?? 'all';
    $rating = $_GET['rating'] ?? 'all';
    $search = $_GET['search'] ?? '';
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
    
    // Build query
    $sql = "
        SELECT 
            r.review_id,
            r.user_id,
            r.reservation_id,
            r.rating,
            r.title,
            r.content,
            r.helpful_count,
            COALESCE(r.edit_count, 0) as edit_count,
            r.status,
            r.created_at,
            r.updated_at,
            u.full_name as guest_name,
            u.email as guest_email,
            res.guest_phone as guest_phone,
            res.booking_type,
            res.package_type,
            res.check_in_date,
            res.check_out_date,
            res.room
        FROM reviews r
        LEFT JOIN users u ON r.user_id = u.user_id
        LEFT JOIN reservations res ON r.reservation_id = res.reservation_id
        WHERE 1=1
    ";
    
    $params = [];
    
    // Filter by status
    if ($status !== 'all') {
        $sql .= " AND r.status = :status";
        $params['status'] = $status;
    }
    
    // Filter by rating
    if ($rating !== 'all' && is_numeric($rating)) {
        $sql .= " AND r.rating = :rating";
        $params['rating'] = intval($rating);
    }
    
    // Search filter
    if (!empty($search)) {
        $sql .= " AND (u.full_name LIKE :search OR r.title LIKE :search OR r.content LIKE :search)";
        $params['search'] = "%$search%";
    }
    
    $sql .= " ORDER BY r.created_at DESC LIMIT " . $limit;
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate stats
    $statsSql = "
        SELECT 
            COUNT(*) as total_reviews,
            ROUND(AVG(rating), 1) as average_rating,
            SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
            SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
            SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
            SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
            SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_reviews,
            SUM(CASE WHEN status = 'hidden' THEN 1 ELSE 0 END) as hidden_reviews,
            SUM(helpful_count) as total_helpful
        FROM reviews
    ";
    
    $statsStmt = $conn->prepare($statsSql);
    $statsStmt->execute();
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'reviews' => $reviews,
        'stats' => $stats,
        'count' => count($reviews)
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

