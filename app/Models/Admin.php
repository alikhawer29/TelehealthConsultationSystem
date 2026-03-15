<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Model;

use App\Core\Traits\Authable;
use App\Core\Traits\Fileable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Authenticatable
{
    use HasApiTokens, Fileable, HasFactory, Notifiable, Authable;


    protected $images = [
        'image' => 'single',
    ];

    protected $provider = 'admin';

    protected $appends = ['phone_number', 'role'];


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'password',
        'country_code',
        'phone'
    ];

    protected $imageable = ['image'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function password(): Attribute
    {
        return Attribute::make(
            set: fn($value) => bcrypt($value)
        );
    }

    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable')->latest();
    }

    public function role(): Attribute
    {
        return Attribute::make(
            get: fn() => 'admin',
        );
    }

    public function phoneNumber(): Attribute
    {
        return Attribute::make(
            get: fn() => "{$this->country_code}{$this->phone}"
        );
    }
    public function isUser()
    {
        return $this->where('is_admin_type', 0)->exists();
    }

    public function isDoctor()
    {
        return $this->where('is_admin_type', 0)->exists();
    }


    public function isNurse()
    {
        return $this->where('is_admin_type', 0)->exists();
    }

    public function isPhysician()
    {
        return $this->where('is_admin_type', 0)->exists();
    }

    public function isDriver()
    {
        return $this->where('is_admin_type', 0)->exists();
    }

    public function isCompany()
    {
        return $this->where('is_admin_type', 0)->exists();
    }
}
