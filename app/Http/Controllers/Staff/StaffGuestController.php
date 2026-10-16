<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StaffGuestController extends Controller
{
    public function getGuests(Request $request)
    {
        if (!session('admin_logged_in') && !session('staff_logged_in')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $guests = Reservation::select('guest_name', 'guest_email', 'guest_phone', 'user_id', 'group_type')
                ->distinct('guest_email')
                ->whereNotNull('guest_name')
                ->get();

            return response()->json([
                'success' => true,
                'count' => $guests->count(),
                'guests' => $guests
            ]);
        } catch (Exception $e) {
            Log::error('Staff Get Guests Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
