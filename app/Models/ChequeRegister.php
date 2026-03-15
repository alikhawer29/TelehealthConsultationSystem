<?php

namespace App\Models;

use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ChequeRegister extends Model
{

    use HasFactory, SoftDeletes, Filterable;

    protected $table = 'cheque_register';

    protected $fillable = [
        'cheque_number',
        'bank',
        'transaction_no',
        'issued_to',
        'amount',
        'reference_no',
        'count',
        'starting_no',
        'status',
        'business_id',
        'branch_id',
        'voucher_id',
        'issued_to_type'
    ];

    protected $hidden = [
        // 'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $appends = [
        'issued_user',
        'can_delete'
    ];



    public function bankName()
    {
        return $this->hasOne(ChartOfAccount::class, 'id', 'bank')->select('id', 'account_name');
    }

    public function voucher()
    {
        return $this->hasOne(Voucher::class, 'id', 'voucher_id');
    }


    public function getCanDeleteAttribute()
    {
        foreach ($this->fillable as $column) {
            if (empty($this->$column)) {
                return true; // At least one column is empty, allow deletion
            }
        }
        return false; // All columns have values, cannot be deleted
    }

    public function getIssuedUserAttribute()
    {
        return match ($this->issued_to_type) {
            'party' => PartyLedger::select('id', 'account_title as title')->find($this->issued_to),
            'walkin' => WalkinCustomer::select('id', 'customer_name as title')->find($this->issued_to),
            'general' => ChartOfAccount::select('id', 'account_name as title')->find($this->issued_to),
            'beneficiary' => BeneficiaryRegister::select('id', 'name as title')->find($this->issued_to),
            default => null,
        };
    }
}
