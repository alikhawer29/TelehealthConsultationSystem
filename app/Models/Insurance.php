<?php

namespace App\Models;

use App\Core\Traits\Fileable;
use App\Core\Traits\Filterable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Insurance extends Model
{
    use HasFactory, Fileable, Filterable;

    protected $fillable = [
        'name',
        'card_number',
        'card_holder_name',
        'user_id',
        'status',
        'reason'
    ];

    protected $appends = [
        'status_detail',
        'is_insured', // new field for your true/false needs
    ];




    // 👇 For label like 'Approved', 'Pending', etc.
    public function statusDetail(): Attribute
    {
        return Attribute::make(
            get: fn() => match ($this->status) {
                "0" => 'Pending',
                "1" => 'Approved',
                "2" => 'Rejected',
                default => 'Unknown',
            },
        );
    }

    // 👇 For boolean true/false
    public function isInsured(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status == 1, // true only if approved
        );
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function setNameAttribute($value)
    {
        $this->attributes['name'] = Crypt::encryptString($value);
        $this->attributes['name_hash'] = hash('sha256', strtolower(trim($value)));
    }

    public function getNameAttribute($value)
    {
        return $this->decryptIfNeeded($value);
    }

    public function setCardNumberAttribute($value)
    {
        $this->attributes['card_number'] = Crypt::encryptString($value);
        $this->attributes['card_number_hash'] = hash('sha256', strtolower(trim($value)));
    }

    public function getCardNumberAttribute($value)
    {
        return $this->decryptIfNeeded($value);
    }

    public function setCardHolderNameAttribute($value)
    {
        $this->attributes['card_holder_name'] = Crypt::encryptString($value);
        $this->attributes['card_holder_name_hash'] = hash('sha256', strtolower(trim($value)));
    }

    public function getCardHolderNameAttribute($value)
    {
        return $this->decryptIfNeeded($value);
    }

    public function setStatusAttribute($value)
    {
        $this->attributes['status'] = Crypt::encryptString($value);
        $this->attributes['status_hash'] = hash('sha256', strtolower(trim($value)));
    }

    public function getStatusAttribute($value)
    {
        return $this->decryptIfNeeded($value);
    }

    public function setReasonAttribute($value)
    {
        $this->attributes['reason'] = Crypt::encryptString($value);
        $this->attributes['reason_hash'] = hash('sha256', strtolower(trim($value)));
    }

    public function getReasonAttribute($value)
    {
        return $this->decryptIfNeeded($value);
    }

    protected function decryptIfNeeded($value)
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return $value; // Return as-is if not encrypted
        }
    }
}
