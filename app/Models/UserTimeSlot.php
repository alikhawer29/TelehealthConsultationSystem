<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class UserTimeSlot extends Model
{
    use HasFactory;

    // Specify the table name (optional if the table name follows Laravel's convention)
    protected $table = 'user_time_slots';

    // Specify the fillable fields for mass assignment
    protected $fillable = [
        'user_id',
        'day',
        'from',
        'to',
    ];

    // Define the relationship with the User model (assuming each user has many time slots)
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
