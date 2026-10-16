<?php

namespace App\Services;

use App\Models\Reservation;
use DateTime;
use InvalidArgumentException;

class ReservationValidator
{
    private array $errors = [];

    const STATUS_PENDING_PAYMENT = 'pending_payment';
    const STATUS_PENDING_CONFIRMATION = 'pending_confirmation';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_CHECKED_IN = 'checked_in';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_NO_SHOW = 'no_show';
    const STATUS_EXPIRED = 'expired';
    const STATUS_REBOOKED = 'rebooked';

    const VALID_TRANSITIONS = [
        self::STATUS_PENDING_PAYMENT => [self::STATUS_PENDING_CONFIRMATION, self::STATUS_CANCELLED, self::STATUS_EXPIRED],
        self::STATUS_PENDING_CONFIRMATION => [self::STATUS_CONFIRMED, self::STATUS_CANCELLED],
        self::STATUS_CONFIRMED => [self::STATUS_CHECKED_IN, self::STATUS_CANCELLED, self::STATUS_NO_SHOW, self::STATUS_REBOOKED],
        self::STATUS_CHECKED_IN => [self::STATUS_COMPLETED],
        self::STATUS_COMPLETED => [],
        self::STATUS_CANCELLED => [],
        self::STATUS_NO_SHOW => [],
        self::STATUS_EXPIRED => [self::STATUS_PENDING_PAYMENT],
        self::STATUS_REBOOKED => [self::STATUS_CONFIRMED, self::STATUS_CANCELLED],
    ];

    const BOOKING_CONFIGS = [
        'daytime' => [
            'check_in_time' => '09:00:00',
            'check_out_time' => '17:00:00',
            'duration_type' => 'days',
            'base_price' => 6000.00,
            'security_bond' => 2000.00,
            'overtime_charge' => 500.00,
            'min_duration' => 1,
            'max_duration' => 7
        ],
        'nighttime' => [
            'check_in_time' => '19:00:00',
            'check_out_time' => '07:00:00',
            'duration_type' => 'nights',
            'base_price' => 10000.00,
            'security_bond' => 2000.00,
            'overtime_charge' => 1000.00,
            'min_duration' => 1,
            'max_duration' => 7
        ],
        '22hours' => [
            'check_in_time' => '14:00:00',
            'check_out_time' => '12:00:00',
            'duration_type' => 'nights',
            'base_price' => 18000.00,
            'security_bond' => 2000.00,
            'overtime_charge' => 500.00,
            'min_duration' => 1,
            'max_duration' => 7
        ],
        'venue-daytime' => [
            'check_in_time' => '09:00:00',
            'check_out_time' => '17:00:00',
            'duration_type' => 'days',
            'base_price' => 6000.00,
            'security_bond' => 2000.00,
            'overtime_charge' => 500.00,
            'min_duration' => 1,
            'max_duration' => 3
        ],
        'venue-nighttime' => [
            'check_in_time' => '19:00:00',
            'check_out_time' => '07:00:00',
            'duration_type' => 'nights',
            'base_price' => 10000.00,
            'security_bond' => 2000.00,
            'overtime_charge' => 1000.00,
            'min_duration' => 1,
            'max_duration' => 3
        ],
        'venue-22hours' => [
            'check_in_time' => '14:00:00',
            'check_out_time' => '12:00:00',
            'duration_type' => 'nights',
            'base_price' => 18000.00,
            'security_bond' => 2000.00,
            'overtime_charge' => 500.00,
            'min_duration' => 1,
            'max_duration' => 3
        ]
    ];

    const DOWNPAYMENT_AMOUNT = 1000.00;
    const RESERVATION_EXPIRY_HOURS = 24;
    const MIN_REBOOKING_DAYS = 7;
    const MAX_REBOOKING_MONTHS = 3;

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getFirstError(): ?string
    {
        return $this->errors[0] ?? null;
    }

    public function clearErrors(): void
    {
        $this->errors = [];
    }

    public function addError(string $error): void
    {
        $this->errors[] = $error;
    }

