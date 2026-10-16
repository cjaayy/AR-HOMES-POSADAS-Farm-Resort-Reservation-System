<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StaffUserController extends Controller
{
    public function getUsers(Request $request)
    {
        if (!session('admin_logged_in') && !session('staff_logged_in')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $users = User::select('user_id', 'username', 'email', 'full_name', 'phone_number', 'is_active', 'loyalty_level', 'created_at')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'count' => $users->count(),
                'users' => $users
            ]);
        } catch (Exception $e) {
            Log::error('Staff Get Users Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function actions(Request $request)
    {
        if (!session('admin_logged_in') && !session('staff_logged_in')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $action = $request->input('action');
        $userId = $request->input('user_id');

        try {
            $user = User::where('user_id', $userId)->first();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'User not found'], 404);
            }

            if ($action === 'toggle_status') {
                $user->update(['is_active' => !$user->is_active]);

                return response()->json([
                    'success' => true,
                    'message' => 'User status toggled successfully',
                    'is_active' => $user->is_active
                ]);
            }

            return response()->json(['success' => false, 'message' => 'Invalid action'], 400);
        } catch (Exception $e) {
            Log::error('Staff User Action Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function changePassword(Request $request)
    {
        $staffId = session('staff_id', session('admin_id'));
        if (!$staffId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $currentPassword = $request->input('current_password');
        $newPassword = $request->input('new_password');

        if (empty($currentPassword) || empty($newPassword)) {
            return response()->json(['success' => false, 'message' => 'All fields are required'], 400);
        }

        if (strlen($newPassword) < 6) {
            return response()->json(['success' => false, 'message' => 'Password must be at least 6 characters long'], 400);
        }

        try {
            $staff = AdminUser::find($staffId);
            if (!$staff) {
                return response()->json(['success' => false, 'message' => 'Staff user not found'], 404);
            }

            if (!password_verify($currentPassword, $staff->password_hash)) {
                return response()->json(['success' => false, 'message' => 'Incorrect current password'], 400);
            }

            $staff->update([
                'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password changed successfully'
            ]);
        } catch (Exception $e) {
            Log::error('Staff Change Password Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
