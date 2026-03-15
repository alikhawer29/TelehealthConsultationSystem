<?php

namespace App\Models;

use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cart extends Model
{
    use HasFactory, Filterable;
    protected $fillable = [
        'service_id',
        'user_id',
        'provider_id',
        'charges',
        'type'
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function bundleService(): BelongsTo
    {
        return $this->belongsTo(ServiceBundle::class, 'service_id', 'id');
    }

    public function provider()
    {
        return $this->morphTo();
    }
}
