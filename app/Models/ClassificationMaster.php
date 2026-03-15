<?php

namespace App\Models;

use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClassificationMaster extends Model
{
    use HasFactory, SoftDeletes, Filterable;

    protected $table = 'classification_master';

    protected $fillable = [
        'classification',
        'description',
        'created_by',
        'edited_by',
        'parent_id',
        'branch_id'
    ];

    protected $hidden = [
        // 'created_at',
        // 'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'classification' => 'integer',
    ];

    public function creator(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }
    public function editor(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'edited_by');
    }

    public function classificationType(): HasOne
    {
        return $this->hasOne(ClassificationType::class, 'id', 'classification');
    }
}
