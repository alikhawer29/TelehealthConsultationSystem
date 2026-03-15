<?php

namespace App\Models;

use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Favourite extends Model
{
    use HasFactory, Filterable;

    protected $fillable = ['user_id', 'supplier_id', 'terms', 'rating'];

    public function supplier()
    {
        return $this->belongsTo(User::class, 'supplier_id')->where('role', 'supplier')->select(['id', 'first_name', 'last_name']);
    }

    public function order()
    {
        return $this->hasManyThrough(User::class, 'supplier_id')->where('role', 'supplier')->select(['id', 'first_name', 'last_name']);
    }
}
