<?php

namespace App\Models;

use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class CommissionDistribution extends Model
{
    use HasFactory, SoftDeletes, Filterable;

    // protected $table = 'commission_master';

    protected $fillable = [
        'special_commission_id',
        'ledger',
        'credit_account_id',
        'narration',
        'percentage',
        'amount',
        'modification_status'
    ];

    protected $appends = [
        'account_details',
    ];


    public function specialCommission()
    {
        return $this->belongsTo(SpecialCommission::class, 'special_commission_id');
    }

    public function ledger()
    {
        return $this->belongsTo(Ledger::class, 'ledger_id');
    }

    public function creditAccount()
    {
        return $this->belongsTo(Ledger::class, 'credit_account_id');
    }

    public function getAccountDetailsAttribute()
    {
        return match ($this->ledger) {
            'party' => PartyLedger::select('id', 'account_title as title')->find($this->credit_account_id),
            'walkin' => WalkinCustomer::select('id', 'customer_name as title')->find($this->credit_account_id),
            'general' => ChartOfAccount::select('id', 'account_name as title')->find($this->credit_account_id),
            'beneficiary' => BeneficiaryRegister::select('id', 'name as title')->find($this->credit_account_id),
            default => null,
        };
    }
}
