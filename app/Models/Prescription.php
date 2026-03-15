<?php

namespace App\Models;

use App\Core\Traits\Fileable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Core\Traits\Filterable;
use Carbon\Carbon;

class Prescription extends Model
{
    use HasFactory, Filterable, Fileable;

    protected $table = 'prescriptions';

    protected $fillable = [
        'doctor_id',
        'patient_id',
        'appointment_id',
        'medication',
        'dosage',
        'status',
        'role',
        'type',
        'file_name',
        'created_by'
    ];

    protected $casts = [
        'status' => 'boolean'
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }


    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
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
