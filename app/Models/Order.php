<?php

namespace App\Models;

use App\Models\Shelter;
use App\Models\UserSession;
use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Order extends Model
{
    use HasFactory, Filterable;
    protected $fillable = [
        'order_id',
        'commission_id',
        'delivery_rate_id',
        'order_type',
        'userable_type',
        'userable_id',
        'order_id',
        'contact_detail',
        'shipping_detail',
        'billing_detail',
        'address',
        'status',
        'order_owner_type',
        'order_owner_id',
        'shop_id',
        'payment_status',
        'payout_status',
        'payout_date'
    ];

    protected $appends = ['status_detail'];
    protected $casts = [
        'address' => 'json',
        'contact_detail' => 'json',
        'shipping_detail' => 'json',
        'billing_detail' => 'json',
    ];

    public function owner(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'userable_type', 'userable_id');
    }
    public function vendor(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'order_owner_type', 'order_owner_id');
    }
    public function orderDetails()
    {
        return $this->hasMany(OrderProduct::class, 'order_id');
    }
    public function products(): HasMany
    {
        return $this->hasMany(OrderProduct::class, 'order_id', 'id');
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shelter()
    {
        return $this->belongsTo(Shelter::class, 'user_id');
    }

    public function report()
    {
        return $this->morphOne(Report::class, 'reportable');
    }

    public function commission()
    {
        return $this->belongsTo(Commissions::class);
    }


    public function delivery()
    {
        return $this->belongsTo(Commissions::class, 'delivery_rate_id', 'id')->where('rate_type', 'delivery');
    }


    public function payment(): MorphOne
    {
        return $this->morphOne(Payment::class, 'payable', 'payable_type', 'payable_id');
    }

    public function statusDetail(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->status == 'processed') {
                    return 'In Process';
                } else {
                    return ucwords($this->status);
                }
            }
        );
    }
    public function product(): HasOne
    {
        return $this->hasOne(OrderProduct::class, 'order_id', 'id');
    }

    public function shop(): HasOne
    {
        return $this->hasOne(Shop::class, 'id', 'shop_id');
    }
}
