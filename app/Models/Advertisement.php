<?php

namespace App\Models;

use App\Core\Traits\Fileable;
use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Advertisement extends Model
{
    use HasFactory, Filterable, Fileable;

    protected $images = [
        'image' => 'multiple',
    ];

    protected $imageable = ['image'];

    protected $hidden = [
        'updated_at',
    ];



    protected $fillable = [
        'title',
        'user_id',
        'email',
        'website_url',
        'status',
        'billing_address',
        'advertisement_status',
        'reject_reason',
        'payment_status',
        'package_id',
        'expiry_date'
    ];
}
