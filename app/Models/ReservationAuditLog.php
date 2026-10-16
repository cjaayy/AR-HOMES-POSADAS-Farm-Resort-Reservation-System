<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReservationAuditLog extends Model
{
    use HasFactory;

    protected $table = 'reservation_audit_log';
    protected $primaryKey = 'id';
    const UPDATED_AT = null;

    protected $fillable = [
        'reservation_id',
        'action',
        'previous_status',
        'new_status',
        'actor_id',
        'actor_type',
        'details',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'reservation_id', 'reservation_id');
    }
}
