<?php

namespace App\Models;

use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PartyLedgerClassification extends Model
{
    use HasFactory, Filterable, SoftDeletes;

    protected $table = 'party_ledger_classification';

    protected $fillable = [
        'classification',
        'created_by',
        'parent_id',
        'branch_id'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];
}
