<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StaffDashboardController extends Controller
{
    public function getStats(Request $request)
    {
        if (!session('admin_logged_in') && !session('staff_logged_in')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $today = now()->toDateString();

            $todayReservations = Reservation::whereDate('created_at', $today)->count();
            $arrivalsToday = Reservation::whereDate('check_in_date', $today)->where('status', 'confirmed')->count();
            $checkoutsToday = Reservation::whereDate('check_out_date', $today)->whereIn('status', ['confirmed', 'completed'])->count();
            $pendingRequests = Reservation::whereIn('status', ['pending', 'pending_payment', 'pending_confirmation'])->count();

            return response()->json([
                'success' => true,
                'stats' => [
                    'today_reservations' => $todayReservations,
                    'arrivals_today' => $arrivalsToday,
                    'checkouts_today' => $checkoutsToday,
                    'pending_requests' => $pendingRequests
                ],
                'timestamp' => now()->toDateTimeString()
            ]);
        } catch (Exception $e) {
            Log::error('Staff Get Stats Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getRoomStats(Request $request)
    {
        if (!session('admin_logged_in') && !session('staff_logged_in')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $today = now()->toDateString();
            $monthStart = now()->startOfMonth()->toDateString();
            $monthEnd = now()->endOfMonth()->toDateString();

            $todayBookings = Reservation::whereIn('status', ['confirmed', 'checked_in'])
                ->whereRaw("? BETWEEN check_in_date AND check_out_date", [$today])
                ->count();

            $bookedDays = Reservation::whereIn('status', ['confirmed', 'checked_in', 'completed'])
                ->whereBetween('check_in_date', [$monthStart, $monthEnd])
                ->distinct('check_in_date')
                ->count('check_in_date');

            $totalDaysInMonth = (int)now()->daysInMonth;
            $occupancyRate = $totalDaysInMonth > 0 ? round(($bookedDays / $totalDaysInMonth) * 100) : 0;

            $upcoming = Reservation::whereIn('status', ['confirmed', 'pending_confirmation'])
                ->whereBetween('check_in_date', [$today, now()->addDays(30)->toDateString()])
                ->count();

            return response()->json([
                'success' => true,
                'stats' => [
                    'today_bookings' => $todayBookings,
                    'booked_days' => $bookedDays,
                    'total_days_in_month' => $totalDaysInMonth,
                    'occupancy_rate' => $occupancyRate,
                    'upcoming_30_days' => $upcoming
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Staff Room Stats Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getActivity(Request $request)
    {
        if (!session('admin_logged_in') && !session('staff_logged_in')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $recentBookings = Reservation::orderBy('created_at', 'desc')->take(10)->get();
            $activities = [];

            foreach ($recentBookings as $b) {
                $activities[] = [
                    'id' => $b->reservation_id,
                    'title' => "Reservation #{$b->reservation_id}",
                    'description' => "{$b->guest_name} booked {$b->booking_type}",
                    'status' => $b->status,
                    'created_at' => $b->created_at ? $b->created_at->diffForHumans() : 'Recently'
                ];
            }

            return response()->json([
                'success' => true,
                'activities' => $activities
            ]);
        } catch (Exception $e) {
            Log::error('Staff Get Activity Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
