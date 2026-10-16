<?php
require_once '../config/connection.php';

$db = new Database();
$conn = $db->getConnection();

echo "Reservations Payment Status Check\n";
echo str_repeat("=", 80) . "\n\n";

// Get all reservations from last week
$sql = "SELECT 
    reservation_id,
    guest_name,
    package_type,
    check_in_date,
    status,
    total_amount,
    downpayment_verified,
    full_payment_verified,
    created_at
FROM reservations 
WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
ORDER BY created_at DESC";

$stmt = $conn->query($sql);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($reservations) > 0) {
    echo "Found " . count($reservations) . " reservations from the last week:\n\n";
    
    foreach ($reservations as $res) {
        $downpayment = $res['downpayment_verified'] ? '✓ VERIFIED' : '✗ NOT VERIFIED';
        $fullPayment = $res['full_payment_verified'] ? '✓ VERIFIED' : '✗ NOT VERIFIED';
        $showInReports = $res['downpayment_verified'] ? '✓ SHOWS IN REPORTS' : '✗ HIDDEN FROM REPORTS';
        
        echo str_repeat("-", 80) . "\n";
        echo "Reservation ID: {$res['reservation_id']}\n";
        echo "Guest: {$res['guest_name']}\n";
        echo "Package: {$res['package_type']}\n";
        echo "Check-in: {$res['check_in_date']}\n";
        echo "Status: {$res['status']}\n";
        echo "Amount: ₱{$res['total_amount']}\n";
        echo "Downpayment: {$downpayment}\n";
        echo "Full Payment: {$fullPayment}\n";
        echo "Reports Status: {$showInReports}\n";
        echo "Created: {$res['created_at']}\n";
    }
    
    echo "\n" . str_repeat("=", 80) . "\n";
    
    // Count visible vs hidden
    $verified = array_filter($reservations, function($r) { return $r['downpayment_verified'] == 1; });
    $unverified = array_filter($reservations, function($r) { return $r['downpayment_verified'] == 0; });
    
    echo "\nSUMMARY:\n";
    echo "  Showing in Reports: " . count($verified) . " reservations\n";
    echo "  Hidden from Reports: " . count($unverified) . " reservations (unpaid/unverified)\n";
    
} else {
    echo "No reservations found in the last week.\n";
}
?>

