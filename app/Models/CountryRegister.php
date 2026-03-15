<?php

namespace App\Models;

use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CountryRegister extends Model
{
    use HasFactory, SoftDeletes, Filterable;

    protected $table = 'country_register';

    protected $fillable = [
        'branch_id',
        'country',
        'code',
        'created_by',
        'edited_by',
        'parent_id',

    ];


    protected $hidden = [
        // 'created_at',
        // 'updated_at',
        'deleted_at',
    ];
}
