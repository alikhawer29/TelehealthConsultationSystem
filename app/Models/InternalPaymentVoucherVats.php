<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InternalPaymentVoucherVats extends Model
{
    use HasFactory;
    protected $fillable = [
        'vouhcer_id',
        'vouhcer_type',
        'ledger',
        'debit_account_id',
        'narration',
        'currency_id',
        'amount',
        'vat_terms',
        'vat_amount',
        'vat_percentage',
        'total',
        'out_of_scope_reason'
    ];
}
