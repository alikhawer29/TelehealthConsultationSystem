<?php

namespace App\Models;

use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class ServiceSlot extends Model
{
    use HasFactory, Filterable;

    protected $table = 'service_slot';

    protected $fillable = [
        'day',
        'from',
        'to',
        'status',
        'service_id'
    ];

    protected $hidden = [
        // 'created_at',
        // 'updated_at',
        // 'deleted_at',
    ];


    public function slots(): HasOne
    {
        return $this->hasMany(User::class, 'id', 'created_by');
    }
    public function editor(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'edited_by');
    }

    public function classificationType(): HasOne
    {
        return $this->hasOne(ClassificationType::class, 'id', 'classification');
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
