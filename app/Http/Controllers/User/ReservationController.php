<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\User;
use App\Services\IDGeneratorService;
use App\Services\ReservationStateManager;
use App\Services\ReservationValidator;
use DateTime;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReservationController extends Controller
{
    protected ReservationValidator $validator;

    public function __construct()
    {
        $this->validator = new ReservationValidator();
    }

    public function checkAvailability(Request $request)
    {
        $checkInDate = $request->input('check_in_date');
        $bookingType = $request->input('booking_type');

        if (!$checkInDate || !$bookingType) {
            return response()->json([
                'success' => false,
                'message' => 'Check-in date and booking type are required'
            ], 400);
        }

        try {
            $lockedBooking = Reservation::where('check_in_date', $checkInDate)
                ->where('booking_type', $bookingType)
                ->whereIn('status', ['confirmed', 'checked_in', 'checked_out', 'pending_confirmation'])
                ->where(function ($query) {
                    $query->where('downpayment_verified', 1)
                          ->orWhere('date_locked', 1);
                })
                ->first();

            if ($lockedBooking) {
                return response()->json([
                    'success' => true,
                    'available' => false,
                    'locked' => true,
                    'message' => 'This date is already booked and confirmed.',
                    'locked_by' => $lockedBooking->guest_name,
                    'reservation_id' => $lockedBooking->reservation_id
                ]);
            }

            $pendingCount = Reservation::where('check_in_date', $checkInDate)
                ->where('booking_type', $bookingType)
                ->where('status', 'pending_payment')
                ->where('downpayment_verified', 0)
                ->count();

            return response()->json([
                'success' => true,
                'available' => true,
                'locked' => false,
                'message' => 'Date is available! First to pay, first to reserve.',
                'pending_reservations' => $pendingCount,
                'warning' => $pendingCount > 0 ? "Note: {$pendingCount} pending reservation(s) for this date. Complete payment quickly to secure your booking!" : null
            ]);
        } catch (Exception $e) {
            Log::error('Check Availability Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getUnavailableDates(Request $request)
    {
        $bookingType = $request->input('booking_type');

        if (!$bookingType) {
            return response()->json([
                'success' => false,
                'message' => 'Booking type is required',
                'unavailable_dates' => []
            ], 400);
        }

        try {
            $reservations = Reservation::whereIn('status', ['confirmed', 'checked_in', 'checked_out', 'pending_confirmation'])
                ->where(function ($query) {
                    $query->where('downpayment_verified', 1)
                          ->orWhere('date_locked', 1);
                })
                ->where('check_out_date', '>=', now()->toDateString())
                ->orderBy('check_in_date', 'asc')
                ->get();

            $unavailableDates = [];
            $isOvernight = in_array($bookingType, ['nighttime', '22hours', 'venue-nighttime', 'venue-22hours']);

            foreach ($reservations as $row) {
                $checkIn = new DateTime($row->check_in_date);
                $checkOut = new DateTime($row->check_out_date ?: $row->check_in_date);

                $current = clone $checkIn;
                while ($current <= $checkOut) {
                    $dateStr = $current->format('Y-m-d');
                    if (!in_array($dateStr, $unavailableDates)) {
                        $unavailableDates[] = $dateStr;
                    }
                    $current->modify('+1 day');

                    if ($checkIn->format('Y-m-d') === $checkOut->format('Y-m-d')) {
                        break;
                    }
                }

                if ($isOvernight) {
                    $dayBefore = clone $checkIn;
                    $dayBefore->modify('-1 day');
                    $dayBeforeStr = $dayBefore->format('Y-m-d');
                    if (!in_array($dayBeforeStr, $unavailableDates)) {
                        $unavailableDates[] = $dayBeforeStr;
                    }
                }
            }

            sort($unavailableDates);

            return response()->json([
                'success' => true,
                'unavailable_dates' => $unavailableDates,
                'count' => count($unavailableDates),
                'message' => count($unavailableDates) > 0 
                    ? count($unavailableDates) . ' date(s) are already booked' 
                    : 'All dates are currently available'
            ]);
        } catch (Exception $e) {
            Log::error('Unavailable Dates Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
                'unavailable_dates' => []
            ], 500);
        }
    }

    public function processCheckout(Request $request)
    {
        $userId = session('user_id');
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Please login to make a reservation',
                'error_code' => 'UNAUTHORIZED',
                'redirect' => 'index.html'
            ], 401);
        }

        $bookingType = trim($request->input('booking_type', ''));
        $packageType = trim($request->input('package_type', $bookingType . '-day'));
        $checkInDate = trim($request->input('check_in_date', ''));
        $paymentMethod = trim($request->input('payment_method', ''));
        $specialRequests = trim($request->input('special_requests', ''));
        $groupType = trim($request->input('group_type', ''));

        $groupSizeInput = $request->input('group_size', $request->input('number_of_guests', '1'));
        if (is_numeric($groupSizeInput)) {
            $numberOfGuests = (int)$groupSizeInput;
        } elseif (preg_match('/(\d+)-(\d+)/', (string)$groupSizeInput, $matches)) {
            $numberOfGuests = (int)(((int)$matches[1] + (int)$matches[2]) / 2);
        } else {
            $numberOfGuests = 1;
        }

        $config = $this->validator->getBookingConfig($bookingType);
        if (!$config) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid booking type'
            ], 400);
        }

        $duration = $config['duration_type'] === 'days' 
            ? (int)$request->input('number_of_days', 1) 
            : (int)$request->input('number_of_nights', 1);

        $validationData = [
            'booking_type' => $bookingType,
            'check_in_date' => $checkInDate,
            'payment_method' => $paymentMethod,
            'number_of_days' => $config['duration_type'] === 'days' ? $duration : null,
            'number_of_nights' => $config['duration_type'] === 'nights' ? $duration : null,
            'number_of_guests' => $numberOfGuests
        ];

        if (!$this->validator->validateNewReservation($validationData)) {
            return response()->json([
                'success' => false,
                'message' => $this->validator->getFirstError(),
                'errors' => $this->validator->getErrors(),
                'error_code' => 'VALIDATION_FAILED'
            ], 400);
        }

        try {
            return DB::transaction(function () use ($userId, $bookingType, $packageType, $checkInDate, $paymentMethod, $specialRequests, $groupType, $numberOfGuests, $config, $duration) {
                // Lock check to prevent race condition
                $locked = Reservation::where('check_in_date', $checkInDate)
                    ->where('booking_type', $bookingType)
                    ->whereIn('status', ['confirmed', 'checked_in', 'pending_confirmation'])
                    ->where(function ($query) {
                        $query->where('downpayment_verified', 1)
                              ->orWhere('date_locked', 1);
                    })
                    ->lockForUpdate()
                    ->first();

                if ($locked) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Sorry, this date has just been booked by another guest. Please select a different date.',
                        'error_code' => 'DATE_UNAVAILABLE'
                    ], 409);
                }

                $pricing = $this->validator->calculatePricing($bookingType, $duration, $packageType);
                $checkOutDate = $this->validator->calculateCheckOutDate($checkInDate, $bookingType, $duration);

                $user = User::find($userId);
                $guestName = session('user_full_name') ?: ($user ? $user->full_name : 'Guest');
                $guestEmail = session('user_email') ?: ($user ? $user->email : null);
                $guestPhone = session('user_phone') ?: ($user ? $user->phone_number : null);

                $reservationId = IDGeneratorService::generateReservationId();

                $reservation = Reservation::create([
                    'reservation_id' => $reservationId,
                    'user_id' => $userId,
                    'guest_name' => $guestName,
                    'guest_email' => $guestEmail,
                    'guest_phone' => $guestPhone,
                    'booking_type' => $bookingType,
                    'package_type' => $packageType,
                    'check_in_date' => $checkInDate,
                    'check_out_date' => $checkOutDate,
                    'check_in_time' => $pricing['check_in_time'],
                    'check_out_time' => $pricing['check_out_time'],
                    'number_of_days' => $config['duration_type'] === 'days' ? $duration : null,
                    'number_of_nights' => $config['duration_type'] === 'nights' ? $duration : null,
                    'number_of_guests' => $numberOfGuests,
                    'group_type' => $groupType ?: null,
                    'special_requests' => $specialRequests ?: null,
                    'base_price' => $pricing['base_price'],
                    'total_amount' => $pricing['total_amount'],
                    'downpayment_amount' => $pricing['downpayment_amount'],
                    'remaining_balance' => $pricing['remaining_balance'],
                    'security_bond' => $pricing['security_bond'],
                    'payment_method' => $paymentMethod,
                    'status' => 'pending_payment',
                    'locked_until' => now()->addHours(24)
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Reservation created successfully! Please complete payment to confirm your booking.',
                    'reservation_id' => $reservationId,
                    'booking_details' => [
                        'check_in_date' => $checkInDate,
                        'check_out_date' => $checkOutDate,
                        'check_in_time' => $pricing['check_in_time'],
                        'check_out_time' => $pricing['check_out_time'],
                        'booking_type' => $bookingType,
                        'duration' => $duration,
                        'duration_type' => $pricing['duration_type'],
                        'number_of_guests' => $numberOfGuests
                    ],
                    'pricing' => [
                        'base_price' => $pricing['base_price'],
                        'total_amount' => $pricing['total_amount'],
                        'downpayment_amount' => $pricing['downpayment_amount'],
                        'remaining_balance' => $pricing['remaining_balance'],
                        'security_bond' => $pricing['security_bond']
                    ],
                    'payment_method' => $paymentMethod,
                    'expires_at' => now()->addHours(24)->toDateTimeString(),
                    'policy' => [
                        'downpayment_required' => '₱' . number_format($pricing['downpayment_amount'], 2),
                        'balance_due' => 'Before check-in',
                        'security_bond' => '₱' . number_format($pricing['security_bond'], 2),
                        'refund_policy' => 'Non-refundable, rebookable within 3 months (7 days notice required)'
                    ]
                ]);
            });
        } catch (Exception $e) {
            Log::error('Process Checkout Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to process reservation: ' . $e->getMessage(),
                'error_code' => 'PROCESSING_FAILED'
            ], 500);
        }
    }

    public function makeReservation(Request $request)
    {
        return $this->processCheckout($request);
    }

    public function myReservations(Request $request)
    {
        $userId = session('user_id');
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $userEmail = session('user_email', session('email'));
        $statusFilter = $request->query('status', 'all');

        try {
            $query = Reservation::where(function ($q) use ($userId, $userEmail) {
                $q->where('user_id', $userId);
                if ($userEmail) {
                    $q->orWhere('guest_email', $userEmail);
                }
            });

            if ($statusFilter !== 'all') {
                $query->where('status', $statusFilter);
            }

            $reservations = $query->orderBy('check_in_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($res) {
                    $item = $res->toArray();
                    $item['downpayment_status'] = $res->downpayment_verified ? 'Verified' : ($res->downpayment_paid ? 'Pending Verification' : 'Unpaid');
                    $item['full_payment_status'] = $res->full_payment_verified ? 'Verified' : ($res->full_payment_paid ? 'Pending Verification' : 'Not Paid');
                    $item['status_label'] = ReservationStateManager::getStatusLabel($res->status);
                    return $item;
                });

            return response()->json([
                'success' => true,
                'count' => $reservations->count(),
                'reservations' => $reservations
            ]);
        } catch (Exception $e) {
            Log::error('My Reservations Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function cancel(Request $request)
    {
        $userId = session('user_id');
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $reservationId = $request->input('reservation_id');
        $reason = $request->input('reason', '');

        if (!$reservationId) {
            return response()->json(['success' => false, 'message' => 'Reservation ID is required'], 400);
        }

        try {
            $reservation = Reservation::where('reservation_id', $reservationId)
                ->where('user_id', $userId)
                ->first();

            if (!$reservation) {
                return response()->json(['success' => false, 'message' => 'Reservation not found'], 404);
            }

            if ($reservation->downpayment_verified || $reservation->full_payment_verified || $reservation->status === 'confirmed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cancellation is not allowed. Downpayment is non-refundable/non-transferable. Please request REBOOKING (within 3 months) instead.'
                ], 400);
            }

            if ($reservation->status !== 'pending_payment' || $reservation->downpayment_verified) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot cancel this reservation. Please contact us for rebooking options.'
                ], 400);
            }

            $stateManager = new ReservationStateManager($userId, 'user');
            $result = $stateManager->transitionTo($reservationId, 'cancelled', [
                'cancellation_reason' => $reason
            ]);

            return response()->json([
                'success' => $result['success'],
                'message' => 'Reservation cancelled successfully.',
                'downpayment_forfeited' => (bool)$reservation->downpayment_paid,
                'downpayment_amount' => $reservation->downpayment_amount
            ]);
        } catch (Exception $e) {
            Log::error('Cancel Reservation Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function requestRebooking(Request $request)
    {
        $userId = session('user_id');
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Please login first'], 401);
        }

        $reservationId = $request->input('reservation_id');
        $newDate = $request->input('new_date');
        $reason = $request->input('reason', '');

        if (!$reservationId || !$newDate) {
            return response()->json(['success' => false, 'message' => 'Reservation ID and new date are required'], 400);
        }

        try {
            $reservation = Reservation::where('reservation_id', $reservationId)
                ->where('user_id', $userId)
                ->first();

            if (!$reservation) {
                return response()->json(['success' => false, 'message' => 'Reservation not found'], 404);
            }

            if (!$this->validator->canRebook($reservation)) {
                return response()->json([
                    'success' => false,
                    'message' => $this->validator->getFirstError()
                ], 400);
            }

            if (!$this->validator->validateRebookingDate($newDate, $reservation->booking_type)) {
                return response()->json([
                    'success' => false,
                    'message' => $this->validator->getFirstError()
                ], 400);
            }

            $reservation->update([
                'rebooking_requested' => 1,
                'rebooking_new_date' => $newDate,
                'rebooking_reason' => $reason,
                'rebooking_approved' => 0
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Rebooking request submitted successfully! Waiting for admin approval.',
                'reservation_id' => $reservationId,
                'new_date' => $newDate
            ]);
        } catch (Exception $e) {
            Log::error('Request Rebooking Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function myRebookingRequests(Request $request)
    {
        $userId = session('user_id');
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $requests = Reservation::where('user_id', $userId)
                ->where('rebooking_requested', 1)
                ->orderBy('updated_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'rebooking_requests' => $requests
            ]);
        } catch (Exception $e) {
            Log::error('My Rebooking Requests Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function acceptTerms(Request $request)
    {
        $userId = session('user_id');
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $reservationId = $request->input('reservation_id');
        $termsAccepted = $request->input('terms_accepted', false);

        if (!$reservationId || !$termsAccepted) {
            return response()->json([
                'success' => false,
                'message' => 'You must accept the terms and conditions'
            ], 400);
        }

        try {
            $reservation = Reservation::where('reservation_id', $reservationId)
                ->where('user_id', $userId)
                ->first();

            if (!$reservation) {
                return response()->json(['success' => false, 'message' => 'Reservation not found'], 404);
            }

            $ipAddress = $request->ip();

            $reservation->update([
                'terms_accepted' => 1,
                'terms_accepted_at' => now(),
                'terms_ip_address' => $ipAddress
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Terms and conditions accepted',
                'accepted_at' => now()->toDateTimeString(),
                'ip_address' => $ipAddress
            ]);
        } catch (Exception $e) {
            Log::error('Accept Terms Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
