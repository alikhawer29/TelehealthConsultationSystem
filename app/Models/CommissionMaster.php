<?php

namespace App\Models;

use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class CommissionMaster extends Model
{
    use HasFactory, SoftDeletes, Filterable;

    protected $table = 'commission_master';

    protected $fillable = [
        'account_type',
        'account',
        'commission_type',
        'receipt_percentage',
        'payment_percentage',
        'tmn_buy_remittance_percentage',
        'tmn_sell_remittance_percentage',
        'currency_transfer_request_percentage',
        'outward_remittance_percentage',
        'currency_buy_sell_percentage',
        'inward_remittance_percentage',
        'branch_id',
        'business_id',
        'created_by',
    ];

    protected $appends = [
        'account_details'
    ];

    // Relationships
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function business()
    {
        return $this->belongsTo(User::class, 'business_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getAccountDetailsAttribute()
    {
        return match ($this->account_type) {
            'party' => PartyLedger::select('id', 'account_title as title')->find($this->account),
            'walkin' => WalkinCustomer::select('id', 'customer_name as title')->find($this->account),
            'general' => ChartOfAccount::select('id', 'account_name as title')->find($this->account),
            default => null,
        };
    }
}
