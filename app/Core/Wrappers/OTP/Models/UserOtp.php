<?php

namespace App\Core\Wrappers\OTP\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserOtp extends Model
{
    use HasFactory;

    protected $fillable = ['code','sent_on','medium','receivable_type','receivable_id'];
}
