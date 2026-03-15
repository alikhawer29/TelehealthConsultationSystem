<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountBalance extends Model
{
    use HasFactory;
    protected $fillable = ['main_account_id', 'voucher_id', 'account_id', 'debit_total', 'credit_total', 'balance', 'branch_id', 'business_id', 'created_by', 'edited_by'];

    public function account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }
}
