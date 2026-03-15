<?php

namespace App\Models;

use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use HasFactory, Filterable;
    protected $fillable = ['path', 'name', 'field_name', 'data', 'fileable_type', 'fileable_id'];
    protected $appends = ['file_url'];

    public function fileUrl(): Attribute
    {
        return Attribute::make(
            get: fn() => asset(Storage::url('media/' . $this->path)),
        );
    }
}
