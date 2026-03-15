<?php

namespace App\Models;

use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Address extends Model
{

    use HasFactory, SoftDeletes, Filterable;

    protected $fillable = [
        'user_id',
        'address',
        'lat',
        'lng',
        'status',
        'building_name',
        'flat_no'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float'
    ];
}
