<?php

namespace App\Models;

use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Subscription extends Model
{
    use HasFactory, Filterable;

    protected $fillable = [
        'package_id',
        'expire_date',
        'user_id',
        'type',
        'status',
        'auto_renewal',
        'business_id'
    ];

    protected $appends = [
        'status_detail',
        'subscription_amount'
    ];

    public function package()
    {
        return $this->belongsTo(Package::class, 'package_id')
            ->select(['id', 'title', 'price_monthly', 'price_yearly', 'status', 'no_of_users', 'branches']);
    }

    // Accessor for subscription amount
    public function getSubscriptionAmountAttribute()
    {
        if ($this->type === 'monthly') {
            return $this->package ? $this->package->price_monthly : null;
        } elseif ($this->type === 'yearly') {
            return $this->package ? $this->package->price_yearly : null;
        }

        return null;
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function statusDetail(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->expire_date < now() ? 'Expired' : 'Active',
        );
    }
}
