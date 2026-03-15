<?php

namespace App\Models;

use App\Core\Traits\Fileable;
use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class MedicalReport extends Model
{
    use HasFactory, Filterable, Fileable,  Notifiable;

    protected $table = 'medical_records';

    protected $fillable = [
        'file_name',
        'type',
        'user_id',
    ];

    protected $hidden = [
        'updated_at',
        'deleted_at',
    ];

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
