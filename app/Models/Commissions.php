<?php

namespace App\Models;

use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Commissions extends Model
{
    use HasFactory, Filterable;

    protected $table = 'commission';
    protected $casts = [
        'rate' => 'integer'
    ];
    protected $fillable = [
        'rate',
        'effective_date',
        'end_date',
        'status',
        'supplier_id'
    ];

    public function suppliers()
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }
}
