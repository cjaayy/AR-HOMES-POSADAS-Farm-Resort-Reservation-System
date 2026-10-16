<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\ReservationAuditLog;
use Exception;
use Illuminate\Support\Facades\Log;

class ReservationStateManager
{
    private ?string $actorId;
    private string $actorType;
    private ReservationValidator $validator;

    const STATUS_LABELS = [
        'pending_payment' => 'Pending Payment',
        'pending_confirmation' => 'Awaiting Confirmation',
        'confirmed' => 'Confirmed',
        'checked_in' => 'Checked In',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'no_show' => 'No Show',
        'expired' => 'Expired',
        'rebooked' => 'Rebooked'
    ];

    const STATUS_COLORS = [
        'pending_payment' => '#fbbf24',
        'pending_confirmation' => '#fb923c',
        'confirmed' => '#22c55e',
        'checked_in' => '#3b82f6',
        'completed' => '#10b981',
        'cancelled' => '#ef4444',
        'no_show' => '#6b7280',
        'expired' => '#9ca3af',
        'rebooked' => '#8b5cf6'
    ];

    public function __construct(?string $actorId = null, string $actorType = 'system')
    {
        $this->actorId = $actorId;
        $this->actorType = $actorType;
        $this->validator = new ReservationValidator();
    }

