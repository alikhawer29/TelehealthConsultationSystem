<?php

namespace App\Models;

use App\Models\Commissions;
use App\Core\Traits\Fileable;
use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory, Filterable, Fileable;

    protected $images = [
        'images' => 'multiple',
    ];

    protected $imageable = ['media'];

    protected $appends = ['status_detail'];

    protected $fillable = [
        'title',
        'description',
        'vendorable_id',
        'vendorable_type',
        'status',
        'shop_id'
    ];

    protected $casts = [
        'is_reviewed' => 'boolean',
        'is_wishlist' => 'boolean',
    ];


    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function wishlist(): MorphOne
    {
        return $this->morphOne(Wishlist::class, 'wishable');
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Review::class, 'product_id', 'id');
    }
    public function statusDetail(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status == 1 ? 'Active' : 'Inactive',
        );
    }
    public function charges(): HasOne
    {
        return $this->hasOne(Commissions::class, 'vendor_id', 'vendor_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'product_id', 'id');
    }
}
