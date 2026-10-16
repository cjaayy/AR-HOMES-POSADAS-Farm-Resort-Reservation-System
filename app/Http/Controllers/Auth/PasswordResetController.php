<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class PasswordResetController extends Controller
{
    public function forgotPassword(Request $request)
    {
        $email = trim($request->input('email', ''));

        if (empty($email)) {
            return response()->json([
                'success' => false,
                'message' => 'Email is required',
                'data' => null
            ]);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email format',
                'data' => null
            ]);
        }

        try {
            $user = User::where('email', $email)->first();

            // Always return a positive message for security
            if (!$user) {
                return response()->json([
                    'success' => true,
                    'message' => 'If an account exists with this email, a password reset link has been sent.',
                    'data' => null
                ]);
            }

            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $expiresAt = now()->addHour();

            $user->update([
                'reset_token' => $tokenHash,
                'reset_token_expires' => $expiresAt
            ]);

            $resetLink = url("/reset_password.html?token={$token}");

            // In local/development, return token/link or log it
            return response()->json([
                'success' => true,
                'message' => 'If an account exists with this email, a password reset link has been sent.',
                'data' => config('app.debug') ? [
                    'reset_link' => $resetLink,
                    'token' => $token
                ] : null
            ]);

        } catch (Exception $e) {
            Log::error('Forgot Password Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Database error occurred. Please try again.',
                'data' => null
            ]);
        }
    }

    public function verifyResetToken(Request $request)
    {
        $token = trim($request->input('token', ''));

        if (empty($token)) {
            return response()->json([
                'success' => false,
                'message' => 'Token is required',
                'data' => null
            ]);
        }

        try {
            $tokenHash = hash('sha256', $token);
            $user = User::where('reset_token', $tokenHash)
                ->where('is_active', 1)
                ->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid reset token',
                    'data' => null
                ]);
            }

            $expiresAt = strtotime($user->reset_token_expires);
            $currentTime = time();

            if ($currentTime > $expiresAt) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reset token has expired. Please request a new password reset.',
                    'data' => null
                ]);
            }

            $remainingMinutes = round(($expiresAt - $currentTime) / 60);

            return response()->json([
                'success' => true,
                'message' => 'Token is valid',
                'data' => [
                    'username' => $user->username,
                    'email' => $user->email,
                    'expires_in_minutes' => $remainingMinutes
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Verify Reset Token Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'data' => null
            ]);
        }
    }

    public function resetPassword(Request $request)
    {
        $token = trim($request->input('token', ''));
        $newPassword = trim($request->input('password', ''));

        if (empty($token) || empty($newPassword)) {
            return response()->json([
                'success' => false,
                'message' => 'Token and password are required',
                'data' => null
            ]);
        }

        if (strlen($newPassword) < 6) {
            return response()->json([
                'success' => false,
                'message' => 'Password must be at least 6 characters long',
                'data' => null
            ]);
        }

        try {
            $tokenHash = hash('sha256', $token);
            $user = User::where('reset_token', $tokenHash)
                ->where('is_active', 1)
                ->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid reset token',
                    'data' => null
                ]);
            }

            if (strtotime($user->reset_token_expires) < time()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reset token has expired. Please request a new password reset.',
                    'data' => null
                ]);
            }

            $user->update([
                'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
                'reset_token' => null,
                'reset_token_expires' => null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully. You can now login with your new password.',
                'data' => null
            ]);

        } catch (Exception $e) {
            Log::error('Reset Password Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Database error occurred. Please try again.',
                'data' => null
            ]);
        }
    }
}
