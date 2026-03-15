<?php

namespace App\Models;

use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TransactionNumberRegister extends Model
{
    use HasFactory, Filterable;

    protected $fillable = [
        'transaction_type',
        'prefix',
        'starting_no',
        'next_transaction_no',
        'transaction_number_limit',
        'auto_generate_transaction_number',
        'user_id',
        'branch_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }


    // Function to handle auto-generating the next transaction number
    public function generateNextTransactionNumber()
    {
        if ($this->auto_generate_transaction_number) {
            $nextTransactionNumber = $this->starting_no;
            if ($this->next_transaction_no) {
                $nextTransactionNumber = (int)str_replace($this->prefix, '', $this->next_transaction_no) + 1;
            }
            if ($nextTransactionNumber <= $this->transaction_number_limit) {
                $this->next_transaction_no = $this->prefix . $nextTransactionNumber;
                $this->save();
            }
        }
    }

    // Function to adjust the next transaction number after deletion of a transaction
    public function adjustNextTransactionNumberAfterDeletion($deletedTransactionNo)
    {
        $deletedTransactionNo = (int)str_replace($this->prefix, '', $deletedTransactionNo);
        if ($deletedTransactionNo < (int)str_replace($this->prefix, '', $this->next_transaction_no)) {
            $this->next_transaction_no = $this->prefix . $deletedTransactionNo;
            $this->save();
        }
    }
}
