<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Education extends Model
{
    use HasFactory;

    // Specify the table name (optional if the table name follows Laravel's convention)
    protected $table = 'education';

    // Specify the fillable fields for mass assignment
    protected $fillable = [
        'user_id',
        'institution_name',
        'degree_title',
    ];

    // Define the relationship with the User model (assuming each user has many time slots)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
