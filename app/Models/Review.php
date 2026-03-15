<?php

namespace App\Models;

use App\Core\Traits\Filterable;
use App\Core\Generators\Graph\Buyer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Review extends Model
{
    use HasFactory, Filterable;


    protected $fillable = ['appointment_id', 'user_id', 'rating', 'review', 'reviewable_id', 'reviewable_type'];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class, 'appointment_id', 'id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
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
