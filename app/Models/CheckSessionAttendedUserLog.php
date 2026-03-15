<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Core\Traits\Filterable;

class CheckSessionAttendedUserLog extends Model
{
    use HasFactory, Filterable;

    protected $table = 'check_session_attended_user_logs';

    protected $fillable = [
        'appointment_id',
        'user_id'
    ];

    protected $hidden = [
        'updated_at',
    ];

    /**
     * Get the appointment that owns the log.
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }

    /**
     * Get the user that owns the log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}