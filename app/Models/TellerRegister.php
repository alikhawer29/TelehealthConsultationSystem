<?php

namespace App\Models;

use App\Core\Traits\Fileable;
use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TellerRegister extends Model
{
    use HasFactory, SoftDeletes, Filterable;

    protected $table = 'teller_register';

    protected $fillable = [
        'code',
        'till_assigned_to_user',
        'description',
        'cash_account',
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

    public function employee()
    {
        return $this->hasOne(User::class, 'id', 'till_assigned_to_user')
            ->where('role', 'employee')
            ->select('id', 'user_name'); // Correct field name 'user_name' instead of 'user_names'
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function cashAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'cash_account');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'edited_by');
    }
}
