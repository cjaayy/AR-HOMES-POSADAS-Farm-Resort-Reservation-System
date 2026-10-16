<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'username',
        'email',
        'email_verified',
        'email_verification_token',
        'email_verification_expires',
        'password_hash',
        'reset_token',
        'reset_token_expires',
        'last_name',
        'given_name',
        'middle_name',
        'full_name',
        'phone_number',
        'is_active',
        'member_since',
        'loyalty_level',
        'last_login',
    ];

    protected $hidden = [
        'password_hash',
        'email_verification_token',
        'reset_token',
    ];

    protected $casts = [
        'email_verified' => 'boolean',
        'is_active' => 'boolean',
        'email_verification_expires' => 'datetime',
        'reset_token_expires' => 'datetime',
        'last_login' => 'datetime',
    ];

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'user_id', 'user_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'user_id', 'user_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'user_id', 'user_id');
    }

    public function helpfulVotes()
    {
        return $this->hasMany(ReviewHelpfulVote::class, 'user_id', 'user_id');
    }
}
