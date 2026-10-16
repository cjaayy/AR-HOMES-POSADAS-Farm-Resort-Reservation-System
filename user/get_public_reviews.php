<?php
/**
 * Public: Get Reviews for Display
 * Fetch active reviews for public display (no login required)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get limit parameter (default 10)
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    if ($limit < 1 || $limit > 50) $limit = 10;
    
    // Get active reviews with user info
    $sql = "
        SELECT 
            r.review_id,
            r.rating,
            r.title,
            r.content,
            r.created_at,
            u.full_name as guest_name
        FROM reviews r
        LEFT JOIN users u ON r.user_id = u.user_id
        WHERE r.status = 'active'
        ORDER BY r.created_at DESC
        LIMIT :limit
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate stats
    $statsSql = "
        SELECT 
            COUNT(*) as total_reviews,
            ROUND(AVG(rating), 1) as average_rating
        FROM reviews
        WHERE status = 'active'
    ";
    
    $statsStmt = $conn->prepare($statsSql);
    $statsStmt->execute();
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'reviews' => $reviews,
        'stats' => [
            'total_reviews' => intval($stats['total_reviews']),
            'average_rating' => floatval($stats['average_rating'] ?? 0)
        ]
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error loading reviews']);
}

