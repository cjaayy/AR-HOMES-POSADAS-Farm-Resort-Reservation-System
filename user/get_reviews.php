<?php
/**
 * User: Get My Reviews
 * Fetch all reviews for logged-in user
 */

session_start();
header('Content-Type: application/json');

require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $user_id = $_SESSION['user_id'];
    
    // Get all reviews for the user
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
            res.booking_type,
            res.package_type,
            res.check_in_date,
            res.check_out_date
        FROM reviews r
        LEFT JOIN reservations res ON r.reservation_id = res.reservation_id
        WHERE r.user_id = :user_id AND r.status = 'active'
        ORDER BY r.created_at DESC
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute(['user_id' => $user_id]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate stats
    $totalReviews = count($reviews);
    $totalRating = 0;
    $totalHelpful = 0;
    
    foreach ($reviews as $review) {
        $totalRating += $review['rating'];
        $totalHelpful += $review['helpful_count'];
    }
    
    $averageRating = $totalReviews > 0 ? round($totalRating / $totalReviews, 1) : 0;
    
    // Get reservations that can be reviewed (completed and not yet reviewed)
    $sqlReviewable = "
        SELECT 
            res.reservation_id,
            res.booking_type,
            res.package_type,
            res.check_in_date,
            res.check_out_date,
            res.status
        FROM reservations res
        LEFT JOIN reviews r ON res.reservation_id = r.reservation_id
        WHERE res.user_id = :user_id 
        AND res.status IN ('completed', 'checked_out')
        AND r.review_id IS NULL
        ORDER BY res.check_out_date DESC
    ";
    
    $stmtReviewable = $conn->prepare($sqlReviewable);
    $stmtReviewable->execute(['user_id' => $user_id]);
    $reviewableReservations = $stmtReviewable->fetchAll(PDO::FETCH_ASSOC);
    
    // Get reservation IDs that have been reviewed (for hiding Review button in history)
    // Include ACTIVE reviews only for the map
    $reviewedIds = array_map(function($review) {
        return $review['reservation_id']; // Keep as string, not int
    }, $reviews);
    
    // Also get ALL reviewed reservation IDs including deleted ones (to prevent re-reviewing)
    $allReviewedSql = "SELECT reservation_id, status FROM reviews WHERE user_id = :user_id";
    $allReviewedStmt = $conn->prepare($allReviewedSql);
    $allReviewedStmt->execute(['user_id' => $user_id]);
    $allReviewedResults = $allReviewedStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $allReviewedIds = array_map(function($r) {
        return $r['reservation_id'];
    }, $allReviewedResults);
    
    // Create a map of reservation_id => review info for the history cards
    $reviewedReservationsMap = [];
    foreach ($reviews as $review) {
        $reviewedReservationsMap[$review['reservation_id']] = [
            'review_id' => $review['review_id'],
            'edit_count' => intval($review['edit_count']),
            'can_edit' => intval($review['edit_count']) < 3,
            'status' => 'active'
        ];
    }
    
    // Add deleted reviews to the map (not editable)
    foreach ($allReviewedResults as $r) {
        if ($r['status'] === 'deleted' && !isset($reviewedReservationsMap[$r['reservation_id']])) {
            $reviewedReservationsMap[$r['reservation_id']] = [
                'review_id' => null,
                'edit_count' => 0,
                'can_edit' => false,
                'status' => 'deleted'
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'reviews' => $reviews,
        'stats' => [
            'total_reviews' => $totalReviews,
            'average_rating' => $averageRating,
            'total_helpful' => $totalHelpful
        ],
        'reviewable_reservations' => $reviewableReservations,
        'reviewed_reservation_ids' => $reviewedIds,
        'reviewed_reservations_map' => $reviewedReservationsMap
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>

