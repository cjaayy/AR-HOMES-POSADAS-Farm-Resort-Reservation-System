<?php
/**
 * User: Delete Review
 * Soft delete a review (set status to 'deleted')
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

if (empty($data['review_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing review_id']);
    exit;
}

$review_id = intval($data['review_id']);
$user_id = $_SESSION['user_id'];

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if review exists and belongs to user
    $checkSql = "SELECT review_id FROM reviews WHERE review_id = :review_id AND user_id = :user_id AND status = 'active'";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->execute(['review_id' => $review_id, 'user_id' => $user_id]);
    
    if (!$checkStmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Review not found or does not belong to you']);
        exit;
    }
    
    // Soft delete the review
    $deleteSql = "UPDATE reviews SET status = 'deleted', updated_at = NOW() WHERE review_id = :review_id AND user_id = :user_id";
    $deleteStmt = $conn->prepare($deleteSql);
    $deleteStmt->execute(['review_id' => $review_id, 'user_id' => $user_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Review deleted successfully!'
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>

