<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RateLimit extends Model
{
    use HasFactory;

    protected $table = 'rate_limits';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'identifier',
        'action',
        'attempts',
        'first_attempt',
        'last_attempt',
        'blocked_until',
    ];

    protected $casts = [
        'attempts' => 'integer',
        'first_attempt' => 'datetime',
        'last_attempt' => 'datetime',
        'blocked_until' => 'datetime',
    ];
}
