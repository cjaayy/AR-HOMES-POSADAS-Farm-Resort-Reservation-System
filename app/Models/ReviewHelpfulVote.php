<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReviewHelpfulVote extends Model
{
    use HasFactory;

    protected $table = 'review_helpful_votes';
    protected $primaryKey = 'vote_id';
    const UPDATED_AT = null;

    protected $fillable = [
        'review_id',
        'user_id',
    ];

    public function review()
    {
        return $this->belongsTo(Review::class, 'review_id', 'review_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
