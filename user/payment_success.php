<?php
/**
 * Payment Success Page
 * Displayed after successful GCash payment via PayMongo
 */

session_start();
require_once '../config/database.php';
require_once '../config/paymongo.php';

// Get reservation ID from query parameter
$reservation_id = $_GET['reservation_id'] ?? null;

if (!$reservation_id) {
    header('Location: ../dashboard.html');
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
    
    $req_payment_type = $_GET['payment_type'] ?? '';
    $link_id = null;
    $is_full_payment_flow = false;

    if ($req_payment_type === 'full_payment' && !empty($reservation['paymongo_full_payment_link_id'])) {
        $link_id = $reservation['paymongo_full_payment_link_id'];
        $is_full_payment_flow = true;
    } else {
        $link_id = $reservation['paymongo_source_id'] ?: $reservation['paymongo_full_payment_link_id'];
        if ($link_id === $reservation['paymongo_full_payment_link_id']) {
            $is_full_payment_flow = true;
        }
    }

    if ($link_id) {
        $endpoint = (strpos($link_id, 'cs_') === 0) ? ('/checkout_sessions/' . $link_id) : ('/links/' . $link_id);
        $result = makePaymongoRequest($endpoint, 'GET');
        
        if ($result['success']) {
            $link = $result['data']['data'];
            $payments = $link['attributes']['payments'] ?? [];
            
            if (!empty($payments)) {
                $payment = $payments[0]['data'] ?? $payments[0];
                $payment_id = $payment['id'] ?? null;
                $payment_status = $payment['attributes']['status'] ?? '';
                
                $payment_method = 'gcash';
                $source = $payment['attributes']['source'] ?? null;
                if ($source && isset($source['type'])) {
                    $payment_method = strtolower($source['type']);
                }
                
                if ($payment_status === 'paid') {
                    if ($is_full_payment_flow) {
                        $stmt = $pdo->prepare("
                            UPDATE reservations 
                            SET full_payment_paid = 1,
                                full_payment_reference = ?,
                                full_payment_paid_at = NOW(),
                                full_payment_verified = 1,
                                full_payment_verified_at = NOW(),
                                payment_method = ?,
                                status = 'confirmed',
                                updated_at = NOW()
                            WHERE reservation_id = ?
                        ");
                        $stmt->execute([$payment_id, $payment_method, $reservation_id]);
                    } else {
                        $stmt = $pdo->prepare("
                            UPDATE reservations 
                            SET status = 'pending_confirmation',
                                paymongo_payment_id = ?,
                                payment_method = ?,
                                downpayment_paid = 1,
                                downpayment_reference = ?,
                                downpayment_paid_at = NOW(),
                                downpayment_verified = 0,
                                updated_at = NOW()
                            WHERE reservation_id = ?
                        ");
                        $stmt->execute([$payment_id, $payment_method, $payment_id, $reservation_id]);
                    }
                }
            }
        }
    }

    // Refresh reservation data
    $stmt = $pdo->prepare("SELECT * FROM reservations WHERE reservation_id = ?");
    $stmt->execute([$reservation_id]);
    $reservation = $stmt->fetch();

    $totalPrice = floatval($reservation['total_price'] ?? 0);
    $downpaymentPaid = floatval($reservation['downpayment_amount'] ?? 1000);
    $isFullyPaid = !empty($reservation['full_payment_paid']) && $reservation['full_payment_paid'] == 1;
    $remainingBalance = $isFullyPaid ? 0 : max(0, $totalPrice - $downpaymentPaid);

} catch (Exception $e) {
    error_log('Payment Success Error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - AR Homes Posadas Farm Resort</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #0a0e17;
            --card-bg: rgba(15, 23, 42, 0.92);
            --gold: #facc15;
            --gold-glow: rgba(250, 204, 21, 0.25);
            --border: rgba(255, 255, 255, 0.12);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --success: #10b981;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        body {
            background: radial-gradient(circle at 50% 20%, #1e293b 0%, #0a0e17 80%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            color: var(--text-main);
        }
        .success-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 40px;
            max-width: 540px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6), 0 0 40px var(--gold-glow);
            backdrop-filter: blur(16px);
            animation: fadeIn 0.4s ease-out;
        }
        .success-badge-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.2), rgba(16, 185, 129, 0.05));
            border: 2px solid var(--success);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: var(--success);
            font-size: 34px;
            box-shadow: 0 0 25px rgba(16, 185, 129, 0.35);
        }
        .success-card h1 {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            color: var(--gold);
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }
        .subtitle {
            color: var(--text-muted);
            font-size: 14px;
            margin-bottom: 24px;
            line-height: 1.5;
        }
        .details-box {
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 18px;
            margin-bottom: 24px;
            text-align: left;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            font-size: 14px;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .detail-label i {
            color: var(--gold);
            font-size: 13px;
        }
        .detail-value {
            color: var(--text-main);
            font-weight: 600;
        }
        .detail-value.highlight {
            color: var(--gold);
            font-size: 16px;
        }
        .detail-value.success {
            color: var(--success);
        }
        .btn-stack {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 10px;
        }
        .btn-gold {
            background: linear-gradient(135deg, #facc15 0%, #ca8a04 100%);
            color: #0b1120;
            font-weight: 700;
            font-size: 15px;
            padding: 14px 24px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.25s ease;
            box-shadow: 0 4px 15px rgba(250, 204, 21, 0.3);
        }
        .btn-gold:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(250, 204, 21, 0.45);
        }
        .btn-secondary {
            background: rgba(255, 255, 255, 0.06);
            color: var(--text-main);
            font-weight: 600;
            font-size: 14px;
            padding: 12px 20px;
            border-radius: 10px;
            border: 1px solid var(--border);
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s ease;
        }
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.12);
            color: var(--gold);
        }
        .footer-note {
            margin-top: 20px;
            font-size: 12px;
            color: var(--text-muted);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="success-card">
        <div class="success-badge-icon">
            <i class="fas fa-check"></i>
        </div>
        
        <h1><?php echo $isFullyPaid ? 'Full Payment Complete' : 'Reservation Fee Paid!'; ?></h1>
        <p class="subtitle">
            <?php echo $isFullyPaid 
                ? 'Your full reservation payment has been confirmed. We look forward to hosting you at AR Homes Posadas Farm Resort!' 
                : 'Your ₱1,000 reservation fee has been received. You can now pay your remaining balance below or later in your dashboard.'; ?>
        </p>
        
        <div class="details-box">
            <div class="detail-row">
                <span class="detail-label"><i class="fas fa-hashtag"></i> Reservation ID</span>
                <span class="detail-value">#<?php echo htmlspecialchars($reservation_id); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label"><i class="fas fa-calendar-check"></i> Check-in Date</span>
                <span class="detail-value"><?php echo !empty($reservation['check_in_date']) ? date('F d, Y', strtotime($reservation['check_in_date'])) : 'Confirmed'; ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label"><i class="fas fa-money-bill-wave"></i> Total Price</span>
                <span class="detail-value">₱<?php echo number_format($totalPrice, 2); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label"><i class="fas fa-receipt"></i> Amount Paid</span>
                <span class="detail-value success">₱<?php echo number_format($isFullyPaid ? $totalPrice : $downpaymentPaid, 2); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label"><i class="fas fa-wallet"></i> Remaining Balance</span>
                <span class="detail-value highlight">₱<?php echo number_format($remainingBalance, 2); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label"><i class="fas fa-info-circle"></i> Status</span>
                <span class="detail-value success"><?php echo $isFullyPaid ? 'Fully Paid' : 'Reserved (Pending Balance)'; ?></span>
            </div>
        </div>

        <div class="btn-stack">
            <?php if (!$isFullyPaid && $remainingBalance > 0): ?>
            <button id="payFullBtn" class="btn-gold" onclick="payFullBalanceNow()">
                <i class="fas fa-credit-card"></i> Pay Remaining Balance in Full (₱<?php echo number_format($remainingBalance, 2); ?>)
            </button>
            <?php endif; ?>

            <a href="../dashboard?section=my-bookings" class="btn-secondary">
                <i class="fas fa-columns"></i> View My Reservations &amp; Booking Details
            </a>
        </div>

        <p class="footer-note">
            <i class="fas fa-shield-alt"></i> Official receipt and reservation details have been recorded securely.
        </p>
    </div>

    <script>
        async function payFullBalanceNow() {
            const btn = document.getElementById('payFullBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Initializing PayMongo Checkout...';
            
            try {
                const res = await fetch('create_payment_intent.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        reservation_id: '<?php echo $reservation_id; ?>',
                        payment_type: 'full_payment'
                    })
                });

                const data = await res.json();
                if (data.success && data.checkout_url) {
                    window.location.href = data.checkout_url;
                } else {
                    alert(data.message || 'Unable to proceed with full payment. Please try from your dashboard.');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-credit-card"></i> Pay Remaining Balance in Full (₱<?php echo number_format($remainingBalance, 2); ?>)';
                }
            } catch (err) {
                alert('Connection error. Please try paying from the My Reservations section in your dashboard.');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-credit-card"></i> Pay Remaining Balance in Full (₱<?php echo number_format($remainingBalance, 2); ?>)';
            }
        }
    </script>
</body>
</html>

