<?php

namespace App\Models;

use App\Core\Traits\Fileable;
use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;


class Banner extends Model
{
    use HasFactory, Filterable, Fileable;

    protected $table = 'banners';

    protected $fillable = ['status'];

    protected $appends = [
        'status_detail',
    ];

    public function statusDetail(): Attribute
    {
        return Attribute::make(
            get: fn() => match ($this->status) {
                0 => 'Inactive',
                1 => 'Active',
                default => 'Unknown',  // Fallback in case an invalid status is present
            },
        );
    }
}
