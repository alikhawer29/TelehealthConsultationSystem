<?php

namespace App\Models;

use App\Core\Traits\Fileable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class License extends Model
{
    use HasFactory, Fileable;

    protected $images = [
        'license_file' => 'single',
    ];

    protected $imageable = ['license_file'];


    // Specify the table name (optional if the table name follows Laravel's convention)
    protected $table = 'license';

    // Specify the fillable fields for mass assignment
    protected $fillable = [
        'user_id',
        'authroity',
        'number',
        'expiry',
        'specialty'
    ];

    // Define the relationship with the User model (assuming each user has many time slots)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
