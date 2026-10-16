<?php
/**
 * Fix Reservation Prices - Calculate and update total_price for all reservations
 */
session_start();
header('Content-Type: application/json');

// Allow admin and super_admin roles only
$allowedRoles = ['admin', 'super_admin'];
$userRole = $_SESSION['admin_role'] ?? '';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || !in_array($userRole, $allowedRoles)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../config/connection.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // First, check what columns exist in the reservations table
    $columnsQuery = $conn->query("SHOW COLUMNS FROM reservations");
    $columns = $columnsQuery->fetchAll(PDO::FETCH_COLUMN);
    
    // Determine which price column to use
    $priceColumn = null;
    $possiblePriceColumns = ['total_price', 'total_amount', 'amount', 'price', 'total_cost'];
    foreach ($possiblePriceColumns as $col) {
        if (in_array($col, $columns)) {
            $priceColumn = $col;
            break;
        }
    }
    
    if (!$priceColumn) {
        echo json_encode([
            'success' => false,
            'message' => 'No price column found in reservations table. Available columns: ' . implode(', ', $columns)
        ]);
        exit;
    }
    
    // Check for room type column
    $roomTypeColumn = null;
    $possibleRoomColumns = ['room_type', 'room_name', 'accommodation_type', 'room'];
    foreach ($possibleRoomColumns as $col) {
        if (in_array($col, $columns)) {
            $roomTypeColumn = $col;
            break;
        }
    }
    
    // Build SELECT query based on available columns
    $selectFields = "r.reservation_id, r.check_in_date, r.check_out_date, r.$priceColumn as total_price, r.status";
    if ($roomTypeColumn) {
        $selectFields .= ", r.$roomTypeColumn as room_type";
    }
    
    // Get all reservations
    $sql = "SELECT $selectFields FROM reservations r ORDER BY r.created_at DESC";
    
    $stmt = $conn->query($sql);
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Define room rates (adjust these based on your actual rates)
    $roomRates = [
        'Standard Room' => 1500,
        'Deluxe Room' => 2500,
        'Family Room' => 3500,
        'Suite' => 5000,
        'Villa' => 8000,
        'Cottage' => 2000,
        'Bungalow' => 2500
    ];
    
    $defaultRate = 2000; // Default rate if room type not found
    $updated = 0;
    $skipped = 0;
    $errors = [];
    
    foreach ($reservations as $reservation) {
        // Skip if already has a valid total_price
        if ($reservation['total_price'] > 0) {
            $skipped++;
            continue;
        }
        
        // Calculate number of nights
        $checkIn = new DateTime($reservation['check_in_date']);
        $checkOut = new DateTime($reservation['check_out_date']);
        $nights = $checkIn->diff($checkOut)->days;
        
        if ($nights <= 0) {
            $nights = 1; // Minimum 1 night
        }
        
        // Get room rate
        $roomType = $reservation['room_type'] ?? 'Standard';
        $rate = $defaultRate;
        
        if ($roomTypeColumn) {
            foreach ($roomRates as $type => $price) {
                if (stripos($roomType, $type) !== false || stripos($type, $roomType) !== false) {
                    $rate = $price;
                    break;
                }
            }
        }
        
        // Calculate total price
        $totalPrice = $rate * $nights;
        
        // Update reservation
        try {
            $updateSql = "UPDATE reservations 
                         SET $priceColumn = :total_price 
                         WHERE reservation_id = :reservation_id";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->execute([
                ':total_price' => $totalPrice,
                ':reservation_id' => $reservation['reservation_id']
            ]);
            $updated++;
        } catch (PDOException $e) {
            $errors[] = [
                'reservation_id' => $reservation['reservation_id'],
                'error' => $e->getMessage()
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Price fix completed using column: $priceColumn",
        'price_column_used' => $priceColumn,
        'stats' => [
            'total_reservations' => count($reservations),
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => count($errors)
        ],
        'error_details' => $errors
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>

