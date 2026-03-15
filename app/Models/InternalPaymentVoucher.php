<?php

namespace App\Models;

use App\Core\Traits\Fileable;
use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InternalPaymentVoucher extends Model
{
    use HasFactory, SoftDeletes, Filterable, Fileable;

    protected $table = 'internal_payment_voucher';

    protected $fillable = [
        'ledger',
        'account_id',
        'mode',
        'mode_account_id',
        'cheque_number',
        'due_date',
        'currency_id',
        'narration',
        'amount',
        'cost_center_id',
        'modification_status',
        'voucher_id',
        'branch_id',
        'business_id',
        'created_by',
        'edited_by',
        'exchange_rates',
        'date'
    ];

    protected $appends = [
        'account_details',
        'attachments'
    ];

    protected $imageable = ['image'];
    protected $images = [
        'image' => 'multiple',
    ];

    public function getAccountDetailsAttribute()
    {
        return match ($this->ledger) {
            'party' => PartyLedger::select('id', 'account_title as title')->find($this->account_id),
            'walkin' => WalkinCustomer::select('id', 'customer_name as title')->find($this->account_id),
            'general' => ChartOfAccount::select('id', 'account_name as title')->find($this->account_id),
            'beneficiary' => BeneficiaryRegister::select('id', 'name as title')->find($this->account_id),
            default => null,
        };
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class, 'voucher_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getAttachmentsAttribute()
    {
        $exists = Media::where('fileable_type', 'App\Models\Voucher')
            ->where('fileable_id', $this->voucher_id)
            ->exists(); // Efficient check

        return $exists ? 'yes' : 'no';
    }


    public function paid_to()
    {
        return $this->belongsTo(BeneficiaryRegister::class, 'paid_to_id');
    }

    public function paid()
    {
        return $this->belongsTo(BeneficiaryRegister::class, 'paid_to_id');
    }


    public function mode_account_id()
    {
        return $this->belongsTo(ChartOfAccount::class, 'mode_account_id');
    }

    public function mode_account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'mode_account_id');
    }

    public function amount_account_id()
    {
        return $this->belongsTo(CurrencyRegister::class, 'amount_account_id');
    }
    public function amount_account()
    {
        return $this->belongsTo(CurrencyRegister::class, 'amount_account_id');
    }

    public function special_commission()
    {
        return $this->hasOne(SpecialCommission::class, 'voucher_id', 'voucher_id');
    }

    public function currency()
    {
        return $this->belongsTo(CurrencyRegister::class, 'currency_id');
    }

    public function vatDetails()
    {
        return $this->belongsTo(InternalPaymentVoucherVats::class, 'voucher_id');
    }
}