    public function validateBookingType(?string $bookingType): bool
    {
        if (empty($bookingType)) {
            $this->addError('Booking type is required');
            return false;
        }

        if (!isset(self::BOOKING_CONFIGS[$bookingType])) {
            $this->addError('Invalid booking type');
            return false;
        }

        return true;
    }

    public function validateCheckInDate(?string $date, ?string $bookingType = null): bool
    {
        if (empty($date)) {
            $this->addError('Check-in date is required');
            return false;
        }

        $dateTimestamp = strtotime($date);
        if (!$dateTimestamp || date('Y-m-d', $dateTimestamp) !== $date) {
            $this->addError('Invalid date format. Please use YYYY-MM-DD');
            return false;
        }

        $today = new DateTime('today');
        $checkInDate = new DateTime($date);

        if ($checkInDate < $today) {
            $this->addError('Check-in date cannot be in the past');
            return false;
        }

        $maxDate = (clone $today)->modify('+1 year');
        if ($checkInDate > $maxDate) {
            $this->addError('Cannot book more than 1 year in advance');
            return false;
        }

        return true;
    }

    public function checkDateAvailability(string $checkInDate, string $bookingType, ?string $excludeReservationId = null): array
    {
        $lockedQuery = Reservation::where('check_in_date', $checkInDate)
            ->where('booking_type', $bookingType)
            ->whereIn('status', ['confirmed', 'checked_in', 'pending_confirmation'])
            ->where(function ($query) {
                $query->where('downpayment_verified', 1)
                      ->orWhere('date_locked', 1);
            });

        if ($excludeReservationId) {
            $lockedQuery->where('reservation_id', '!=', $excludeReservationId);
        }

        $lockedBooking = $lockedQuery->first();

        if ($lockedBooking) {
            return [
                'available' => false,
                'locked' => true,
                'pending_count' => 0,
                'message' => 'This date is already booked and confirmed.',
                'locked_by' => $lockedBooking->guest_name ?? 'Another guest'
            ];
        }

        $pendingQuery = Reservation::where('check_in_date', $checkInDate)
            ->where('booking_type', $bookingType)
            ->where('status', 'pending_payment')
            ->where('downpayment_verified', 0);

        if ($excludeReservationId) {
            $pendingQuery->where('reservation_id', '!=', $excludeReservationId);
        }

        $pendingCount = $pendingQuery->count();

        $message = 'Date is available! First to pay, first to reserve.';
        $warning = null;

        if ($pendingCount > 0) {
            $warning = "Note: {$pendingCount} pending reservation(s) for this date. Complete payment quickly to secure your booking!";
        }

        return [
            'available' => true,
            'locked' => false,
            'pending_count' => $pendingCount,
            'message' => $message,
            'warning' => $warning
        ];
    }

    public function validateDuration(?int $duration, string $bookingType): bool
    {
        if (!isset(self::BOOKING_CONFIGS[$bookingType])) {
            $this->addError('Invalid booking type');
            return false;
        }

        $config = self::BOOKING_CONFIGS[$bookingType];

        if ($duration === null || $duration < $config['min_duration']) {
            $durationType = $config['duration_type'];
            $this->addError("Minimum duration is {$config['min_duration']} {$durationType}");
            return false;
        }

        if ($duration > $config['max_duration']) {
            $durationType = $config['duration_type'];
            $this->addError("Maximum duration is {$config['max_duration']} {$durationType}");
            return false;
        }

        return true;
    }

    public function validateGuestCount(?int $guestCount, int $min = 1, int $max = 100): bool
    {
        if ($guestCount === null || $guestCount < $min) {
            $this->addError("Minimum guest count is {$min}");
            return false;
        }

        if ($guestCount > $max) {
            $this->addError("Maximum guest count is {$max}");
            return false;
        }

        return true;
    }

    public function validatePaymentMethod(?string $method): bool
    {
        $validMethods = [
            'gcash', 'paymaya', 'grab_pay', 'card', 
            'dob_bpi', 'dob_ubp', 'atome', 'otc',
            'bank_transfer', 'cash', 'online_banking'
        ];

        if (empty($method)) {
            $this->addError('Payment method is required');
            return false;
        }

        if (!in_array($method, $validMethods)) {
            $this->addError('Invalid payment method');
            return false;
        }

        return true;
    }

