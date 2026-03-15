<?php

namespace App\Models;

use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MedicalOrder extends Model
{

    use HasFactory, Filterable;

    protected $table = 'medical_orders';

    protected $fillable = [
        'user_id',
        'name',
        'quantity',
        'medicine_name',
        'contact_details',
        'payment_method',
        'status',
        'reason'
    ];
}
