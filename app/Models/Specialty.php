<?php

namespace App\Models;

use App\Core\Traits\Fileable;
use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

class Specialty extends Model
{
    use HasFactory, Filterable, Fileable;

    protected $table = 'speciality';

    protected $fillable = ['title', 'status'];

    protected $appends = [
        'status_detail',
    ];

    public function statusDetail(): Attribute
    {
        return Attribute::make(
            get: fn() => match ($this->status) {
                0 => 'Inactive',
                1 => 'Active',
                default => 'Unknown',  // Fallback in case an invalid status is present
            },
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
