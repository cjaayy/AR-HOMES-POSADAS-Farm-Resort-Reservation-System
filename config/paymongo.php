<?php
/**
 * PayMongo Configuration
 * Get your API keys from https://dashboard.paymongo.com/developers
 */

// Load environment variables from .env file
if (file_exists(__DIR__ . '/../.env')) {
    $envFile = file_get_contents(__DIR__ . '/../.env');
    $lines = explode("\n", $envFile);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

// PayMongo API Keys
// For security, store these in environment variables or a separate config file not tracked by git
define('PAYMONGO_SECRET_KEY', getenv('PAYMONGO_SECRET_KEY') ?: ''); // Your test secret key
define('PAYMONGO_PUBLIC_KEY', getenv('PAYMONGO_PUBLIC_KEY') ?: ''); // Your test public key

// PayMongo API Base URL
define('PAYMONGO_API_URL', 'https://api.paymongo.com/v1');

// Payment configuration
define('PAYMONGO_CURRENCY', 'PHP');
define('PAYMONGO_STATEMENT_DESCRIPTOR', 'AR Homes Resort');

// Webhook secret (for verifying webhook events)
define('PAYMONGO_WEBHOOK_SECRET', 'whsec_YOUR_WEBHOOK_SECRET_HERE');

// Custom PayMongo Payment Page URL
define('PAYMONGO_CHECKOUT_URL', 'https://pm.link/org-TsVFBrzU4hxjqPiivG5o2eJy/DB8nCNS');

// Return URLs (adjust based on your domain)
// For local development - Since DocumentRoot points to project folder, use root-relative paths
define('PAYMONGO_SUCCESS_URL', 'http://localhost/user/payment_success.php');
define('PAYMONGO_FAILED_URL', 'http://localhost/user/payment_failed.php');

// For production, uncomment and update these:
// define('PAYMONGO_SUCCESS_URL', 'https://yourdomain.com/user/payment_success.php');
// define('PAYMONGO_FAILED_URL', 'https://yourdomain.com/user/payment_failed.php');

/**
 * Get PayMongo Authorization Header
 */
function getPaymongoAuthHeader() {
    return 'Basic ' . base64_encode(PAYMONGO_SECRET_KEY . ':');
}

/**
 * Make PayMongo API Request
 */
function makePaymongoRequest($endpoint, $method = 'GET', $data = null) {
    $url = PAYMONGO_API_URL . $endpoint;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: ' . getPaymongoAuthHeader(),
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    // Log curl errors
    if ($curlError) {
        error_log('PayMongo cURL Error: ' . $curlError);
    }
    
    $result = json_decode($response, true);
    
    // Log raw response for debugging
    error_log('PayMongo Raw Response: ' . substr($response, 0, 500));
    
    return [
        'success' => $httpCode >= 200 && $httpCode < 300,
        'http_code' => $httpCode,
        'data' => $result,
        'curl_error' => $curlError
    ];
}
