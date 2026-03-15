<?php

namespace App\Models;

use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Warehouse extends Model
{

    use HasFactory, SoftDeletes, Filterable;

    protected $table = 'warehouse_master';

    protected $fillable = [
        'code',
        'name',
        'created_by',
        'edited_by',
        'parent_id',
        'status',
        'branch_id'
    ];

    protected $hidden = [
        // 'created_at',
        // 'updated_at',
        'deleted_at',
    ];
}
