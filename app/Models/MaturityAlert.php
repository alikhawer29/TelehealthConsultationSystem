<?php

namespace App\Models;

use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaturityAlert extends Model
{
    use HasFactory, Filterable;

    protected $table = 'maturity_alert';

    protected $fillable = [
        'user_id',
        'levels',
        'status',
        'branch_id'
    ];
}
