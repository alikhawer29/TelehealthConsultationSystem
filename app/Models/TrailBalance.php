<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrailBalance extends Model
{
    use HasFactory;
    protected $table = 'trial_balance';
    protected $fillable = ['main_account_id', 'voucher_id', 'account_id', 'debit', 'credit', 'branch_id', 'business_id', 'created_by', 'edited_by'];

    public function account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }
}
