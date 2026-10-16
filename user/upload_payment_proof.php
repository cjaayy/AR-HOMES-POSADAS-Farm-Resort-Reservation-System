<?php
/**
 * Upload Payment Proof
 * Handles payment screenshot/proof upload for downpayment or full payment
 */

session_start();
header('Content-Type: application/json');

require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    $reservation_id = $_POST['reservation_id'] ?? null;
    $payment_type = $_POST['payment_type'] ?? 'downpayment'; // 'downpayment' or 'full_payment'
    $payment_method = $_POST['payment_method'] ?? null;
    $reference_number = $_POST['reference_number'] ?? null;
    
    if (!$reservation_id || !$payment_method || !$reference_number) {
        throw new Exception('Reservation ID, payment method, and reference number are required');
    }
    
    // Verify reservation belongs to user
    $stmt = $pdo->prepare("SELECT user_id, status FROM reservations WHERE reservation_id = :id");
    $stmt->execute([':id' => $reservation_id]);
    $reservation = $stmt->fetch();
    
    if (!$reservation || $reservation['user_id'] != $_SESSION['user_id']) {
        throw new Exception('Invalid reservation');
    }
    
    // Check if file was uploaded
    if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Please upload payment proof (screenshot)');
    }
    
    $file = $_FILES['payment_proof'];
    
    // Validate file type
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'];
    if (!in_array($file['type'], $allowed_types)) {
        throw new Exception('Invalid file type. Please upload JPG, PNG, GIF, or PDF');
    }
    
    // Validate file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('File too large. Maximum size is 5MB');
    }
    
    // Create upload directory if not exists
    $upload_dir = '../uploads/payment_proofs/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'payment_' . $reservation_id . '_' . $payment_type . '_' . time() . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Failed to upload file');
    }
    
    // Update database based on payment type
    if ($payment_type === 'downpayment') {
        $stmt = $pdo->prepare("
            UPDATE reservations 
            SET downpayment_proof = :proof,
                downpayment_reference = :ref,
                payment_method = :method,
                downpayment_paid = 1,
                downpayment_paid_at = NOW(),
                status = 'pending_confirmation',
                updated_at = NOW()
            WHERE reservation_id = :id
        ");
    } else {
        $stmt = $pdo->prepare("
            UPDATE reservations 
            SET full_payment_proof = :proof,
                full_payment_reference = :ref,
                full_payment_paid = 1,
                full_payment_paid_at = NOW(),
                updated_at = NOW()
            WHERE reservation_id = :id
        ");
    }
    
    $stmt->execute([
        ':proof' => $filename,
        ':ref' => $reference_number,
        ':method' => $payment_method,
        ':id' => $reservation_id
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => ucfirst($payment_type) . ' proof uploaded successfully! Waiting for admin verification.',
        'filename' => $filename,
        'status' => $payment_type === 'downpayment' ? 'pending_confirmation' : $reservation['status']
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

