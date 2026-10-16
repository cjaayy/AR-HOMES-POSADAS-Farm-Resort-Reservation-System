<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminAuthController extends Controller
{
    public function adminLogin(Request $request)
    {
        $username = trim($request->input('username', ''));
        $password = trim($request->input('password', ''));

        if (empty($username) || empty($password)) {
            return response()->json([
                'success' => false,
                'message' => 'Username and password cannot be empty',
                'data' => null
            ]);
        }

        try {
            $user = AdminUser::where('username', $username)
                ->orWhere('email', $username)
                ->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid username or password',
                    'data' => null
                ]);
            }

            if (!$user->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account is inactive. Please contact system administrator.',
                    'data' => null
                ]);
            }

            if (!password_verify($password, $user->password_hash)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid username or password',
                    'data' => null
                ]);
            }

            $user->update(['last_login' => now()]);

            session([
                'admin_logged_in' => true,
                'admin_id' => $user->admin_id,
                'admin_username' => $user->username,
                'admin_full_name' => $user->full_name,
                'admin_email' => $user->email,
                'admin_role' => $user->role,
                'login_time' => time(),
                'last_activity' => time()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'admin_id' => $user->admin_id,
                    'username' => $user->username,
                    'full_name' => $user->full_name,
                    'email' => $user->email,
                    'role' => $user->role
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Admin Login Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Database error occurred. Please try again.',
                'data' => null
            ]);
        }
    }

    public function staffLogin(Request $request)
    {
        $username = trim($request->input('username', ''));
        $password = trim($request->input('password', ''));

        if (empty($username) || empty($password)) {
            return response()->json([
                'success' => false,
                'message' => 'Username and password cannot be empty',
                'data' => null
            ]);
        }

        try {
            $user = AdminUser::where(function ($query) use ($username) {
                    $query->where('username', $username)
                          ->orWhere('email', $username);
                })
                ->where('role', 'staff')
                ->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid username or password, or access restricted to staff only.',
                    'data' => null
                ]);
            }

            if (!$user->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Staff account is inactive. Please contact administrator.',
                    'data' => null
                ]);
            }

            if (!password_verify($password, $user->password_hash)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid username or password',
                    'data' => null
                ]);
            }

            $user->update(['last_login' => now()]);

            session([
                'staff_logged_in' => true,
                'staff_id' => $user->admin_id,
                'staff_username' => $user->username,
                'staff_full_name' => $user->full_name,
                'staff_email' => $user->email,
                'staff_role' => $user->role,
                'staff_login_time' => time(),
                'staff_last_activity' => time(),
                // Also set admin session for compatibility
                'admin_logged_in' => true,
                'admin_id' => $user->admin_id,
                'admin_username' => $user->username,
                'admin_full_name' => $user->full_name,
                'admin_email' => $user->email,
                'admin_role' => $user->role,
                'login_time' => time(),
                'last_activity' => time()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'staff_id' => $user->admin_id,
                    'username' => $user->username,
                    'full_name' => $user->full_name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'redirect' => 'staff_dashboard.php'
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Staff Login Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Database error occurred. Please try again.',
                'data' => null
            ]);
        }
    }

    public function checkSession(Request $request)
    {
        if (!session('admin_logged_in') && !session('staff_logged_in')) {
            return response()->json([
                'success' => false,
                'logged_in' => false,
                'message' => 'Not logged in',
                'data' => null
            ]);
        }

        $timeout = 3600;
        $lastActivity = session('last_activity', time());

        if (time() - $lastActivity > $timeout) {
            session()->flush();
            return response()->json([
                'success' => false,
                'logged_in' => false,
                'message' => 'Session expired',
                'data' => null
            ]);
        }

        session(['last_activity' => time()]);

        return response()->json([
            'success' => true,
            'logged_in' => true,
            'message' => 'Session active',
            'data' => [
                'admin_id' => session('admin_id'),
                'username' => session('admin_username'),
                'full_name' => session('admin_full_name'),
                'email' => session('admin_email'),
                'role' => session('admin_role'),
                'login_time' => session('login_time'),
                'last_activity' => session('last_activity')
            ]
        ]);
    }

    public function logout(Request $request)
    {
        session()->forget([
            'admin_logged_in',
            'admin_id',
            'admin_username',
            'admin_full_name',
            'admin_email',
            'admin_role',
            'staff_logged_in',
            'staff_id',
            'staff_username',
            'staff_full_name',
            'staff_email',
            'staff_role',
            'staff_login_time',
            'staff_last_activity',
            'login_time',
            'last_activity'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }
}
