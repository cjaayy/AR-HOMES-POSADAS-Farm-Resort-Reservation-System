<?php
/**
 * User: Submit Review
 * Submit a new review for a reservation
 */

session_start();
header('Content-Type: application/json');

require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    // Try form data
    $data = $_POST;
}

// Validate required fields
$required = ['reservation_id', 'rating', 'title', 'content'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit;
    }
}

$reservation_id = trim($data['reservation_id']);
$rating = intval($data['rating']);
$title = trim($data['title']);
$content = trim($data['content']);
$user_id = $_SESSION['user_id'];

// Validate reservation_id
if (empty($reservation_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid reservation ID']);
    exit;
}

// Validate rating
if ($rating < 1 || $rating > 5) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5']);
    exit;
}

// Validate title and content length
if (strlen($title) < 3 || strlen($title) > 255) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Title must be between 3 and 255 characters']);
    exit;
}

if (strlen($content) < 10) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Review content must be at least 10 characters']);
    exit;
}

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if reservation exists and belongs to user - get all relevant fields
    $checkSql = "SELECT reservation_id, status, checked_out, checked_in, check_out_date, check_in_date FROM reservations WHERE reservation_id = :res_id AND user_id = :user_id";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->execute(['res_id' => $reservation_id, 'user_id' => $user_id]);
    $reservation = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reservation) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Reservation not found or does not belong to you']);
        exit;
    }
    
    // Check if reservation is completed/checked out (case-insensitive)
    // Also check the checked_out flag as an alternative indicator
    $status = strtolower(trim($reservation['status'] ?? ''));
    $isCheckedOut = !empty($reservation['checked_out']) && ($reservation['checked_out'] == 1 || $reservation['checked_out'] === '1' || $reservation['checked_out'] === true);
    $isCheckedIn = !empty($reservation['checked_in']) && ($reservation['checked_in'] == 1 || $reservation['checked_in'] === '1' || $reservation['checked_in'] === true);
    
    // Allow review if:
    // 1. Status is completed/checked_out (case-insensitive), OR
    // 2. Reservation has been checked out (checked_out = 1), OR
    // 3. Check-out date has passed (regardless of status, if check-out date exists and is in the past)
    $canReview = in_array($status, ['completed', 'checked_out']) || $isCheckedOut;
    
    // Additional check: if check-out date has passed, allow review (for reservations that may not have status updated)
    if (!$canReview && !empty($reservation['check_out_date'])) {
        try {
            $checkOutDate = new DateTime($reservation['check_out_date']);
            $today = new DateTime();
            $today->setTime(0, 0, 0);
            $checkOutDate->setTime(0, 0, 0);
            // Allow review if check-out date is today or in the past
            if ($checkOutDate <= $today) {
                $canReview = true;
                error_log("Review allowed for reservation #{$reservation_id} - check-out date ({$reservation['check_out_date']}) has passed or is today");
            }
        } catch (Exception $e) {
            // Invalid date format, skip this check
            error_log("Invalid check_out_date format for reservation #{$reservation_id}: " . $e->getMessage());
        }
    }
    
    // Also check if check-in date has passed (for same-day bookings where check-out might be same day)
    if (!$canReview && !empty($reservation['check_in_date'])) {
        try {
            $checkInDate = new DateTime($reservation['check_in_date']);
            $today = new DateTime();
            $today->setTime(0, 0, 0);
            $checkInDate->setTime(0, 0, 0);
            // If check-in date has passed, allow review (especially for same-day bookings)
            if ($checkInDate < $today) {
                $canReview = true;
                error_log("Review allowed for reservation #{$reservation_id} - check-in date ({$reservation['check_in_date']}) has passed");
            }
        } catch (Exception $e) {
            // Invalid date format, skip this check
            error_log("Invalid check_in_date format for reservation #{$reservation_id}: " . $e->getMessage());
        }
    }
    
    if (!$canReview) {
        http_response_code(400);
        // Log the actual status for debugging (but don't expose to user)
        error_log("Review submission rejected - Reservation #{$reservation_id} has status: '{$reservation['status']}', checked_out: " . ($isCheckedOut ? '1' : '0') . ", checked_in: " . ($isCheckedIn ? '1' : '0'));
        // Return error with status info for debugging
        echo json_encode([
            'success' => false, 
            'message' => 'You can only review completed reservations',
            'debug' => [
                'status' => $reservation['status'],
                'status_lower' => $status,
                'checked_out' => $isCheckedOut ? '1' : '0',
                'checked_out_raw' => $reservation['checked_out'] ?? 'NULL',
                'checked_in' => $isCheckedIn ? '1' : '0',
                'checked_in_raw' => $reservation['checked_in'] ?? 'NULL',
                'check_out_date' => $reservation['check_out_date'] ?? 'NULL',
                'check_in_date' => $reservation['check_in_date'] ?? 'NULL',
                'today' => date('Y-m-d')
            ]
        ]);
        exit;
    }
    
    // Check if review already exists
    $existsSql = "SELECT review_id FROM reviews WHERE reservation_id = :res_id";
    $existsStmt = $conn->prepare($existsSql);
    $existsStmt->execute(['res_id' => $reservation_id]);
    
    if ($existsStmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'You have already reviewed this reservation']);
        exit;
    }
    
    // Insert the review
    $insertSql = "
        INSERT INTO reviews (user_id, reservation_id, rating, title, content, status, created_at)
        VALUES (:user_id, :reservation_id, :rating, :title, :content, 'active', NOW())
    ";
    
    $insertStmt = $conn->prepare($insertSql);
    $insertStmt->execute([
        'user_id' => $user_id,
        'reservation_id' => $reservation_id,
        'rating' => $rating,
        'title' => $title,
        'content' => $content
    ]);
    
    $review_id = $conn->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Review submitted successfully!',
        'review_id' => $review_id
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>

