<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\Reservation;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        if (!session('admin_logged_in') && !session('staff_logged_in')) {
            return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);
        }

        try {
            $adminId = session('admin_id');
            $adminData = AdminUser::select('admin_id', 'full_name', 'email', 'username', 'role', 'created_at', 'last_login')
                ->where('admin_id', $adminId)
                ->first();

            $staffCount = AdminUser::count();
            $activeSessions = AdminUser::where('last_login', '>=', now()->subHours(24))->count();
            $lastActivity = AdminUser::max('last_login');

            $dbStats = [
                'total_users' => User::count(),
                'total_reservations' => Reservation::count(),
                'total_staff' => $staffCount
            ];

            $systemSettings = [
                'resort_name' => 'AR Homes Posadas Farm Resort',
                'contact_email' => 'info@arhomesposadas.com',
                'contact_phone' => '+63 912 345 6789',
                'language' => 'English (US)',
                'timezone' => 'Asia/Manila (UTC+8)',
                'currency' => 'PHP (₱)',
                'date_format' => 'M d, Y',
                'time_format' => '12-hour',
                'session_timeout' => '30 minutes'
            ];

            return response()->json([
                'success' => true,
                'admin' => $adminData,
                'stats' => [
                    'total_staff' => $staffCount,
                    'active_sessions' => $activeSessions,
                    'last_activity' => $lastActivity,
                    'total_users' => $dbStats['total_users'],
                    'total_reservations' => $dbStats['total_reservations']
                ],
                'settings' => $systemSettings
            ]);

        } catch (Exception $e) {
            Log::error('Get Settings Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Database error occurred'], 500);
        }
    }

    public function update(Request $request)
    {
        if (!session('admin_logged_in')) {
            return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);
        }

        if (session('admin_role') === 'staff') {
            return response()->json(['success' => false, 'message' => 'You do not have permission to modify system settings'], 403);
        }

        $settingKey = trim($request->input('key', ''));
        $settingValue = trim($request->input('value', ''));

        if (empty($settingKey)) {
            return response()->json(['success' => false, 'message' => 'Setting key is required'], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Setting updated successfully',
            'data' => [
                'key' => $settingKey,
                'value' => $settingValue
            ]
        ]);
    }
}
