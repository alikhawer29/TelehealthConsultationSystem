<?php

namespace App\Models;

use App\Core\Traits\Fileable;
use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReceiptVoucher extends Model
{
    use HasFactory, SoftDeletes, Filterable, Fileable;

    protected $fillable = [
        'ledger',
        'account_id',
        'narration',
        'received_from',
        'mode',
        'mode_account_id',
        'party_bank',
        'cheque_number',
        'due_date',
        'amount_account_id',
        'amount',
        'commission_type',
        'commission',
        'vat_terms',
        'vat_amount',
        'net_total',
        'comment',
        'out_of_scope_reason',
        'voucher_id',
        'branch_id',
        'business_id',
        'created_by',
        'edited_by',
        'modification_status',
        'vat_percentage',
        'exchange_rates'
    ];

    protected $appends = [
        'account_details',
        'attachments'
    ];

    protected $imageable = ['image'];

    protected $images = [
        'image' => 'multiple',
    ];

    protected $casts = [
        'commission' => 'decimal:2', // Cast to decimal with 2 decimal places
        'vat_amount' => 'decimal:2',
        'net_total' => 'decimal:2',
        'amount' => 'decimal:2'
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


    public function received_from()
    {
        return $this->belongsTo(BeneficiaryRegister::class, 'received_from');
    }

    public function receive_from()
    {
        return $this->belongsTo(BeneficiaryRegister::class, 'received_from');
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
}
