<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebexToken extends Model
{
    protected $fillable = [
        'doctor_id',
        'access_token',
        'refresh_token',
        'expires_at'
    ];
}
