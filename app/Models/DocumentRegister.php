<?php

namespace App\Models;

use App\Core\Traits\Fileable;
use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DocumentRegister extends Model
{
    use HasFactory, SoftDeletes, Filterable, Fileable;

    protected $table = 'document_register';

    protected $images = [
        'image' => 'multiple',
    ];

    protected $imageable = ['image'];


    protected $fillable = [
        'group_name',
        'type',
        'description',
        'number',
        'issue_date',
        'due_date',
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

    public function creator(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }
    public function editor(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'edited_by');
    }

    public function group(): HasOne
    {
        return $this->hasOne(ClassificationType::class, 'id', 'group_name');
    }

    public function classification(): HasOne
    {
        return $this->hasOne(ClassificationMaster::class, 'id', 'type');
    }
}
