<?php

namespace App\Models;

use App\Core\Traits\Fileable;
use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Page extends Model
{
    use HasFactory, Filterable, Fileable;

    protected $images = [
        'file' => 'single',
    ];

    protected $imageable = ['file'];

    protected $table = 'pages';

    protected $fillable = [
        'name',
        'slug',
        'title',
        'description',
        'status',
    ];

    protected $appends = [
        'status_detail',
    ];

    protected $casts = [
        'status' => 'integer'
    ];

    public function getStatusDetailAttribute()
    {
        return match ($this->status) {
            0 => 'Inactive',
            1 => 'Active',
            default => 'Unknown',
        };
    }
}
