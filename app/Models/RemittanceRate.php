<?php

namespace App\Models;

use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;


class RemittanceRate extends Model
{
    use HasFactory, SoftDeletes, Filterable;

    protected $fillable = [
        'currency_id',
        'ag_fcy_id',
        'buy_rate',
        'buy_from',
        'buy_upto',
        'sell_rate',
        'sell_from',
        'sell_upto',
        'action',
        'branch_id',
        'business_id',
        'created_by',
        'edited_by',
        'date'
    ];


    public function currency()
    {
        return $this->belongsTo(CurrencyRegister::class, 'currency_id');
    }

    public function agFcy()
    {
        return $this->belongsTo(CurrencyRegister::class, 'ag_fcy_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function business()
    {
        return $this->belongsTo(User::class, 'business_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'edited_by');
    }
}
