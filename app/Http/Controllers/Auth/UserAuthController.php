<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Reservation;
use App\Models\Review;
use App\Services\IDGeneratorService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserAuthController extends Controller
{
    public function login(Request $request)
    {
        $usernameInput = $request->input('usernameOrEmail', $request->input('username'));
        $password = $request->input('password');

        if (empty($usernameInput) || empty($password)) {
            return response()->json([
                'success' => false,
                'message' => 'Username and password cannot be empty',
                'data' => null
            ]);
        }

        try {
            $user = User::where('username', $usernameInput)
                ->orWhere('email', $usernameInput)
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
                    'message' => 'Account is inactive. Please contact support.',
                    'data' => null
                ]);
            }

            $requireEmailVerification = env('REQUIRE_EMAIL_VERIFICATION', false);
            if ($requireEmailVerification && !$user->email_verified) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please verify your email address before logging in. Check your inbox for the verification link.',
                    'data' => [
                        'email_verified' => false,
                        'email' => $user->email
                    ]
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
                'user_logged_in' => true,
                'user_id' => $user->user_id,
                'user_username' => $user->username,
                'user_email' => $user->email,
                'user_full_name' => $user->full_name,
                'user_given_name' => $user->given_name,
                'user_last_name' => $user->last_name,
                'user_middle_name' => $user->middle_name,
                'user_phone' => $user->phone_number,
                'login_time' => time(),
                'last_activity' => time(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user_id' => $user->user_id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'full_name' => $user->full_name,
                    'given_name' => $user->given_name,
                    'last_name' => $user->last_name,
                    'middle_name' => $user->middle_name,
                    'phone_number' => $user->phone_number
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Login Exception: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Database error occurred. Please try again.',
                'data' => null
            ]);
        }
    }

    public function register(Request $request)
    {
        $requiredFields = ['lastName', 'givenName', 'middleName', 'email', 'phoneNumber', 'username', 'password'];
        foreach ($requiredFields as $field) {
            if (!$request->filled($field)) {
                return response()->json([
                    'success' => false,
                    'message' => ucfirst($field) . ' is required',
                    'data' => null
                ]);
            }
        }

        $lastName = trim($request->input('lastName'));
        $givenName = trim($request->input('givenName'));
        $middleName = trim($request->input('middleName'));
        $email = trim($request->input('email'));
        $phoneNumber = trim($request->input('phoneNumber'));
        $username = trim($request->input('username'));
        $password = $request->input('password');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email format',
                'data' => null
            ]);
        }

        if (strlen($password) < 6) {
            return response()->json([
                'success' => false,
                'message' => 'Password must be at least 6 characters long',
                'data' => null
            ]);
        }

        try {
            if (User::where('email', $email)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email address is already registered',
                    'data' => null
                ]);
            }

            if (User::where('username', $username)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Username is already taken',
                    'data' => null
                ]);
            }

            $userId = IDGeneratorService::generateUserId();
            $fullName = "{$lastName}, {$givenName} {$middleName}";
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $requireEmailVerification = env('REQUIRE_EMAIL_VERIFICATION', false);
            $emailVerified = !$requireEmailVerification;

            $user = User::create([
                'user_id' => $userId,
                'username' => $username,
                'email' => $email,
                'password_hash' => $passwordHash,
                'last_name' => $lastName,
                'given_name' => $givenName,
                'middle_name' => $middleName,
                'full_name' => $fullName,
                'phone_number' => $phoneNumber,
                'email_verified' => $emailVerified,
                'is_active' => true,
                'member_since' => date('Y'),
                'loyalty_level' => 'Regular'
            ]);

            return response()->json([
                'success' => true,
                'message' => $requireEmailVerification 
                    ? 'Registration successful! Please check your email to verify your account before logging in.'
                    : 'Registration successful! You can now log in with your credentials.',
                'data' => [
                    'user_id' => $userId,
                    'username' => $username,
                    'email' => $email,
                    'full_name' => $fullName,
                    'email_verified' => $emailVerified,
                    'verification_required' => $requireEmailVerification
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Registration Exception: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Database error occurred. Please try again.',
                'data' => null
            ]);
        }
    }

    public function logout(Request $request)
    {
        session()->forget([
            'user_logged_in',
            'user_id',
            'user_username',
            'user_email',
            'user_full_name',
            'user_given_name',
            'user_last_name',
            'user_middle_name',
            'user_phone',
            'login_time',
            'last_activity'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    public function session(Request $request)
    {
        $userId = session('user_id');

        if (!session('user_logged_in') || !$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Not authenticated',
                'authenticated' => false
            ], 401);
        }

        $timeout = 3600;
        $lastActivity = session('last_activity', time());
        if (time() - $lastActivity > $timeout) {
            session()->flush();
            return response()->json([
                'success' => false,
                'message' => 'Session expired',
                'authenticated' => false
            ], 401);
        }

        session(['last_activity' => time()]);

        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
                'authenticated' => false
            ], 401);
        }

        $memberSince = $user->created_at ? $user->created_at->format('Y') : date('Y');

        $totalBookings = Reservation::where(function ($query) use ($user) {
                $query->where('user_id', $user->user_id)
                      ->orWhere('guest_email', $user->email);
            })
            ->whereRaw("NOT (status = 'pending_payment' AND (downpayment_verified = 0 OR downpayment_verified IS NULL))")
            ->count();

        $reviewsGiven = Review::where('user_id', $user->user_id)->count();

        return response()->json([
            'success' => true,
            'authenticated' => true,
            'data' => [
                'user_id' => $user->user_id,
                'username' => $user->username,
                'email' => $user->email,
                'full_name' => $user->full_name,
                'given_name' => $user->given_name,
                'last_name' => $user->last_name,
                'middle_name' => $user->middle_name,
                'phone_number' => $user->phone_number,
                'memberSince' => $memberSince,
                'totalBookings' => $totalBookings,
                'reviewsGiven' => $reviewsGiven,
                'loyaltyLevel' => $user->loyalty_level ?? 'Regular',
                'pending_email' => $user->pending_email ?? null
            ]
        ]);
    }
}
