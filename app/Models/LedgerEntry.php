<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LedgerEntry extends Model
{
    use HasFactory;
    protected $fillable = ['main_account_id', 'account_id', 'debit', 'credit', 'currency_id', 'exchange_rate', 'converted_amount', 'voucher_id', 'branch_id', 'business_id', 'created_by', 'edited_by'];

    public function account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }
}
