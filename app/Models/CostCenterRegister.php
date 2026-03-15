<?php

namespace App\Models;

use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CostCenterRegister extends Model
{
    use HasFactory, SoftDeletes, Filterable;

    protected $table = 'cost_register_center';

    protected $fillable = [
        'code',
        'type',
        'group',
        'description',
        'default',
        'branch_id',
        'business_id',
        'created_by',
    ];
}
