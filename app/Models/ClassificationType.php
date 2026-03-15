<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassificationType extends Model
{
    use HasFactory;

    protected $table = 'classification_type';

    protected $fillable = [
        'type',
        'created_by',
        'branch_id'
    ];
}
