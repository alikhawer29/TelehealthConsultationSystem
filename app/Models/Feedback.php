<?php

namespace App\Models;

use App\Core\Traits\Fileable;
use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Feedback extends Model
{
    use HasFactory, Filterable;

    protected $fillable = ['name', 'email', 'contact_no', 'message', 'user_id', 'subject'];
}
