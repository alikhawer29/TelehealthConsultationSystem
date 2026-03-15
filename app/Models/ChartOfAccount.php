<?php

namespace App\Models;

use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChartOfAccount extends Model
{
    use HasFactory, SoftDeletes, Filterable;

    protected $fillable = [
        'account_type_id',
        'account_type',
        'account_name',
        'parent_account_id',
        'description',
        'account_code',
        'level',
        'status',
        'created_by',
        'edited_by',
        'parent_id',
        'branch_id',
        'is_template'
    ];

    public function parentAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'parent_account_id');
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editedByUser()
    {
        return $this->belongsTo(User::class, 'edited_by');
    }

    public function children()
    {
        return $this->hasMany(ChartOfAccount::class, 'parent_account_id');
    }

    public function parent()
    {
        return $this->belongsTo(ChartOfAccount::class, 'parent_account_id');
    }

    public function scopeByAccountType($query, $type)
    {
        return $query->where('account_type', $type);
    }
}
