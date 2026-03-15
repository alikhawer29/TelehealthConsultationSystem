<?php

namespace App\Models;

use App\Core\Traits\Fileable;
use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Voucher extends Model
{
    use HasFactory, SoftDeletes, Filterable;

    protected $fillable = [
        'voucher_type',
        'voucher_no',
        'total_debit',
        'total_credit',
        'created_by',
        'branch_id',
        'business_id',
        'edited_by',
        'modification_status',
        'date'
    ];


    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function journalVouchers()
    {
        return $this->hasMany(JournalVoucher::class, 'voucher_id')->where('modification_status', 'active');
    }

    public function receiptVouchers()
    {
        return $this->hasOne(ReceiptVoucher::class, 'voucher_id')->where('modification_status', 'active');
    }

    public function paymentVouchers()
    {
        return $this->hasOne(PaymentVoucher::class, 'voucher_id')->where('modification_status', 'active');
    }

    public function internalPaymentVouchers()
    {
        return $this->hasOne(InternalPaymentVoucher::class, 'voucher_id')->where('modification_status', 'active');
    }

    public function files()
    {
        return $this->hasMany(Media::class, 'fileable_id')->where('fileable_type', 'App\Models\Voucher');
    }
}
