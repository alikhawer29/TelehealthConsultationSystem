<?php

namespace App\Models;

use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BranchTimings extends Model
{
    use HasFactory, SoftDeletes, Filterable;

    protected $fillable = [
        'branch_id',
        'day',
        'start_time',
        'end_time',
        'status'
    ];

    protected $hidden = [
        'deleted_at',
    ];
}
