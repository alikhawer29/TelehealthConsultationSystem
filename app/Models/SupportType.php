<?php

namespace App\Models;

use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportType extends Model
{
    use HasFactory, Filterable;

    protected $fillable = ['name', 'status'];
}
