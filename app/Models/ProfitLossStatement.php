<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfitLossStatement extends Model
{
    use HasFactory;
    protected $table = 'profit_loss_statement';
    protected $fillable = ['main_account_id', 'voucher_id', 'account_id', 'amount', 'type', 'branch_id', 'business_id', 'created_by', 'edited_by'];

    public function account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }
}
