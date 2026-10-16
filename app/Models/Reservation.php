<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $table = 'reservations';
    protected $primaryKey = 'reservation_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'reservation_id',
        'user_id',
        'guest_name',
        'guest_email',
        'guest_phone',
        'booking_type',
        'package_type',
        'room',
        'check_in_date',
        'check_out_date',
        'check_in_time',
        'check_out_time',
        'number_of_days',
        'number_of_nights',
        'number_of_guests',
        'group_type',
        'special_requests',
        'base_price',
        'total_amount',
        'downpayment_amount',
        'remaining_balance',
        'security_bond',
        'overtime_hours',
        'overtime_charges',
        'damage_charges',
        'final_amount',
        'downpayment_paid',
        'downpayment_proof',
        'downpayment_reference',
        'downpayment_paid_at',
        'downpayment_verified',
        'downpayment_verified_by',
        'downpayment_verified_at',
        'full_payment_paid',
        'full_payment_proof',
        'full_payment_reference',
        'paymongo_full_payment_id',
        'full_payment_paid_at',
        'full_payment_verified',
        'full_payment_verified_by',
        'full_payment_verified_at',
        'payment_method',
        'paymongo_source_id',
        'paymongo_payment_id',
        'paymongo_payment_type',
        'security_bond_paid',
        'security_bond_paid_at',
        'security_bond_returned',
        'security_bond_returned_at',
        'security_bond_deduction',
        'checked_in',
        'checked_in_at',
        'checked_in_by',
        'checked_out',
        'checked_out_at',
        'checked_out_by',
        'actual_checkout_time',
        'status',
        'date_locked',
        'locked_until',
        'terms_accepted',
        'terms_accepted_at',
        'terms_ip_address',
        'rebooking_requested',
        'rebooking_reason',
        'rebooking_new_date',
        'rebooking_approved',
        'rebooking_approved_by',
        'rebooking_approved_at',
        'original_booking_id',
        'cancellation_reason',
        'cancelled_at',
        'cancelled_by',
        'no_show_marked_at',
        'no_show_marked_by',
        'admin_notes',
        'staff_notes',
        'submission_token',
        'created_ip',
    ];

    protected $casts = [
        'check_in_date' => 'date',
        'check_out_date' => 'date',
        'rebooking_new_date' => 'date',
        'base_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'downpayment_amount' => 'decimal:2',
        'remaining_balance' => 'decimal:2',
        'security_bond' => 'decimal:2',
        'overtime_charges' => 'decimal:2',
        'damage_charges' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'security_bond_deduction' => 'decimal:2',
        'downpayment_paid' => 'boolean',
        'downpayment_verified' => 'boolean',
        'full_payment_paid' => 'boolean',
        'full_payment_verified' => 'boolean',
        'security_bond_paid' => 'boolean',
        'security_bond_returned' => 'boolean',
        'checked_in' => 'boolean',
        'checked_out' => 'boolean',
        'date_locked' => 'boolean',
        'terms_accepted' => 'boolean',
        'rebooking_requested' => 'boolean',
        'rebooking_approved' => 'boolean',
        'downpayment_paid_at' => 'datetime',
        'downpayment_verified_at' => 'datetime',
        'full_payment_paid_at' => 'datetime',
        'full_payment_verified_at' => 'datetime',
        'security_bond_paid_at' => 'datetime',
        'security_bond_returned_at' => 'datetime',
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
        'locked_until' => 'datetime',
        'terms_accepted_at' => 'datetime',
        'rebooking_approved_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'no_show_marked_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function review()
    {
        return $this->hasOne(Review::class, 'reservation_id', 'reservation_id');
    }

    public function auditLogs()
    {
        return $this->hasMany(ReservationAuditLog::class, 'reservation_id', 'reservation_id');
    }

    public function roomInventory()
    {
        return $this->hasOne(RoomInventory::class, 'current_reservation_id', 'reservation_id');
    }
}
