<?php
/**
 * Get Current User Session Data API
 * Returns user data for dashboard.html
 * 
 * This fetches fresh data from the database to ensure profile shows
 * the latest information (especially after admin updates)
 */

session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Not authenticated',
        'authenticated' => false
    ]);
    exit;
}

// Check session timeout (1 hour)
$timeout = 3600;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
    // Session has expired
    session_unset();
    session_destroy();
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Session expired',
        'authenticated' => false
    ]);
    exit;
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Include database configuration
require_once '../config/database.php';

// Default values
$memberSince = date('Y');
$totalBookings = 0;
$reviewsGiven = 0;

// User data (will be refreshed from database)
$userData = [
    'user_id' => $_SESSION['user_id'] ?? null,
    'username' => $_SESSION['user_username'] ?? '',
    'email' => $_SESSION['user_email'] ?? '',
    'full_name' => $_SESSION['user_full_name'] ?? 'Guest',
    'given_name' => $_SESSION['user_given_name'] ?? 'Guest',
    'last_name' => $_SESSION['user_last_name'] ?? '',
    'middle_name' => $_SESSION['user_middle_name'] ?? '',
    'phone_number' => $_SESSION['user_phone'] ?? '',
    'loyalty_level' => 'Regular'
];

// Fetch fresh user data from database
try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $user_id = $_SESSION['user_id'] ?? null;
    
    if ($user_id) {
        // Get fresh user data from database (in case admin updated it)
        $userStmt = $conn->prepare("
            SELECT user_id, username, email, full_name, given_name, last_name, middle_name, 
                   phone_number, loyalty_level, created_at, pending_email
            FROM users 
            WHERE user_id = :user_id 
            LIMIT 1
        ");
        $userStmt->bindParam(':user_id', $user_id, PDO::PARAM_STR);
        $userStmt->execute();
        $freshUserData = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($freshUserData) {
            // Update session with fresh data
            $_SESSION['user_username'] = $freshUserData['username'];
            $_SESSION['user_email'] = $freshUserData['email'];
            $_SESSION['user_full_name'] = $freshUserData['full_name'];
            $_SESSION['user_given_name'] = $freshUserData['given_name'];
            $_SESSION['user_last_name'] = $freshUserData['last_name'];
            $_SESSION['user_middle_name'] = $freshUserData['middle_name'];
            $_SESSION['user_phone'] = $freshUserData['phone_number'];
            
            // Update userData array
            $userData = [
                'user_id' => $freshUserData['user_id'],
                'username' => $freshUserData['username'],
                'email' => $freshUserData['email'],
                'full_name' => $freshUserData['full_name'],
                'given_name' => $freshUserData['given_name'],
                'last_name' => $freshUserData['last_name'],
                'middle_name' => $freshUserData['middle_name'],
                'phone_number' => $freshUserData['phone_number'],
                'loyalty_level' => $freshUserData['loyalty_level'] ?? 'Regular',
                'pending_email' => $freshUserData['pending_email'] ?? null
            ];
            
            // Get member since year
            if (!empty($freshUserData['created_at'])) {
                $memberSince = date('Y', strtotime($freshUserData['created_at']));
            }
        }
        
        $user_email = $userData['email'];
        
        // Get total bookings count (by user_id or email)
        // Excludes pending_payment without verified downpayment (same as dashboard)
        $bookingsStmt = $conn->prepare("
            SELECT COUNT(*) as total 
            FROM reservations 
            WHERE (user_id = :user_id OR guest_email = :email)
            AND NOT (status = 'pending_payment' AND (downpayment_verified = 0 OR downpayment_verified IS NULL))
        ");
        $bookingsStmt->bindParam(':user_id', $user_id, PDO::PARAM_STR);
        $bookingsStmt->bindParam(':email', $user_email, PDO::PARAM_STR);
        $bookingsStmt->execute();
        $bookingsData = $bookingsStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($bookingsData) {
            $totalBookings = (int)$bookingsData['total'];
        }
        
        // Get total reviews given
        $reviewsStmt = $conn->prepare("
            SELECT COUNT(*) as total 
            FROM reviews 
            WHERE user_id = :user_id
        ");
        $reviewsStmt->bindParam(':user_id', $user_id, PDO::PARAM_STR);
        $reviewsStmt->execute();
        $reviewsData = $reviewsStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($reviewsData) {
            $reviewsGiven = (int)$reviewsData['total'];
        }
    }
} catch (PDOException $e) {
    // Log error but don't break the response
    error_log("Error fetching user stats: " . $e->getMessage());
}

echo json_encode([
    'success' => true,
    'authenticated' => true,
    'data' => [
        'user_id' => $userData['user_id'],
        'username' => $userData['username'],
        'email' => $userData['email'],
        'full_name' => $userData['full_name'],
        'given_name' => $userData['given_name'],
        'last_name' => $userData['last_name'],
        'middle_name' => $userData['middle_name'],
        'phone_number' => $userData['phone_number'],
        'memberSince' => $memberSince,
        'totalBookings' => $totalBookings,
        'reviewsGiven' => $reviewsGiven,
        'loyaltyLevel' => $userData['loyalty_level'],
        'pending_email' => $userData['pending_email'] ?? null
    ]
]);

