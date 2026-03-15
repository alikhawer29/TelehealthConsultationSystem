<?php

namespace App\Models;

use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class QueryReport extends Model
{
    use HasFactory, Filterable;

    protected $fillable = ['reason', 'status'];

    
}
