<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormSubmission extends Model
{
    use HasFactory;

    protected $table = 'form_submissions';
    protected $primaryKey = 'id';
    const UPDATED_AT = null;

    protected $fillable = [
        'form_token',
        'user_id',
        'form_type',
        'result_id',
        'ip_address',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
