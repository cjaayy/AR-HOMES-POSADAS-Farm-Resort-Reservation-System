<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomInventory extends Model
{
    use HasFactory;

    protected $table = 'room_inventory';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'room_number',
        'room_type',
        'status',
        'current_reservation_id',
        'last_updated',
    ];

    protected $casts = [
        'last_updated' => 'datetime',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'current_reservation_id', 'reservation_id');
    }
}
