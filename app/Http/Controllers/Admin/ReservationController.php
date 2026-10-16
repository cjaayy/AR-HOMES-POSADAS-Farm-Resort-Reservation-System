<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Services\ReservationStateManager;
use DateTime;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReservationController extends Controller
{
    public function calendar(Request $request)
    {
        if (!session('admin_logged_in') && !session('staff_logged_in')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $startDate = substr($request->query('start', date('Y-m-01')), 0, 10);
        $endDate = substr($request->query('end', date('Y-m-t', strtotime('+2 months'))), 0, 10);

        try {
            $reservations = Reservation::where('check_in_date', '<=', $endDate)
                ->where('check_out_date', '>=', $startDate)
                ->orderBy('check_in_date', 'asc')
                ->get();

            $events = [];
            foreach ($reservations as $r) {
                $statusColors = [
                    'confirmed' => '#28a745',
                    'checked_in' => '#17a2b8',
                    'checked_out' => '#6c757d',
                    'completed' => '#20c997',
                    'pending_payment' => '#ffc107',
                    'pending_confirmation' => '#fd7e14',
                    'cancelled' => '#dc3545',
                    'no_show' => '#343a40'
                ];

                $color = $statusColors[$r->status] ?? '#6c757d';

                $events[] = [
                    'id' => $r->reservation_id,
                    'title' => "{$r->guest_name} ({$r->booking_type})",
                    'start' => $r->check_in_date . 'T' . $r->check_in_time,
                    'end' => ($r->check_out_date ?: $r->check_in_date) . 'T' . $r->check_out_time,
                    'backgroundColor' => $color,
                    'borderColor' => $color,
                    'extendedProps' => [
                        'guest_name' => $r->guest_name,
                        'guest_phone' => $r->guest_phone,
                        'guest_email' => $r->guest_email,
                        'booking_type' => $r->booking_type,
                        'status' => $r->status,
                        'total_amount' => $r->total_amount,
                        'downpayment_verified' => $r->downpayment_verified,
                        'full_payment_verified' => $r->full_payment_verified,
                        'room' => $r->room
                    ]
                ];
            }

            return response()->json([
                'success' => true,
                'events' => $events
            ]);
        } catch (Exception $e) {
            Log::error('Admin Calendar Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function checkIn(Request $request)
    {
        if (!session('admin_logged_in') && !session('staff_logged_in')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $reservationId = $request->input('reservation_id');
        $securityBond = $request->input('security_bond_collected', 2000);
        $notes = $request->input('notes', '');

        if (!$reservationId) {
            return response()->json(['success' => false, 'message' => 'Reservation ID is required'], 400);
        }

        try {
            $reservation = Reservation::where('reservation_id', $reservationId)
                ->where('status', 'confirmed')
                ->first();

            if (!$reservation) {
                return response()->json(['success' => false, 'message' => 'Reservation not found or not confirmed'], 404);
            }

            if ($reservation->checked_in) {
                return response()->json(['success' => false, 'message' => 'Guest is already checked in'], 400);
            }

            $staffId = session('admin_id') ?: session('staff_id', 0);
            $stateManager = new ReservationStateManager((string)$staffId, 'staff');

            $result = $stateManager->transitionTo($reservationId, 'checked_in', [
                'security_bond_collected' => $securityBond,
                'admin_notes' => "Check-in completed. Security bond: ₱{$securityBond}. {$notes}"
            ]);

            return response()->json([
                'success' => $result['success'],
                'message' => 'Guest checked in successfully!',
                'checked_in_at' => now()->toDateTimeString(),
                'security_bond_collected' => $securityBond
            ]);
        } catch (Exception $e) {
            Log::error('Admin Check-In Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function checkOut(Request $request)
    {
        if (!session('admin_logged_in') && !session('staff_logged_in')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $reservationId = $request->input('reservation_id');
        $damageCharges = (float)$request->input('damage_charges', 0);
        $notes = $request->input('notes', '');

        if (!$reservationId) {
            return response()->json(['success' => false, 'message' => 'Reservation ID is required'], 400);
        }

        try {
            $reservation = Reservation::where('reservation_id', $reservationId)
                ->where('checked_in', 1)
                ->where('checked_out', 0)
                ->first();

            if (!$reservation) {
                return response()->json(['success' => false, 'message' => 'Reservation not found or not checked in'], 404);
            }

            $staffId = session('admin_id') ?: session('staff_id', 0);
            $stateManager = new ReservationStateManager((string)$staffId, 'staff');

            $result = $stateManager->transitionTo($reservationId, 'completed', [
                'damage_charges' => $damageCharges,
                'security_bond_returned' => 1,
                'security_bond_deduction' => $damageCharges,
                'actual_checkout_time' => now()->toTimeString(),
                'admin_notes' => "Check-out completed. Damage charges: ₱{$damageCharges}. {$notes}"
            ]);

            return response()->json([
                'success' => $result['success'],
                'message' => 'Guest checked out successfully!',
                'checked_out_at' => now()->toDateTimeString()
            ]);
        } catch (Exception $e) {
            Log::error('Admin Check-Out Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function markNoShow(Request $request)
    {
        if (!session('admin_logged_in') && !session('staff_logged_in')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $reservationId = $request->input('reservation_id');
        $notes = $request->input('notes', '');

        if (!$reservationId) {
            return response()->json(['success' => false, 'message' => 'Reservation ID is required'], 400);
        }

        try {
            $reservation = Reservation::where('reservation_id', $reservationId)
                ->where('status', 'confirmed')
                ->where('checked_in', 0)
                ->first();

            if (!$reservation) {
                return response()->json(['success' => false, 'message' => 'Reservation not found, not confirmed, or already checked in'], 404);
            }

            $staffId = session('admin_id') ?: session('staff_id', 0);
            $stateManager = new ReservationStateManager((string)$staffId, 'staff');

            $result = $stateManager->transitionTo($reservationId, 'no_show', [
                'admin_notes' => "Marked as no show. {$notes}"
            ]);

            return response()->json([
                'success' => $result['success'],
                'message' => 'Guest marked as no-show. Downpayment forfeited and date unlocked.',
                'forfeited_amount' => $reservation->downpayment_amount
            ]);
        } catch (Exception $e) {
            Log::error('Admin Mark No-Show Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function verifyPayment(Request $request)
    {
        if (!session('admin_logged_in') && !session('staff_logged_in')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $reservationId = $request->input('reservation_id');
        $paymentType = $request->input('payment_type', 'downpayment');
        $action = $request->input('action', 'approve');
        $rejectionReason = $request->input('rejection_reason');

        if (!$reservationId) {
            return response()->json(['success' => false, 'message' => 'Reservation ID is required'], 400);
        }

        try {
            $reservation = Reservation::find($reservationId);
            if (!$reservation) {
                return response()->json(['success' => false, 'message' => 'Reservation not found'], 404);
            }

            $adminId = session('admin_id') ?: session('staff_id', 0);

            if ($action === 'approve') {
                if ($paymentType === 'downpayment') {
                    $reservation->update([
                        'downpayment_verified' => 1,
                        'downpayment_verified_by' => $adminId,
                        'downpayment_verified_at' => now(),
                        'status' => 'confirmed',
                        'date_locked' => 1
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Downpayment approved successfully! Reservation is now confirmed and date locked.'
                    ]);
                } else {
                    $reservation->update([
                        'full_payment_verified' => 1,
                        'full_payment_verified_by' => $adminId,
                        'full_payment_verified_at' => now(),
                        'remaining_balance' => 0
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Full payment approved successfully!'
                    ]);
                }
            } else {
                // Reject
                if ($paymentType === 'downpayment') {
                    $reservation->update([
                        'downpayment_paid' => 0,
                        'downpayment_proof' => null,
                        'status' => 'pending_payment',
                        'admin_notes' => ($reservation->admin_notes ? $reservation->admin_notes . "\n" : "") . "[Rejected Downpayment: {$rejectionReason}]"
                    ]);
                } else {
                    $reservation->update([
                        'full_payment_paid' => 0,
                        'full_payment_proof' => null,
                        'admin_notes' => ($reservation->admin_notes ? $reservation->admin_notes . "\n" : "") . "[Rejected Full Payment: {$rejectionReason}]"
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => ucfirst($paymentType) . ' payment proof has been rejected.'
                ]);
            }
        } catch (Exception $e) {
            Log::error('Admin Verify Payment Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function approveRebooking(Request $request)
    {
        if (!session('admin_logged_in') && !session('staff_logged_in')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $reservationId = $request->input('reservation_id');
        $action = $request->input('action', 'approve');

        if (!$reservationId) {
            return response()->json(['success' => false, 'message' => 'Reservation ID is required'], 400);
        }

        try {
            $reservation = Reservation::find($reservationId);
            if (!$reservation) {
                return response()->json(['success' => false, 'message' => 'Reservation not found'], 404);
            }

            $adminId = session('admin_id') ?: session('staff_id', 0);

            if ($action === 'approve') {
                $newDate = $reservation->rebooking_new_date;

                $reservation->update([
                    'check_in_date' => $newDate,
                    'rebooking_approved' => 1,
                    'rebooking_approved_by' => $adminId,
                    'rebooking_approved_at' => now(),
                    'rebooking_requested' => 0,
                    'status' => 'confirmed'
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Rebooking approved successfully! Date moved to ' . $newDate
                ]);
            } else {
                $reservation->update([
                    'rebooking_requested' => 0,
                    'rebooking_approved' => 0
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Rebooking request rejected.'
                ]);
            }
        } catch (Exception $e) {
            Log::error('Approve Rebooking Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function rebookingRequests(Request $request)
    {
        if (!session('admin_logged_in') && !session('staff_logged_in')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $requests = Reservation::where('rebooking_requested', 1)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'count' => $requests->count(),
                'rebooking_requests' => $requests
            ]);
        } catch (Exception $e) {
            Log::error('Get Rebooking Requests Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
