<?php

/**
 * AR Homes Posadas Farm Resort - Front Controller
 */

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
ini_set('display_errors', '0');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['_token'])) {
    $_SESSION['_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/../vendor/autoload.php';

// Helper functions for Blade and Application
if (!function_exists('csrf_token')) {
    function csrf_token() {
        return $_SESSION['_token'] ?? '';
    }
}

if (!function_exists('asset')) {
    function asset($path) {
        $base = '/AR_Homes_Posadas_Farm_Resort_Reservation_System_Design_2/public/';
        return $base . ltrim($path, '/');
    }
}

if (!function_exists('url')) {
    function url($path) {
        $base = '/AR_Homes_Posadas_Farm_Resort_Reservation_System_Design_2/';
        return $base . ltrim($path, '/');
    }
}

if (!function_exists('session')) {
    function session($key = null, $default = null) {
        if ($key === null) return $_SESSION;
        return $_SESSION[$key] ?? $default;
    }
}

if (!function_exists('route')) {
    function route($name) {
        $routes = [
            'home' => url('/'),
            'registration' => url('/registration'),
            'password.reset' => url('/reset-password'),
            'user.dashboard' => url('/dashboard'),
            'admin.dashboard' => url('/admin/dashboard'),
            'staff.dashboard' => url('/staff/dashboard'),
            'user.logout' => url('/user/logout.php'),
            'admin.logout' => url('/admin/logout.php'),
            'staff.logout' => url('/admin/staff_logout.php'),
        ];
        return $routes[$name] ?? url($name);
    }
}

function view($viewPath, $data = []) {
    extract($data);
    $viewsDir = dirname(__DIR__) . '/resources/views';
    $file = $viewsDir . '/' . str_replace('.', '/', $viewPath) . '.blade.php';

    if (!file_exists($file)) {
        http_response_code(404);
        echo "View [{$viewPath}] not found.";
        exit;
    }

    $content = file_get_contents($file);

    // If view extends a layout
    if (preg_match('/@extends\([\'"]([^\'"]+)[\'"]\)/', $content, $extMatch)) {
        $layoutName = $extMatch[1];
        $layoutFile = $viewsDir . '/' . str_replace('.', '/', $layoutName) . '.blade.php';
        $layoutContent = file_exists($layoutFile) ? file_get_contents($layoutFile) : '';

        // Single-line sections: @section('title', 'Value')
        preg_match_all('/@section\([\'"]([^\'"]+)[\'"],\s*[\'"]([^\'"]+)[\'"]\)/', $content, $secSingleMatches, PREG_SET_ORDER);
        foreach ($secSingleMatches as $sm) {
            $layoutContent = preg_replace('/@yield\([\'"]' . preg_quote($sm[1], '/') . '[\'"](?:,\s*[\'"][^\'"]*[\'"])?\)/', $sm[2], $layoutContent);
        }

        // Block sections: @section('name') ... @endsection
        preg_match_all('/@section\([\'"]([^\'"]+)[\'"]\)(.*?)@endsection/s', $content, $secBlockMatches, PREG_SET_ORDER);
        foreach ($secBlockMatches as $bm) {
            $layoutContent = preg_replace('/@yield\([\'"]' . preg_quote($bm[1], '/') . '[\'"](?:,\s*[\'"][^\'"]*[\'"])?\)/', $bm[2], $layoutContent);
        }

        // Clean any leftover @yields with default or empty
        $layoutContent = preg_replace_callback('/@yield\([\'"]([^\'"]+)[\'"](?:,\s*[\'"]([^\'"]*)[\'"])?\)/', function($m) {
            return $m[2] ?? '';
        }, $layoutContent);

        // Process @push('name') ... @endpush
        preg_match_all('/@push\([\'"]([^\'"]+)[\'"]\)(.*?)@endpush/s', $content, $pushMatches, PREG_SET_ORDER);
        $stacks = [];
        foreach ($pushMatches as $pm) {
            $stackName = $pm[1];
            if (!isset($stacks[$stackName])) {
                $stacks[$stackName] = '';
            }
            $stacks[$stackName] .= "\n" . $pm[2];
        }

        foreach ($stacks as $stackName => $stackContent) {
            $layoutContent = preg_replace('/@stack\([\'"]' . preg_quote($stackName, '/') . '[\'"]\)/', $stackContent, $layoutContent);
        }

        // Clean any leftover @stacks
        $layoutContent = preg_replace('/@stack\([\'"][^\'"]+[\'"]\)/', '', $layoutContent);
        $content = $layoutContent;
    }

    // Process @include('partials.name')
    $content = preg_replace_callback('/@include\([\'"]([^\'"]+)[\'"]\)/', function($m) use ($viewsDir) {
        $incFile = $viewsDir . '/' . str_replace('.', '/', $m[1]) . '.blade.php';
        return file_exists($incFile) ? file_get_contents($incFile) : '';
    }, $content);

    // Process @csrf
    $content = str_replace('@csrf', '<input type="hidden" name="_token" value="' . htmlspecialchars(csrf_token()) . '">', $content);

    // Process {{ expressions }}
    $content = preg_replace_callback('/\{\{\s*(.*?)\s*\}\}/s', function($m) use ($data) {
        $expr = trim($m[1]);
        try {
            extract($data);
            return (string) eval("return $expr;");
        } catch (\Throwable $e) {
            return '';
        }
    }, $content);

    header('Content-Type: text/html; charset=UTF-8');
    echo $content;
    exit;
}

// Route the incoming request
$projectRoot = dirname(__DIR__);
$docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
$projectDir = str_replace('\\', '/', $projectRoot);

$baseFolder = '';
if (str_starts_with($projectDir, $docRoot)) {
    $baseFolder = substr($projectDir, strlen($docRoot));
}

$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$relativePath = $requestUri;
if ($baseFolder && str_starts_with($relativePath, $baseFolder)) {
    $relativePath = substr($relativePath, strlen($baseFolder));
}
$relativePath = preg_replace('#^/public#', '', $relativePath);
$path = '/' . ltrim(urldecode($relativePath), '/');
if ($path === '//' || $path === '') {
    $path = '/';
}

// 1. Static asset in public/
$publicFile = __DIR__ . $path;
if ($path !== '/' && file_exists($publicFile) && !is_dir($publicFile)) {
    $ext = pathinfo($publicFile, PATHINFO_EXTENSION);
    $mimes = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'webp' => 'image/webp',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
    ];
    $contentType = $mimes[$ext] ?? mime_content_type($publicFile);
    header("Content-Type: $contentType");
    readfile($publicFile);
    exit;
}

// 2. Dispatch to legacy PHP endpoint scripts (e.g. user/*.php, admin/*.php)
$rootFile = $projectRoot . $path;
if ($path !== '/' && file_exists($rootFile) && !is_dir($rootFile) && str_ends_with($rootFile, '.php')) {
    if (!in_array($path, ['/admin/dashboard.php', '/admin/staff_dashboard.php'])) {
        chdir(dirname($rootFile));
        require $rootFile;
        exit;
    }
}

// 3. Page View Routing
switch ($path) {
    case '/':
    case '/index.html':
    case '/index.php':
        if (session('user_logged_in') === true) {
            header('Location: ' . url('/dashboard'));
            exit;
        }
        if (session('admin_logged_in') === true) {
            $dest = (strtolower(session('admin_role', '')) === 'staff') ? '/staff/dashboard' : '/admin/dashboard';
            header('Location: ' . url($dest));
            exit;
        }
        if (session('staff_logged_in') === true) {
            header('Location: ' . url('/staff/dashboard'));
            exit;
        }
        view('pages.index');
        break;

    case '/registration':
    case '/registration.html':
        if (session('user_logged_in') === true) {
            header('Location: ' . url('/dashboard'));
            exit;
        }
        view('pages.registration');
        break;

    case '/reset-password':
    case '/reset_password.html':
        view('pages.reset_password', [
            'token' => $_GET['token'] ?? '',
            'email' => $_GET['email'] ?? ''
        ]);
        break;

    case '/dashboard':
    case '/dashboard.html':
    case '/user/dashboard':
    case '/user/dashboard.html':
        if (!session('user_logged_in') || !session('user_id')) {
            header('Location: ' . url('/'));
            exit;
        }
        view('user.dashboard');
        break;

    case '/admin':
    case '/admin/dashboard':
    case '/admin/dashboard.php':
        if (!session('admin_logged_in')) {
            header('Location: ' . url('/'));
            exit;
        }
        if (strtolower(session('admin_role', '')) === 'staff') {
            header('Location: ' . url('/staff/dashboard'));
            exit;
        }
        view('admin.dashboard');
        break;

    case '/staff':
    case '/staff/dashboard':
    case '/admin/staff_dashboard.php':
        if (!session('staff_logged_in') && !(session('admin_logged_in') && strtolower(session('admin_role', '')) === 'staff')) {
            header('Location: ' . url('/'));
            exit;
        }
        view('staff.dashboard');
        break;

    // --- API & Auth Action Endpoints ---
    case '/user/logout.php':
    case '/user/logout':
        unset($_SESSION['user_logged_in'], $_SESSION['user_id'], $_SESSION['user_username'], $_SESSION['user_email'], $_SESSION['user_full_name']);
        header('Location: ' . url('/'));
        exit;

    case '/admin/logout.php':
    case '/admin/staff_logout.php':
    case '/admin/logout':
        unset($_SESSION['admin_logged_in'], $_SESSION['admin_id'], $_SESSION['admin_username'], $_SESSION['admin_role'], $_SESSION['staff_logged_in'], $_SESSION['staff_id'], $_SESSION['staff_username']);
        header('Location: ' . url('/'));
        exit;

    case '/user/get_session.php':
        header('Content-Type: application/json');
        if (empty($_SESSION['user_logged_in']) || empty($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'authenticated' => false, 'message' => 'Not authenticated']);
            exit;
        }
        try {
            $env = parse_ini_file($projectRoot . '/.env');
            $db = new PDO(
                "mysql:host=" . ($env['DB_HOST'] ?? '127.0.0.1') . ";port=" . ($env['DB_PORT'] ?? '3306') . ";dbname=" . ($env['DB_DATABASE'] ?? 'ar_homes_resort_db'),
                $env['DB_USERNAME'] ?? 'root',
                $env['DB_PASSWORD'] ?? ''
            );
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ? LIMIT 1");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                $memberSince = !empty($user['created_at']) ? date('Y', strtotime($user['created_at'])) : date('Y');
                $bStmt = $db->prepare("SELECT COUNT(*) FROM reservations WHERE user_id = ? OR guest_email = ?");
                $bStmt->execute([$user['user_id'], $user['email']]);
                $totalBookings = (int)$bStmt->fetchColumn();

                $rStmt = $db->prepare("SELECT COUNT(*) FROM reviews WHERE user_id = ?");
                $rStmt->execute([$user['user_id']]);
                $reviewsGiven = (int)$rStmt->fetchColumn();

                echo json_encode([
                    'success' => true,
                    'authenticated' => true,
                    'data' => [
                        'user_id' => $user['user_id'],
                        'username' => $user['username'] ?? 'Guest',
                        'email' => $user['email'] ?? '',
                        'full_name' => $user['full_name'] ?? 'Guest',
                        'given_name' => $user['given_name'] ?? '',
                        'last_name' => $user['last_name'] ?? '',
                        'middle_name' => $user['middle_name'] ?? '',
                        'phone_number' => $user['phone_number'] ?? '',
                        'memberSince' => $memberSince,
                        'totalBookings' => $totalBookings,
                        'reviewsGiven' => $reviewsGiven,
                        'loyaltyLevel' => $user['loyalty_level'] ?? 'Regular',
                        'pending_email' => null
                    ]
                ]);
                exit;
            }
        } catch (\Throwable $e) {}
        echo json_encode(['success' => false, 'authenticated' => false, 'message' => 'User not found']);
        exit;

    case '/user/get_my_reservations.php':
    case '/user/get_reservations.php':
        header('Content-Type: application/json');
        if (empty($_SESSION['user_logged_in']) || empty($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        try {
            $env = parse_ini_file($projectRoot . '/.env');
            $db = new PDO(
                "mysql:host=" . ($env['DB_HOST'] ?? '127.0.0.1') . ";port=" . ($env['DB_PORT'] ?? '3306') . ";dbname=" . ($env['DB_DATABASE'] ?? 'ar_homes_resort_db'),
                $env['DB_USERNAME'] ?? 'root',
                $env['DB_PASSWORD'] ?? ''
            );
            $stmt = $db->prepare("SELECT * FROM reservations WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->execute([$_SESSION['user_id']]);
            $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'reservations' => $res]);
            exit;
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }

    case '/user/get_notifications.php':
        header('Content-Type: application/json');
        $userId = $_SESSION['user_id'] ?? '';
        try {
            $env = parse_ini_file($projectRoot . '/.env');
            $db = new PDO(
                "mysql:host=" . ($env['DB_HOST'] ?? '127.0.0.1') . ";port=" . ($env['DB_PORT'] ?? '3306') . ";dbname=" . ($env['DB_DATABASE'] ?? 'ar_homes_resort_db'),
                $env['DB_USERNAME'] ?? 'root',
                $env['DB_PASSWORD'] ?? ''
            );
            $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
            $stmt->execute([$userId]);
            $notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'notifications' => $notifs, 'unread_count' => count($notifs)]);
            exit;
        } catch (\Throwable $e) {
            echo json_encode(['success' => true, 'notifications' => [], 'unread_count' => 0]);
            exit;
        }

    case '/user/get_unavailable_dates.php':
        header('Content-Type: application/json');
        $type = $_REQUEST['booking_type'] ?? '';
        try {
            $env = parse_ini_file($projectRoot . '/.env');
            $db = new PDO(
                "mysql:host=" . ($env['DB_HOST'] ?? '127.0.0.1') . ";port=" . ($env['DB_PORT'] ?? '3306') . ";dbname=" . ($env['DB_DATABASE'] ?? 'ar_homes_resort_db'),
                $env['DB_USERNAME'] ?? 'root',
                $env['DB_PASSWORD'] ?? ''
            );
            $sql = "SELECT check_in_date FROM reservations WHERE status IN ('confirmed', 'checked_in')";
            if ($type) $sql .= " AND booking_type = " . $db->quote($type);
            $dates = $db->query($sql)->fetchAll(PDO::FETCH_COLUMN);
            echo json_encode(['success' => true, 'unavailable_dates' => $dates]);
            exit;
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }

    case '/user/get_reviews.php':
    case '/user/get_public_reviews.php':
    case '/reviews/public':
        header('Content-Type: application/json');
        try {
            $env = parse_ini_file($projectRoot . '/.env');
            $db = new PDO(
                "mysql:host=" . ($env['DB_HOST'] ?? '127.0.0.1') . ";port=" . ($env['DB_PORT'] ?? '3306') . ";dbname=" . ($env['DB_DATABASE'] ?? 'ar_homes_resort_db'),
                $env['DB_USERNAME'] ?? 'root',
                $env['DB_PASSWORD'] ?? ''
            );
            $reviews = $db->query("SELECT * FROM reviews WHERE status = 'approved' ORDER BY created_at DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'reviews' => $reviews, 'data' => $reviews]);
            exit;
        } catch (\Throwable $e) {
            echo json_encode(['success' => true, 'reviews' => [], 'data' => []]);
            exit;
        }

    case '/user/get_csrf_token.php':
        header('Content-Type: application/json');
        echo json_encode(['csrf_token' => csrf_token()]);
        exit;

    case '/admin/check_session.php':
        header('Content-Type: application/json');
        $isLoggedIn = !empty($_SESSION['admin_logged_in']) || !empty($_SESSION['staff_logged_in']);
        echo json_encode([
            'success' => $isLoggedIn,
            'logged_in' => $isLoggedIn,
            'role' => $_SESSION['admin_role'] ?? ($_SESSION['staff_logged_in'] ? 'staff' : '')
        ]);
        exit;

    case '/admin/get_dashboard_stats.php':
    case '/admin/staff_get_stats.php':
        header('Content-Type: application/json');
        try {
            $env = parse_ini_file($projectRoot . '/.env');
            $db = new PDO(
                "mysql:host=" . ($env['DB_HOST'] ?? '127.0.0.1') . ";port=" . ($env['DB_PORT'] ?? '3306') . ";dbname=" . ($env['DB_DATABASE'] ?? 'ar_homes_resort_db'),
                $env['DB_USERNAME'] ?? 'root',
                $env['DB_PASSWORD'] ?? ''
            );
            $totalUsers = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
            $totalRes = (int)$db->query("SELECT COUNT(*) FROM reservations")->fetchColumn();
            $pendingRes = (int)$db->query("SELECT COUNT(*) FROM reservations WHERE status = 'pending_payment'")->fetchColumn();
            $confirmedRes = (int)$db->query("SELECT COUNT(*) FROM reservations WHERE status = 'confirmed'")->fetchColumn();
            echo json_encode([
                'success' => true,
                'stats' => [
                    'total_users' => $totalUsers,
                    'total_reservations' => $totalRes,
                    'pending_reservations' => $pendingRes,
                    'confirmed_reservations' => $confirmedRes
                ]
            ]);
            exit;
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
}

// Fallback to 404
http_response_code(404);
header('Content-Type: text/plain');
echo "404 Not Found: " . htmlspecialchars($path);
