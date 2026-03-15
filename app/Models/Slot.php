<?php

namespace App\Models;

use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Slot extends Model
{
    use HasFactory, Filterable;

    protected $fillable = [
        'slot_type',
        'reference_type',
        'reference_id',
        'day',
        'day_name',
        'index',
        'start_time',
        'end_time',
        'status',
        'modification_status',
        'parent_id'
    ];



    public function referenceable(): MorphTo
    {
        return $this->morphTo('reference', 'reference_type', 'reference_id');
    }


    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'slot_id');
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
