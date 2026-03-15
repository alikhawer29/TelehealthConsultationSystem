<?php

namespace App\Models;

use App\Core\Traits\Fileable;
use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PaymentDetail extends Model
{
    use HasFactory, Filterable, Fileable;

    protected $fillable = [
        'voucher_id',
        'voucher_type',
        'cardholder_name',
        'card_number',
        'cvv_number',
        'validity_date',
        'bank_account_number',
        'swift_bic_code',
        'routing_number',
        'iban',
        'modification_status'
    ];


    public function paymentVoucher()
    {
        return $this->belongsTo(PaymentVoucher::class, 'voucher_id');
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
