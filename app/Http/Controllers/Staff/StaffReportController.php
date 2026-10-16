<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StaffReportController extends Controller
{
    public function getReportData(Request $request)
    {
        if (!session('admin_logged_in') && !session('staff_logged_in')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $period = $request->query('period', 'week');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        switch ($period) {
            case 'today':
                $start = now()->toDateString();
                $end = now()->toDateString();
                break;
            case 'month':
                $start = now()->subDays(30)->toDateString();
                $end = now()->toDateString();
                break;
            case 'year':
                $start = now()->subDays(365)->toDateString();
                $end = now()->toDateString();
                break;
            case 'custom':
                $start = $startDate ?: now()->subDays(7)->toDateString();
                $end = $endDate ?: now()->toDateString();
                break;
            case 'week':
            default:
                $start = now()->subDays(7)->toDateString();
                $end = now()->toDateString();
                break;
        }

        try {
            $reservations = Reservation::whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])->get();

            $totalRevenue = $reservations->whereIn('status', ['confirmed', 'completed'])->sum('total_amount');
            $confirmedBookings = $reservations->where('status', 'confirmed')->count();
            $completedBookings = $reservations->where('status', 'completed')->count();
            $cancelledBookings = $reservations->where('status', 'cancelled')->count();

            return response()->json([
                'success' => true,
                'period' => $period,
                'start_date' => $start,
                'end_date' => $end,
                'stats' => [
                    'total_bookings' => $reservations->count(),
                    'total_revenue' => $totalRevenue,
                    'confirmed_bookings' => $confirmedBookings,
                    'completed_bookings' => $completedBookings,
                    'cancelled_bookings' => $cancelledBookings
                ],
                'reservations' => $reservations
            ]);
        } catch (Exception $e) {
            Log::error('Staff Report Data Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
