<?php

namespace App\Models;

use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class SpecialCommission extends Model
{
    use HasFactory, SoftDeletes, Filterable;

    // protected $table = 'commission_master';

    protected $fillable = [
        'transaction_no',
        'date',
        'commission_type',
        'account_type',
        'account_id',
        'amount_type',
        'amount',
        'description',
        'commission',
        'total_commission',
        'branch_id',
        'business_id',
        'created_by',
        'edited_by',
        'voucher_id',
        'modification_status'
    ];

    protected $appends = [
        'account_details',
    ];

    public function business()
    {
        return $this->belongsTo(User::class, 'business_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function amountCurrency()
    {
        return $this->belongsTo(CurrencyRegister::class, 'amount_type');
    }

    public function commissionDistribution()
    {
        return $this->hasMany(CommissionDistribution::class, 'special_commission_id');
    }

    public function getAccountDetailsAttribute()
    {
        return match ($this->account_type) {
            'party' => PartyLedger::select('id', 'account_title as title')->find($this->account_id),
            'walkin' => WalkinCustomer::select('id', 'customer_name as title')->find($this->account_id),
            'general' => ChartOfAccount::select('id', 'account_name as title')->find($this->account_id),
            'beneficiary' => BeneficiaryRegister::select('id', 'name as title')->find($this->account_id),
            default => null,
        };
    }

    public function amount_type()
    {
        return $this->belongsTo(CurrencyRegister::class, 'amount_type');
    }
}
