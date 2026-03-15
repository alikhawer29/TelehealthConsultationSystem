<?php

namespace App\Models;

use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteInformation extends Model
{
    use HasFactory, Filterable;

    protected $fillable = ['email', 'website_link', 'whatsapp_numbers', 'landline_numbers', 'social_media'];

    protected $casts = [
        'whatsapp_numbers' => 'array', // Auto-convert JSON to array
        'landline_numbers' => 'array',
        'social_media' => 'array',
    ];

    protected $appends = ['is_editable'];

    public function getIsEditableAttribute(): bool
    {
        // Check if this is the latest record
        return $this->id === self::latest('id')->value('id');
    }
}
