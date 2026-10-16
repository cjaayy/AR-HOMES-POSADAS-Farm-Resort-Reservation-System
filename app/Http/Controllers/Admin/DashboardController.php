<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\Reservation;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        if (!session('admin_logged_in') && !session('staff_logged_in')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 401);
        }

        try {
            $totalUsers = User::count();
            $activeUsers = User::where('is_active', 1)->count();
            $newUsersMonth = User::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();
            $todayUsers = User::whereDate('created_at', now()->toDateString())->count();
            $weekUsers = User::where('created_at', '>=', now()->subDays(7))->count();

            // Reservations excluding abandoned
            $validReservationsQuery = Reservation::whereRaw("NOT (status = 'pending_payment' AND (downpayment_verified = 0 OR downpayment_verified IS NULL))");

            $totalReservations = (clone $validReservationsQuery)->count();
            $pendingReservations = Reservation::where('status', 'pending_confirmation')->count();
            $confirmedReservations = Reservation::where('status', 'confirmed')->count();
            $completedReservations = Reservation::whereIn('status', ['completed', 'checked_out'])->count();

            $loyaltyStats = [
                'Regular' => User::where('loyalty_level', 'Regular')->count(),
                'Silver' => User::where('loyalty_level', 'Silver')->count(),
                'Gold' => User::where('loyalty_level', 'Gold')->count(),
                'VIP' => User::where('loyalty_level', 'VIP')->count()
            ];

            $recentUsers = User::orderBy('created_at', 'desc')->take(5)->get();
            $recentActivities = [];
            foreach ($recentUsers as $u) {
                $recentActivities[] = [
                    'type' => 'new_user',
                    'title' => 'New User Registered',
                    'description' => "{$u->full_name} ({$u->username}) joined",
                    'timestamp' => $u->created_at ? $u->created_at->diffForHumans() : 'Recently'
                ];
            }

            return response()->json([
                'success' => true,
                'stats' => [
                    'total_users' => $totalUsers,
                    'active_users' => $activeUsers,
                    'inactive_users' => $totalUsers - $activeUsers,
                    'new_users_this_month' => $newUsersMonth,
                    'new_users_today' => $todayUsers,
                    'new_users_this_week' => $weekUsers,
                    'total_reservations' => $totalReservations,
                    'pending_reservations' => $pendingReservations,
                    'confirmed_reservations' => $confirmedReservations,
                    'completed_reservations' => $completedReservations
                ],
                'loyalty_breakdown' => $loyaltyStats,
                'recent_activities' => $recentActivities,
                'timestamp' => now()->toDateTimeString()
            ]);
        } catch (Exception $e) {
            Log::error('Admin Dashboard Stats Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
        }
    }
}
