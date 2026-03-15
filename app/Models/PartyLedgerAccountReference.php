<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartyLedgerAccountReference extends Model
{
    use HasFactory;
    protected $table = 'party_ledgers_account_references';
    protected $fillable = [
        'account_code',
        'user_id',
        'branch_id',
    ];
    // Disable timestamps if you don't need them (optional)
    public $timestamps = false;

    // Define the relationship with the User model (assuming the users table exists)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
