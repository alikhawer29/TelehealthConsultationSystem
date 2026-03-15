<?php

namespace App\Models;

use App\Core\Traits\Fileable;
use App\Core\Traits\Filterable;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Service extends Model
{
    use HasFactory, SoftDeletes, Filterable, Fileable;

    protected $table = 'service';

    protected $fillable = [
        'name',
        'about',
        'conditions_to_treat',
        'what_to_expect_during_the_sessions',
        'preparations_and_precautions',
        'who_should_consider_this_service',
        'why_to_get_tested',
        'specimen_type',
        'preparation_needed',
        'key_servies_included',
        'what_to_expect',
        'precautions',
        'general_information',
        'ingredients',
        'preparations',
        'administration_time',
        'restriction',
        'created_by',
        'price',
        'status',
        'type',
        'parameters_included',
        'fasting_requirments',
        'turnaround_time',
        'when_to_get_tested',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    protected $appends = [
        'status_detail',
    ];

    protected $casts = [
        'status' => 'string',
        'conditions_to_treat' => 'array',
        'what_to_expect_during_the_sessions' => 'array',
        'preparations_and_precautions' => 'array',
        'ingredients' => 'array',
        'restriction' => 'array'
    ];


    public function getStatusDetailAttribute()
    {
        return match ($this->status) {
            "0" => 'Inactive',
            "1" => 'Active',
            default => 'Unknown',  // Fallback in case an invalid status is present
        };
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function slots(): HasMany
    {
        return $this->hasMany(Slot::class, 'reference_id', 'id')->where('reference_type', 'App\Models\Service');
    }


    public function editor(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'edited_by');
    }

    public function icon(): HasOne
    {
        return $this->hasOne(Media::class, 'fileable_id', 'id')->where('fileable_type', 'icon');
    }


    public function appointments(): MorphMany
    {
        return $this->morphMany(Appointment::class, 'bookable');
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

    // Convert deleted_at to Dubai timezone
    public function getDeletedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->timezone('Asia/Dubai')->toDateTimeString() : null;
    }
}