    public function canTransitionTo(string $currentStatus, string $newStatus): bool
    {
        if (!isset(self::VALID_TRANSITIONS[$currentStatus])) {
            $this->addError('Invalid current status');
            return false;
        }

        if (!in_array($newStatus, self::VALID_TRANSITIONS[$currentStatus])) {
            $this->addError("Cannot transition from '{$currentStatus}' to '{$newStatus}'");
            return false;
        }

        return true;
    }

    public function canCancel($reservation): bool
    {
        $checkedIn = is_array($reservation) ? ($reservation['checked_in'] ?? 0) : $reservation->checked_in;
        $downpaymentVerified = is_array($reservation) ? ($reservation['downpayment_verified'] ?? 0) : $reservation->downpayment_verified;
        $status = is_array($reservation) ? ($reservation['status'] ?? '') : $reservation->status;

        if ($checkedIn == 1) {
            $this->addError('Cannot cancel after check-in');
            return false;
        }

        if ($downpaymentVerified == 1) {
            $this->addError('Cancellation not allowed after payment verification. Please request rebooking instead.');
            return false;
        }

        if ($status === self::STATUS_CANCELLED) {
            $this->addError('Reservation is already cancelled');
            return false;
        }

        $cancellableStatuses = [self::STATUS_PENDING_PAYMENT, self::STATUS_PENDING_CONFIRMATION];
        if (!in_array($status, $cancellableStatuses)) {
            $this->addError('This reservation cannot be cancelled');
            return false;
        }

        return true;
    }

    public function canRebook($reservation): bool
    {
        $downpaymentVerified = is_array($reservation) ? ($reservation['downpayment_verified'] ?? 0) : $reservation->downpayment_verified;
        $checkedIn = is_array($reservation) ? ($reservation['checked_in'] ?? 0) : $reservation->checked_in;
        $rebookingRequested = is_array($reservation) ? ($reservation['rebooking_requested'] ?? 0) : $reservation->rebooking_requested;
        $rebookingApproved = is_array($reservation) ? ($reservation['rebooking_approved'] ?? 0) : $reservation->rebooking_approved;
        $checkInDateStr = is_array($reservation) ? ($reservation['check_in_date'] ?? null) : $reservation->check_in_date;
        $status = is_array($reservation) ? ($reservation['status'] ?? '') : $reservation->status;

        if ($downpaymentVerified != 1) {
            $this->addError('Downpayment must be verified before rebooking');
            return false;
        }

        if ($checkedIn == 1) {
            $this->addError('Cannot rebook after check-in');
            return false;
        }

        if ($rebookingRequested == 1 && !$rebookingApproved) {
            $this->addError('A rebooking request is already pending');
            return false;
        }

        $checkInDate = new DateTime($checkInDateStr);
        $today = new DateTime('today');
        $daysUntil = (int)$today->diff($checkInDate)->format('%r%a');

        if ($daysUntil < self::MIN_REBOOKING_DAYS) {
            $this->addError('Rebooking must be requested at least ' . self::MIN_REBOOKING_DAYS . ' days before check-in');
            return false;
        }

        $rebookableStatuses = [self::STATUS_CONFIRMED, self::STATUS_PENDING_CONFIRMATION, self::STATUS_REBOOKED];
        if (!in_array($status, $rebookableStatuses)) {
            $this->addError('This reservation cannot be rebooked');
            return false;
        }

        return true;
    }

    public function validateRebookingDate(string $newDate, string $bookingType): bool
    {
        if (!$this->validateCheckInDate($newDate, $bookingType)) {
            return false;
        }

        $maxDate = new DateTime();
        $maxDate->modify('+' . self::MAX_REBOOKING_MONTHS . ' months');
        $newCheckIn = new DateTime($newDate);

        if ($newCheckIn > $maxDate) {
            $this->addError('New date must be within ' . self::MAX_REBOOKING_MONTHS . ' months from today');
            return false;
        }

        $availability = $this->checkDateAvailability($newDate, $bookingType);
        if (!$availability['available']) {
            $this->addError($availability['message']);
            return false;
        }

        return true;
    }