    public static function getStatusLabel(string $status): string
    {
        return self::STATUS_LABELS[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }

    public static function getStatusColor(string $status): string
    {
        return self::STATUS_COLORS[$status] ?? '#6b7280';
    }

    public function transitionTo(string $reservationId, string $newStatus, array $additionalData = []): array
    {
        try {
            $reservation = Reservation::find($reservationId);
            if (!$reservation) {
                return [
                    'success' => false,
                    'message' => 'Reservation not found',
                    'error_code' => 'NOT_FOUND'
                ];
            }

            $currentStatus = $reservation->status;

            if (!$this->validator->canTransitionTo($currentStatus, $newStatus)) {
                return [
                    'success' => false,
                    'message' => $this->validator->getFirstError(),
                    'error_code' => 'INVALID_TRANSITION'
                ];
            }

            $updateFields = [
                'status' => $newStatus,
            ];

            switch ($newStatus) {
                case 'pending_confirmation':
                    if (!empty($additionalData['downpayment_proof'])) {
                        $updateFields['downpayment_proof'] = $additionalData['downpayment_proof'];
                        $updateFields['downpayment_paid'] = 1;
                        $updateFields['downpayment_paid_at'] = now();
                    }
                    if (!empty($additionalData['downpayment_reference'])) {
                        $updateFields['downpayment_reference'] = $additionalData['downpayment_reference'];
                    }
                    if (!empty($additionalData['payment_method'])) {
                        $updateFields['payment_method'] = $additionalData['payment_method'];
                    }
                    break;

                case 'confirmed':
                    $updateFields['downpayment_verified'] = 1;
                    $updateFields['downpayment_verified_at'] = now();
                    $updateFields['downpayment_verified_by'] = $this->actorId;
                    $updateFields['date_locked'] = 1;
                    break;

                case 'checked_in':
                    $updateFields['checked_in'] = 1;
                    $updateFields['checked_in_at'] = now();
                    $updateFields['checked_in_by'] = $this->actorId;
                    if (isset($additionalData['security_bond_collected'])) {
                        $updateFields['security_bond_paid'] = 1;
                        $updateFields['security_bond_paid_at'] = now();
                    }
                    break;

                case 'completed':
                    $updateFields['checked_out'] = 1;
                    $updateFields['checked_out_at'] = now();
                    $updateFields['checked_out_by'] = $this->actorId;
                    if (isset($additionalData['actual_checkout_time'])) {
                        $updateFields['actual_checkout_time'] = $additionalData['actual_checkout_time'];
                    }
                    if (isset($additionalData['overtime_hours'])) {
                        $updateFields['overtime_hours'] = $additionalData['overtime_hours'];
                    }
                    if (isset($additionalData['overtime_charges'])) {
                        $updateFields['overtime_charges'] = $additionalData['overtime_charges'];
                    }
                    if (isset($additionalData['damage_charges'])) {
                        $updateFields['damage_charges'] = $additionalData['damage_charges'];
                    }
                    if (isset($additionalData['final_amount'])) {
                        $updateFields['final_amount'] = $additionalData['final_amount'];
                    }
                    if (isset($additionalData['security_bond_returned'])) {
                        $updateFields['security_bond_returned'] = 1;
                        $updateFields['security_bond_returned_at'] = now();
                        $updateFields['security_bond_deduction'] = $additionalData['security_bond_deduction'] ?? 0;
                    }
                    break;

                case 'cancelled':
                    $updateFields['cancelled_at'] = now();
                    $updateFields['cancelled_by'] = $this->actorId;
                    if (!empty($additionalData['cancellation_reason'])) {
                        $updateFields['cancellation_reason'] = $additionalData['cancellation_reason'];
                    }
                    $updateFields['date_locked'] = 0;
                    $updateFields['locked_until'] = null;
                    break;

                case 'no_show':
                    $updateFields['no_show_marked_at'] = now();
                    $updateFields['no_show_marked_by'] = $this->actorId;
                    break;

                case 'expired':
                    $updateFields['date_locked'] = 0;
                    $updateFields['locked_until'] = null;
                    break;

                case 'rebooked':
                    if (!empty($additionalData['new_check_in_date'])) {
                        $updateFields['rebooking_requested'] = 1;
                        $updateFields['rebooking_new_date'] = $additionalData['new_check_in_date'];
                    }
                    if (!empty($additionalData['rebooking_reason'])) {
                        $updateFields['rebooking_reason'] = $additionalData['rebooking_reason'];
                    }
                    break;
            }

            if (!empty($additionalData['admin_notes'])) {
                $prefix = "[" . now()->toDateTimeString() . "] [{$this->actorType}] ";
                $existing = $reservation->admin_notes ? $reservation->admin_notes . "\n" : "";
                $updateFields['admin_notes'] = $existing . $prefix . $additionalData['admin_notes'];
            }

            $reservation->update($updateFields);

            $this->logTransition($reservationId, $currentStatus, $newStatus, $additionalData);

            return [
                'success' => true,
                'message' => 'Status updated successfully',
                'previous_status' => $currentStatus,
                'new_status' => $newStatus,
                'reservation' => $reservation->fresh()->toArray()
            ];

        } catch (Exception $e) {
            Log::error("State transition error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update status',
                'error_code' => 'UPDATE_FAILED',
                'error' => $e->getMessage()
            ];
        }
    }

    private function logTransition(string $reservationId, string $fromStatus, string $toStatus, array $additionalData = []): void
    {
        try {
            ReservationAuditLog::create([
                'reservation_id' => $reservationId,
                'action' => 'status_change',
                'previous_status' => $fromStatus,
                'new_status' => $toStatus,
                'actor_id' => $this->actorId,
                'actor_type' => $this->actorType,
                'details' => $additionalData,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
        } catch (Exception $e) {
            Log::error("Failed to write audit log: " . $e->getMessage());
        }
    }

    public function recordPayment(string $reservationId, string $paymentType, array $paymentData): array
    {
        $reservation = Reservation::find($reservationId);
        if (!$reservation) {
            return ['success' => false, 'message' => 'Reservation not found'];
        }

        $updateFields = [];
        $newStatus = null;

        if ($paymentType === 'downpayment') {
            if ($reservation->downpayment_paid) {
                return ['success' => false, 'message' => 'Downpayment already recorded'];
            }

            $updateFields = [
                'downpayment_paid' => 1,
                'downpayment_paid_at' => now(),
                'downpayment_proof' => $paymentData['proof'] ?? null,
                'downpayment_reference' => $paymentData['reference'] ?? null,
                'payment_method' => $paymentData['method'] ?? null
            ];
            $newStatus = 'pending_confirmation';

        } elseif ($paymentType === 'full_payment') {
            if ($reservation->full_payment_paid) {
                return ['success' => false, 'message' => 'Full payment already recorded'];
            }

            $updateFields = [
                'full_payment_paid' => 1,
                'full_payment_paid_at' => now(),
                'full_payment_proof' => $paymentData['proof'] ?? null,
                'full_payment_reference' => $paymentData['reference'] ?? null
            ];
        }

        if (empty($updateFields)) {
            return ['success' => false, 'message' => 'Invalid payment type'];
        }

        if ($newStatus) {
            $updateFields['status'] = $newStatus;
        }

        $reservation->update($updateFields);

        return [
            'success' => true,
            'message' => ucfirst($paymentType) . ' recorded successfully',
            'new_status' => $newStatus
        ];
    }

    public function verifyPayment(string $reservationId, string $paymentType): array
    {
        $reservation = Reservation::find($reservationId);
        if (!$reservation) {
            return ['success' => false, 'message' => 'Reservation not found'];
        }

        if ($paymentType === 'downpayment') {
            if (!$reservation->downpayment_paid) {
                return ['success' => false, 'message' => 'Downpayment has not been paid'];
            }
            if ($reservation->downpayment_verified) {
                return ['success' => false, 'message' => 'Downpayment already verified'];
            }

            return $this->transitionTo($reservationId, 'confirmed', [
                'admin_notes' => 'Downpayment verified'
            ]);

        } elseif ($paymentType === 'full_payment') {
            if (!$reservation->full_payment_paid) {
                return ['success' => false, 'message' => 'Full payment has not been paid'];
            }
            if ($reservation->full_payment_verified) {
                return ['success' => false, 'message' => 'Full payment already verified'];
            }

            $reservation->update([
                'full_payment_verified' => 1,
                'full_payment_verified_at' => now(),
                'full_payment_verified_by' => $this->actorId,
                'remaining_balance' => 0
            ]);

            return [
                'success' => true,
                'message' => 'Full payment verified successfully'
            ];
        }

        return ['success' => false, 'message' => 'Invalid payment type'];
    }
}
