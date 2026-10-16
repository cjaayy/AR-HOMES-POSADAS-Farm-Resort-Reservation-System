<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function index(Request $request)
    {
        if (!session('admin_logged_in') && !session('staff_logged_in')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $users = User::orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'users' => $users,
                'total' => $users->count()
            ]);
        } catch (Exception $e) {
            Log::error('Admin Get Users Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request)
    {
        if (!session('admin_logged_in')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $userId = $request->input('user_id');

        try {
            $user = User::where('user_id', $userId)->first();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'User not found'], 404);
            }

            $user->update([
                'given_name' => $request->input('given_name', $user->given_name),
                'last_name' => $request->input('last_name', $user->last_name),
                'middle_name' => $request->input('middle_name', $user->middle_name),
                'full_name' => $request->input('last_name', $user->last_name) . ', ' . $request->input('given_name', $user->given_name) . ' ' . $request->input('middle_name', $user->middle_name),
                'email' => $request->input('email', $user->email),
                'phone_number' => $request->input('phone_number', $user->phone_number),
                'loyalty_level' => $request->input('loyalty_level', $user->loyalty_level),
                'is_active' => $request->has('is_active') ? (bool)$request->input('is_active') : $user->is_active,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully'
            ]);
        } catch (Exception $e) {
            Log::error('Admin Update User Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request)
    {
        if (!session('admin_logged_in')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $userId = $request->input('user_id');

        try {
            $user = User::where('user_id', $userId)->first();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'User not found'], 404);
            }

            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);
        } catch (Exception $e) {
            Log::error('Admin Delete User Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function updateStatus(Request $request)
    {
        if (!session('admin_logged_in')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $userId = $request->input('user_id');
        $status = $request->input('is_active');

        try {
            $user = User::where('user_id', $userId)->first();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'User not found'], 404);
            }

            $user->update(['is_active' => (bool)$status]);

            return response()->json([
                'success' => true,
                'message' => 'User status updated successfully'
            ]);
        } catch (Exception $e) {
            Log::error('Admin Update User Status Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function updateProfile(Request $request)
    {
        $adminId = session('admin_id');
        if (!$adminId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $admin = AdminUser::find($adminId);
            if (!$admin) {
                return response()->json(['success' => false, 'message' => 'Admin not found'], 404);
            }

            $updateData = [
                'full_name' => $request->input('full_name', $admin->full_name),
                'email' => $request->input('email', $admin->email),
            ];

            if ($request->filled('password')) {
                $updateData['password_hash'] = password_hash($request->input('password'), PASSWORD_DEFAULT);
            }

            $admin->update($updateData);

            session([
                'admin_full_name' => $admin->full_name,
                'admin_email' => $admin->email
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully'
            ]);
        } catch (Exception $e) {
            Log::error('Admin Update Profile Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
