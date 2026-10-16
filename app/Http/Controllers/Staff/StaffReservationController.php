<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StaffReservationController extends Controller
{
    public function getReservations(Request $request)
    {
        if (!session('admin_logged_in') && !session('staff_logged_in')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $status = $request->query('status');
            $query = Reservation::orderBy('created_at', 'desc');

            if ($status && $status !== 'all') {
                $query->where('status', $status);
            }

            $reservations = $query->get();

            return response()->json([
                'success' => true,
                'count' => $reservations->count(),
                'reservations' => $reservations
            ]);
        } catch (Exception $e) {
            Log::error('Staff Get Reservations Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function handleAction(Request $request)
    {
        if (!session('admin_logged_in') && !session('staff_logged_in')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $reservationId = $request->input('reservation_id');
        $action = $request->input('action');

        if (empty($reservationId) || empty($action)) {
            return response()->json(['success' => false, 'message' => 'Reservation ID and action are required'], 400);
        }

        try {
            $reservation = Reservation::find($reservationId);
            if (!$reservation) {
                return response()->json(['success' => false, 'message' => 'Reservation not found'], 404);
            }

            $staffName = session('staff_full_name', session('admin_full_name', 'Staff Member'));

            switch ($action) {
                case 'approve':
                    $reservation->update([
                        'status' => 'confirmed',
                        'staff_notes' => ($reservation->staff_notes ? $reservation->staff_notes . "\n" : "") . "[" . now()->toDateTimeString() . "] Approved by {$staffName}"
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Reservation approved successfully'
                    ]);

                case 'cancel':
                    $reason = $request->input('reason', 'Cancelled by staff');
                    $reservation->update([
                        'status' => 'cancelled',
                        'cancellation_reason' => $reason,
                        'cancelled_at' => now(),
                        'staff_notes' => ($reservation->staff_notes ? $reservation->staff_notes . "\n" : "") . "[" . now()->toDateTimeString() . "] Cancelled by {$staffName}: {$reason}"
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Reservation cancelled successfully'
                    ]);

                case 'assign_room':
                    $room = $request->input('room');
                    $reservation->update(['room' => $room]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Room assigned successfully'
                    ]);

                default:
                    return response()->json(['success' => false, 'message' => 'Invalid action'], 400);
            }
        } catch (Exception $e) {
            Log::error('Staff Reservation Action Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
