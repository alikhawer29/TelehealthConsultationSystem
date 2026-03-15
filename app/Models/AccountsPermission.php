<?php

namespace App\Models;

use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AccountsPermission extends Model
{

    use HasFactory, Filterable;

    protected $table = 'accounts_permission';

    protected $fillable = [
        'business_id',
        'employee_id',
        'chart_of_account_code',
        'granted',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $casts = [
        // 'lat' => 'float',
        // 'lng' => 'float'
    ];
}
