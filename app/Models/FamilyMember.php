<?php

namespace App\Models;

use App\Core\Traits\Filterable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class FamilyMember extends Model
{

    use HasFactory, Filterable;
    protected  $table = 'family_member';

    protected $fillable = [
        'name',
        'user_id',
        'gender',
        'emirates_id',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];


    public function setNameAttribute($value)
    {
        $this->attributes['name'] = Crypt::encryptString($value);
        $this->attributes['name_hash'] = hash('sha256', strtolower(trim($value)));
    }

    public function getNameAttribute($value)
    {
        return $this->decryptIfNeeded($value);
    }

    public function setGenderAttribute($value)
    {
        $this->attributes['gender'] = Crypt::encryptString($value);
        $this->attributes['gender_hash'] = hash('sha256', strtolower(trim($value)));
    }

    public function getGenderAttribute($value)
    {
        return $this->decryptIfNeeded($value);
    }

    public function setEmiratesIdAttribute($value)
    {
        $this->attributes['emirates_id'] = Crypt::encryptString($value);
        $this->attributes['emirates_id_hash'] = hash('sha256', strtolower(trim($value)));
    }

    public function getEmiratesIdAttribute($value)
    {
        return $this->decryptIfNeeded($value);
    }

    /**
     * Helper: decrypt only if encrypted, avoid "payload invalid" error
     */
    protected function decryptIfNeeded($value)
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return $value; // already plain text or not encrypted
        }
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
