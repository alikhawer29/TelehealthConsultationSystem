<?php

namespace App\Models;

use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Vat extends Model
{

    use HasFactory, Filterable;

    protected $fillable = [
        'branch_id',
        'title',
        'percentage',
        'business_id'
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
