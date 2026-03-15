<?php

namespace App\Models;

use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Carbon\Carbon;

class Payment extends Model
{
    use HasFactory, Filterable;
    protected $fillable = [
        'payer_type',
        'payer_id',
        'payable_type',
        'payable_id',
        'amount',
        'transaction_id',
        'status',
        'split_payment_data',
        'customer_payment_data',
        'customer_refund_data',
        'payment_method'
    ];
    protected $casts = [
        'amount' => 'integer',
        'split_payment_data' => 'json',
        'customer_payment_data' => 'json',
        'customer_refund_data' => 'json',
    ];

    protected $hidden = [
        'split_payment_data',
        'customer_payment_data'
    ];


    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function payer(): MorphTo
    {
        return $this->morphTo();
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
