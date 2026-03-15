<?php

namespace App\Models;

use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Carbon\Carbon;

class Report extends Model
{
    use HasFactory, Filterable;
    protected $fillable = ['reportable_type', 'reportable_id', 'reason', 'user_id', 'admin_note', 'status', 'service_type'];

    protected $appends = ['type'];


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reportable(): MorphTo
    {
        return $this->morphTo('reportable');
    }

    public function type(): Attribute
    {
        return Attribute::make(
            get: fn() => [
                'lab_service' => 'Lab Service',
                'homecare_service' => 'Homecare Service',
                'doctor_service' => 'Doctor Service',
                'doctor_profile' => 'Doctor Profile',
                'nurse_profile' => 'Nurse Profile',
                'physician_profile' => 'Physician Profile',

            ][$this->service_type] ?? ''
        );
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
