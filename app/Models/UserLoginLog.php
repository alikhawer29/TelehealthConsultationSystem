<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class UserLoginLog extends Model
{
    use HasFactory;

    protected $table = 'user_login_logs';

    protected $fillable = [
        'user_id',
        'login_time',
        'ip_address',
        'device',
        'location',
        'status',
        'branch_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    // Convert created_at to Dubai timezone
    public function getCreatedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->timezone('Asia/Dubai')->toDateTimeString() : null;
    }

    // Convert updated_at to Dubai timezone
    public function getUpdatedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->timezone('Asia/Dubai')->toDateTimeString() : null;
    }
}
