<?php

namespace App\Models;

use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReminderSetting extends Model
{
    use HasFactory, Filterable;

    protected $fillable = [
        'user_type',
        'reminder_time',
        'custom_time',
        'reference_id'
    ];

    public static function getReminderForUser($userType)
    {
        $reminder = self::where('user_type', $userType)->first();
        return $reminder ? ($reminder->reminder_time === 'custom' ? "{$reminder->custom_time} minutes before" : $reminder->reminder_time) : '10_min';
    }
}
