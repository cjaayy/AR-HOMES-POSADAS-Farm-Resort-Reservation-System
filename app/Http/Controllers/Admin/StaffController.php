<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        if (!session('admin_logged_in')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $staff = AdminUser::select('admin_id', 'username', 'email', 'full_name', 'position', 'role', 'is_active', 'last_login', 'created_at')
                ->where('role', 'staff')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'staff' => $staff,
                'total' => $staff->count()
            ]);
        } catch (Exception $e) {
            Log::error('Admin Get Staff Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        if (!session('admin_logged_in')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $username = trim($request->input('username', ''));
        $email = trim($request->input('email', ''));
        $password = trim($request->input('password', ''));
        $fullName = trim($request->input('full_name', ''));
        $position = trim($request->input('position', 'Staff Member'));

        if (empty($username) || empty($email) || empty($password) || empty($fullName)) {
            return response()->json(['success' => false, 'message' => 'All fields are required'], 400);
        }

        try {
            if (AdminUser::where('username', $username)->exists()) {
                return response()->json(['success' => false, 'message' => 'Username already taken'], 400);
            }

            if (AdminUser::where('email', $email)->exists()) {
                return response()->json(['success' => false, 'message' => 'Email already registered'], 400);
            }

            $adminUser = AdminUser::create([
                'username' => $username,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'full_name' => $fullName,
                'position' => $position,
                'role' => 'staff',
                'is_active' => true,
                'created_by' => session('admin_id')
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Staff member created successfully',
                'staff_id' => $adminUser->admin_id
            ]);
        } catch (Exception $e) {
            Log::error('Create Staff Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request)
    {
        if (!session('admin_logged_in')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $staffId = $request->input('staff_id', $request->input('admin_id'));

        try {
            $staff = AdminUser::where('admin_id', $staffId)->where('role', 'staff')->first();
            if (!$staff) {
                return response()->json(['success' => false, 'message' => 'Staff member not found'], 404);
            }

            $staff->update([
                'full_name' => $request->input('full_name', $staff->full_name),
                'email' => $request->input('email', $staff->email),
                'position' => $request->input('position', $staff->position),
                'is_active' => $request->has('is_active') ? (bool)$request->input('is_active') : $staff->is_active,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Staff member updated successfully'
            ]);
        } catch (Exception $e) {
            Log::error('Update Staff Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function resetPassword(Request $request)
    {
        if (!session('admin_logged_in')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $staffId = $request->input('staff_id', $request->input('admin_id'));
        $newPassword = $request->input('new_password', $request->input('password'));

        if (empty($newPassword) || strlen($newPassword) < 6) {
            return response()->json(['success' => false, 'message' => 'Password must be at least 6 characters long'], 400);
        }

        try {
            $staff = AdminUser::where('admin_id', $staffId)->where('role', 'staff')->first();
            if (!$staff) {
                return response()->json(['success' => false, 'message' => 'Staff member not found'], 404);
            }

            $staff->update([
                'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Staff password reset successfully'
            ]);
        } catch (Exception $e) {
            Log::error('Reset Staff Password Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
