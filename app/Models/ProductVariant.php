<?php

namespace App\Models;

use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use HasFactory, Filterable, SoftDeletes;

    protected $table = 'product_variations';

    protected $fillable = [
        'product_id',
        'color',
        'size',
        'qty',
        'price',
        'status',
        'type'
    ];
}
