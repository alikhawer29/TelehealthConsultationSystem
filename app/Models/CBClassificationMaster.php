<?php

namespace App\Models;

use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CBClassificationMaster extends Model
{
    use HasFactory, SoftDeletes, Filterable;

    protected $table = 'cb_classification_master';

    protected $fillable = [
        'group',
        'title',
        'created_by',
        'edited_by',
        'parent_id',
        'branch_id'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];
}
