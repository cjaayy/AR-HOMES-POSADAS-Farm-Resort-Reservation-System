<?php
/**
 * User: Update Review
 * Update an existing review
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
    $data = $_POST;
}

// Validate required fields
$required = ['review_id', 'rating', 'title', 'content'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit;
    }
}

$review_id = intval($data['review_id']);
$rating = intval($data['rating']);
$title = trim($data['title']);
$content = trim($data['content']);
$user_id = $_SESSION['user_id'];

// Validate review_id
if ($review_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid review ID']);
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
    
    // Check if review exists and belongs to user, also get edit count
    $checkSql = "SELECT review_id, COALESCE(edit_count, 0) as edit_count FROM reviews WHERE review_id = :review_id AND user_id = :user_id AND status = 'active'";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->execute(['review_id' => $review_id, 'user_id' => $user_id]);
    
    $review = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$review) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Review not found or does not belong to you']);
        exit;
    }
    
    // Check if edit limit reached (maximum 3 edits allowed)
    if ($review['edit_count'] >= 3) {
        http_response_code(403);
        echo json_encode([
            'success' => false, 
            'message' => 'You have reached the maximum number of edits (3) for this review.',
            'edit_count' => $review['edit_count']
        ]);
        exit;
    }
    
    // Update the review and increment edit count
    $updateSql = "
        UPDATE reviews 
        SET rating = :rating, title = :title, content = :content, 
            edit_count = COALESCE(edit_count, 0) + 1, updated_at = NOW()
        WHERE review_id = :review_id AND user_id = :user_id
    ";
    
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->execute([
        'rating' => $rating,
        'title' => $title,
        'content' => $content,
        'review_id' => $review_id,
        'user_id' => $user_id
    ]);
    
    $new_edit_count = $review['edit_count'] + 1;
    $edits_remaining = 3 - $new_edit_count;
    
    echo json_encode([
        'success' => true,
        'message' => 'Review updated successfully!' . ($edits_remaining > 0 ? " You have $edits_remaining edit(s) remaining." : ' This was your last edit.'),
        'edit_count' => $new_edit_count,
        'edits_remaining' => $edits_remaining
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>

