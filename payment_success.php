<?php
/**
 * Payment Success Page
 * Displayed after successful GCash payment via PayMongo
 */

session_start();
require_once 'config/database.php';
require_once 'config/paymongo.php';

// Get reservation ID from query parameter
$reservation_id = $_GET['reservation_id'] ?? null;

if (!$reservation_id) {
    header('Location: dashboard.html');
    exit;
}

// Verify payment status
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    $stmt = $pdo->prepare("SELECT * FROM reservations WHERE reservation_id = ?");
    $stmt->execute([$reservation_id]);
    $reservation = $stmt->fetch();
    
    if (!$reservation) {
        throw new Exception('Reservation not found');
    }
    
    // Get link/source status from PayMongo
    $link_id = $reservation['paymongo_source_id'];
    if ($link_id) {
        // Try to get payment link status
        $result = makePaymongoRequest('/links/' . $link_id, 'GET');
        
        if ($result['success']) {
            $link = $result['data']['data'];
            $payments = $link['attributes']['payments'] ?? [];
            
            // Check if there are any successful payments
            if (!empty($payments)) {
                $payment = $payments[0]; // Get the first (most recent) payment
                $payment_id = $payment['id'];
                $payment_status = $payment['attributes']['status'];
                
                // Extract payment method from payment data
                $payment_method = 'gcash'; // default
                $source = $payment['attributes']['source'] ?? null;
                if ($source && isset($source['type'])) {
                    $payment_method = strtolower($source['type']);
                }
                
                // Update reservation if payment is successful
                if ($payment_status === 'paid') {
                    // Update reservation with payment ID and status - pending admin confirmation
                    $stmt = $pdo->prepare("
                        UPDATE reservations 
                        SET status = 'pending_confirmation',
                            paymongo_payment_id = ?,
                            payment_method = ?,
                            downpayment_paid = 1,
                            downpayment_reference = ?,
                            downpayment_paid_at = NOW(),
                            downpayment_verified = 0
                        WHERE reservation_id = ?
                    ");
                    $stmt->execute([$payment_id, $payment_method, $payment_id, $reservation_id]);
                }
            }
        } else {
            // Fallback: Try sources API for backwards compatibility
            $result = makePaymongoRequest('/sources/' . $link_id, 'GET');
            
            if ($result['success']) {
                $source = $result['data']['data'];
                $payment_status = $source['attributes']['status'];
                
                // Update reservation if payment is successful
                if ($payment_status === 'chargeable' || $payment_status === 'paid') {
                    // Create payment from the source
                    $payment_data = [
                        'data' => [
                            'attributes' => [
                                'amount' => (int)($reservation['downpayment_amount'] * 100),
                                'currency' => 'PHP',
                                'source' => [
                                    'id' => $link_id,
                                    'type' => 'source'
                                ]
                            ]
                        ]
                    ];
                    
                    $payment_result = makePaymongoRequest('/payments', 'POST', $payment_data);
                    
                    if ($payment_result['success']) {
                        $payment = $payment_result['data']['data'];
                        $payment_id = $payment['id'];
                        
                        // Update reservation with payment ID and status - pending admin confirmation
                        $stmt = $pdo->prepare("
                            UPDATE reservations 
                            SET status = 'pending_confirmation',
                                paymongo_payment_id = ?,
                                payment_method = 'gcash',
                                downpayment_paid = 1,
                                downpayment_reference = ?,
                                downpayment_paid_at = NOW(),
                                downpayment_verified = 0
                            WHERE reservation_id = ?
                        ");
                        $stmt->execute([$payment_id, $payment_id, $reservation_id]);
                    }
                }
            }
        }
    }
    
} catch (Exception $e) {
    error_log('Payment Success Error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - AR Homes Resort</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        
        .success-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            max-width: 500px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }
        
        .success-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #4CAF50, #45a049);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            animation: scaleIn 0.5s ease-out;
        }
        
        .success-icon i {
            font-size: 50px;
            color: white;
        }
        
        h1 {
            color: #333;
            margin-bottom: 15px;
            font-size: 28px;
        }
        
        p {
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .reservation-details {
            background: #f5f5f5;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: left;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #ddd;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 600;
            color: #555;
        }
        
        .detail-value {
            color: #333;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
        }
        
        @keyframes scaleIn {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-icon">
            <i class="fas fa-check"></i>
        </div>
        
        <h1>Payment Successful!</h1>
        <p>Your reservation has been confirmed. Thank you for choosing AR Homes Posadas Farm Resort!</p>
        
        <div class="reservation-details">
            <div class="detail-row">
                <span class="detail-label">Reservation ID:</span>
                <span class="detail-value">#<?php echo htmlspecialchars($reservation_id); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Amount Paid:</span>
                <span class="detail-value">₱<?php echo number_format($reservation['downpayment_amount'], 2); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Check-in Date:</span>
                <span class="detail-value"><?php echo date('F d, Y', strtotime($reservation['check_in_date'])); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Status:</span>
                <span class="detail-value">Confirmed</span>
            </div>
        </div>
        
        <p style="font-size: 14px; color: #888;">
            A confirmation email has been sent to your registered email address.
        </p>
        
        <a href="dashboard.html" class="btn-primary">
            <i class="fas fa-home"></i> Go to Dashboard
        </a>
    </div>
</body>
</html>
