<?php

namespace App\Models;

use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SalesmanMaster extends Model
{
    use HasFactory, SoftDeletes, Filterable;

    protected $table = 'salesman_master';

    protected $fillable = [
        'code',
        'name',
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

    // Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'edited_by');
    }

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }
}
