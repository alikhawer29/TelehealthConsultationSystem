<?php

namespace App\Models;

use App\Core\Traits\Filterable;
use App\Core\Traits\Addressable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;


class Package extends Model
{
    use  HasFactory, SoftDeletes, Filterable, Addressable;

    protected $fillable = [
        'title',
        'duration',
        'price',
        'description',
        'status',
    ];

    protected $hidden = ['deleted_at', 'updated_at'];


    public function payments()
    {
        return $this->hasMany(Payment::class, 'payable_id')->latest();
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'package_id');
    }

    public function subscribed()
    {
        return $this->hasOne(Subscription::class, 'package_id');
    }

    public function cartSelected()
    {
        return $this->hasOne(PackageCart::class, 'package_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
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
