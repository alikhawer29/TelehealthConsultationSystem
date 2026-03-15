<?php

namespace App\Models;

use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CurrencyRegister extends Model
{
    use HasFactory, SoftDeletes, Filterable;

    protected $table = 'currency_register';

    protected $fillable = [
        'currency_code',
        'currency_name',
        'rate_type',
        'currency_type',
        'rate_variation',
        'group',
        'allow_online_rate',
        'allow_auto_pairing',
        'allow_second_preference',
        'restrict_pair',
        'special_rate_currency',
        'created_by',
        'edited_by',
        'parent_id',
        'is_custom',
        'branch_id'
    ];

    protected $casts = [
        'rate_variation' => 'decimal:2', // Cast to decimal with 2 decimal places
    ];

    protected $hidden = [
        // 'created_at',
        // 'updated_at',
        'deleted_at',
    ];

    public function creator(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }
    public function editor(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'edited_by');
    }
}