    public function calculatePricing(string $bookingType, int $duration, ?string $packageType = null): array
    {
        if (!isset(self::BOOKING_CONFIGS[$bookingType])) {
            throw new InvalidArgumentException('Invalid booking type');
        }

        $config = self::BOOKING_CONFIGS[$bookingType];
        $basePrice = $config['base_price'];
        $totalAmount = $basePrice * $duration;

        return [
            'base_price' => $basePrice,
            'duration' => $duration,
            'duration_type' => $config['duration_type'],
            'total_amount' => $totalAmount,
            'downpayment_amount' => self::DOWNPAYMENT_AMOUNT,
            'remaining_balance' => $totalAmount - self::DOWNPAYMENT_AMOUNT,
            'security_bond' => $config['security_bond'],
            'overtime_charge_per_hour' => $config['overtime_charge'],
            'check_in_time' => $config['check_in_time'],
            'check_out_time' => $config['check_out_time']
        ];
    }

    public function calculateCheckOutDate(string $checkInDate, string $bookingType, int $duration): string
    {
        if (!isset(self::BOOKING_CONFIGS[$bookingType])) {
            throw new InvalidArgumentException('Invalid booking type');
        }

        $config = self::BOOKING_CONFIGS[$bookingType];
        $checkIn = new DateTime($checkInDate);

        if ($config['duration_type'] === 'days') {
            $checkIn->modify('+' . ($duration - 1) . ' days');
        } else {
            $checkIn->modify('+' . $duration . ' days');
        }

        return $checkIn->format('Y-m-d');
    }

    public function getBookingConfig(string $bookingType): ?array
    {
        return self::BOOKING_CONFIGS[$bookingType] ?? null;
    }

    public function hasExpired($reservation): bool
    {
        $status = is_array($reservation) ? ($reservation['status'] ?? '') : $reservation->status;
        $downpaymentPaid = is_array($reservation) ? ($reservation['downpayment_paid'] ?? 0) : $reservation->downpayment_paid;
        $createdAt = is_array($reservation) ? ($reservation['created_at'] ?? null) : $reservation->created_at;

        if ($status !== self::STATUS_PENDING_PAYMENT) {
            return false;
        }

        if ($downpaymentPaid == 1) {
            return false;
        }

        $created = new DateTime($createdAt);
        $now = new DateTime();
        $hoursDiff = ($now->getTimestamp() - $created->getTimestamp()) / 3600;

        return $hoursDiff > self::RESERVATION_EXPIRY_HOURS;
    }

    public function validateNewReservation(array $data): bool
    {
        $this->clearErrors();

        $requiredFields = ['booking_type', 'check_in_date', 'payment_method'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $this->addError("Missing required field: {$field}");
            }
        }

        if (!empty($this->errors)) {
            return false;
        }

        if (!$this->validateBookingType($data['booking_type'])) {
            return false;
        }

        if (!$this->validateCheckInDate($data['check_in_date'], $data['booking_type'])) {
            return false;
        }

        if (!$this->validatePaymentMethod($data['payment_method'])) {
            return false;
        }

        $config = self::BOOKING_CONFIGS[$data['booking_type']];
        $durationKey = $config['duration_type'] === 'days' ? 'number_of_days' : 'number_of_nights';
        $duration = isset($data[$durationKey]) ? (int)$data[$durationKey] : 1;

        if (!$this->validateDuration($duration, $data['booking_type'])) {
            return false;
        }

        if (isset($data['number_of_guests'])) {
            $guestCount = (int)$data['number_of_guests'];
            if (!$this->validateGuestCount($guestCount)) {
                return false;
            }
        }

        $availability = $this->checkDateAvailability($data['check_in_date'], $data['booking_type']);
        if (!$availability['available']) {
            $this->addError($availability['message']);
            return false;
        }

        return true;
    }
}
