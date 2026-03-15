<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionLog extends Model
{
    use HasFactory;

    protected $table = 'transaction_logs';

    protected $fillable = [
        'voucher_id',
        'user_id',
        'action',
        'old_data',
        'new_data',
        'timestamp',
        'branch_id'
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
    ];

    public $timestamps = false;

    // Relationship with Voucher
    public function voucher()
    {
        return $this->belongsTo(Voucher::class, 'voucher_id', 'id')->withTrashed();
    }

    // Relationship with User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
