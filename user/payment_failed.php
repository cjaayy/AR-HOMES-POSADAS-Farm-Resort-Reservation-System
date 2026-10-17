<?php
/**
 * Payment Failed Page
 * Displayed after failed GCash payment via PayMongo
 */

session_start();

// Get reservation ID from query parameter
$reservation_id = $_GET['reservation_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed - AR Homes Resort</title>
    <link rel="stylesheet" href="../css/styles.css">
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
        
        .failed-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            max-width: 500px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }
        
        .failed-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            animation: shake 0.5s ease-out;
        }
        
        .failed-icon i {
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
        
        .btn-container {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn-primary, .btn-secondary {
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-secondary {
            background: #f5f5f5;
            color: #333;
        }
        
        .btn-primary:hover, .btn-secondary:hover {
            transform: translateY(-2px);
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
    </style>
</head>
<body>
    <div class="failed-container">
        <div class="failed-icon">
            <i class="fas fa-times"></i>
        </div>
        
        <h1>Payment Failed</h1>
        <p>Unfortunately, your payment could not be processed. Your reservation is still pending payment.</p>
        
        <p style="font-size: 14px; color: #888;">
            <?php if ($reservation_id): ?>
                Reservation ID: #<?php echo htmlspecialchars($reservation_id); ?>
            <?php endif; ?>
        </p>
        
        <div class="btn-container">
            <a href="../dashboard" class="btn-secondary">
                <i class="fas fa-home"></i> Go to Dashboard
            </a>
            <?php if ($reservation_id): ?>
                <a href="javascript:retryPayment('<?php echo htmlspecialchars($reservation_id); ?>')" class="btn-primary">
                    <i class="fas fa-redo"></i> Try Again
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function retryPayment(reservationId) {
            // Redirect to dashboard and open payment modal
            window.location.href = '../dashboard?retry_payment=' + reservationId;
        }
    </script>
</body>
</html>

