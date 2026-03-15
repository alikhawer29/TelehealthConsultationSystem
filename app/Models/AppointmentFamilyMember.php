<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AppointmentFamilyMember extends Model
{
    use HasFactory;
    protected $table = 'appointment_family_members';
    protected $fillable = ['appointment_id', 'family_member_id'];

    public function user()
    {
        return $this->belongsTo(FamilyMember::class, 'family_member_id');
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
