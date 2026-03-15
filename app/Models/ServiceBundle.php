<?php

namespace App\Models;

use App\Core\Traits\Fileable;
use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class ServiceBundle extends Model
{
    use HasFactory, Filterable, Fileable;

    protected $fillable = [
        'bundle_name',
        'why_to_get_tested',
        'specimen_type',
        'preparation_needed',
        'parameter_list',
        'price',
        'status',
        'type',
        'about',
        'parameters_included',
        'precautions',
        'fasting_requirments',
        'turnaround_time',
        'when_to_get_tested',
    ];

    public function services()
    {
        return $this->belongsToMany(Service::class, 'bundle_services', 'bundle_id', 'service_id');
    }

    public function cartServices()
    {
        return $this->hasMany(BundleService::class, 'bundle_id');
    }

    public function icon(): HasOne
    {
        return $this->hasOne(Media::class, 'fileable_id', 'id')->where('fileable_type', 'icon');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'bookable_id');
    }



    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
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
