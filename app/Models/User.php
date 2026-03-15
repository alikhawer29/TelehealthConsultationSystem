<?php

namespace App\Models;

use App\Core\Traits\Authable;
use App\Core\Traits\Fileable;
use App\Core\Traits\Filterable;
use App\Core\Traits\Addressable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Carbon\Carbon;

class User extends Authenticatable
{
    use HasApiTokens, Fileable, HasFactory, Notifiable, Authable, SoftDeletes, Filterable, Addressable;

    protected $images = [
        'file' => 'single',
    ];

    protected $imageable = ['file'];


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'country_code',
        'phone',
        'status',
        'role',
        'login_status',
        'stripe_customer_id',
        'professional',
        'about',
        'experience',
        'languages',
        'flag_type',
        'zoho_id',
        'scan_type'
    ];

    protected $appends = [
        'status_detail',
        'phone_number',
        'complete_profile',

    ];



    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */

    protected $hidden = [
        'updated_at',
        'deleted_at',
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
        'session_type' => 'array',
        'languages' => 'array',
    ];

    public function getCompleteProfileAttribute()
    {
        // Check if the role is one of the healthcare-related roles
        if (in_array($this->role, ['doctor', 'nurse', 'physician'])) {
            // List of all the required fields to check for the User model itself
            $requiredFields = [
                'first_name',
                'last_name',
                'email',
                'password',
                'country_code',
                'phone',
                'status',
                'role',
                'professional',
                'about',
                'experience',
                'languages',
            ];

            // Check all required fields for the User model itself
            foreach ($requiredFields as $field) {
                $value = $this->{$field};  // Checking User model's field

                // If the field is empty, null, or invalid (except for 0), return false
                if (empty($value) && $value !== 0) {
                    return false;
                }
            }

            // Always required
            $education = $this->education()->first();
            $license = $this->license()->first();

            if (!$education || !$license) {
                return false;
            }

            // For doctor, also check sessionType
            $sessionType = null;
            if ($this->role === 'doctor') {
                $sessionType = $this->sessionType()->first();
                if (!$sessionType) {
                    return false;
                }
            }

            // Fields required per model
            $relatedFields = [
                'education' => ['institution_name', 'degree_title'],
                'license' => ['authroity', 'number', 'expiry'],
            ];

            // Add sessionType fields if doctor
            if ($this->role === 'doctor') {
                $relatedFields['license'][] = 'specialty';
                $relatedFields['sessionType'] = ['session_type', 'price'];
            }

            // Validate each related model field
            foreach ($relatedFields as $modelName => $fields) {
                $model = $$modelName;
                foreach ($fields as $field) {
                    $value = $model->{$field};
                    if (empty($value) && $value !== 0) {
                        return false;
                    }
                }
            }
        }

        // If all required fields are filled, return true
        return true;
    }

    public function setFirstNameAttribute($value)
    {
        $this->attributes['first_name'] = Crypt::encryptString($value);
        $this->attributes['first_name_hash'] = hash('sha256', strtolower(trim($value)));
    }

    public function getFirstNameAttribute($value)
    {
        return Crypt::decryptString($value);
    }

    public function setLastNameAttribute($value)
    {
        $this->attributes['last_name'] = Crypt::encryptString($value);
        $this->attributes['last_name_hash'] = hash('sha256', strtolower(trim($value)));
    }

    public function getLastNameAttribute($value)
    {
        return Crypt::decryptString($value);
    }

    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = Crypt::encryptString($value);
        $this->attributes['email_hash'] = hash('sha256', strtolower(trim($value)));
    }

    // public function getEmailAttribute($value)
    // {
    //     return Crypt::decryptString($value);
    // }

    public function getEmailAttribute($value)
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            // value might be plain text (admins table, seed data, etc.)
            return $value;
        }
    }


    public function setPhoneAttribute($value)
    {
        $this->attributes['phone'] = Crypt::encryptString($value);
        $this->attributes['phone_number_hash'] = hash('sha256', strtolower(trim($value)));
    }

    public function getPhoneAttribute($value)
    {
        return Crypt::decryptString($value);
    }

    public function setCountryCodeAttribute($value)
    {
        $this->attributes['country_code'] = Crypt::encryptString($value);
        $this->attributes['country_code_hash'] = hash('sha256', strtolower(trim($value)));
    }

    public function getCountryCodeAttribute($value)
    {
        return Crypt::decryptString($value);
    }


    public function isDoctor()
    {
        return $this->where('role', 'doctor')->exists();
    }
    public function isNurse()
    {
        return $this->where('role', 'nurse')->exists();
    }

    public function isPhysician()
    {
        return $this->where('role', 'physician')->exists();
    }


    public function isUser()
    {
        return $this->where('role', 'user')->exists();
    }

    public function password(): Attribute
    {
        return Attribute::make(
            set: fn($value) => bcrypt($value)
        );
    }

    public function phoneNumber(): Attribute
    {
        return Attribute::make(
            get: fn() => "{$this->country_code}{$this->phone}"
        );
    }

    public function officePhoneNumber(): Attribute
    {
        return Attribute::make(
            get: fn() => "{$this->office_country_code}{$this->office_phone}"
        );
    }


    public function statusDetail(): Attribute
    {
        return Attribute::make(
            get: fn() => match ($this->status) {
                0 => 'Inactive',
                1 => 'Active',
                default => 'Unknown',  // Fallback in case an invalid status is present
            },
        );
    }

    public function education(): HasMany
    {
        return $this->hasMany(Education::class, 'user_id');
    }

    public function license(): HasOne
    {
        return $this->hasOne(License::class, 'user_id');
    }

    public function webexToken(): HasOne
    {
        return $this->hasOne(WebexToken::class, 'doctor_id', 'id');
    }

    public function insurance(): HasOne
    {
        return $this->hasOne(Insurance::class, 'user_id');
    }

    public function passport(): HasOne
    {
        return $this->hasOne(Media::class, 'fileable_id');
    }

    public function sessionType(): HasMany
    {
        return $this->hasMany(SessionType::class, 'user_id');
    }


    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class, 'user_id');
    }

    public function timeSlots(): HasMany
    {
        return $this->hasMany(UserTimeSlot::class, 'user_id');
    }

    public function accessRights(): HasMany
    {
        return $this->hasMany(AccessManagement::class, 'employee_id');
    }

    public function accountsPermission(): HasMany
    {
        return $this->hasMany(AccountsPermission::class, 'employee_id');
    }

    public function branches()
    {
        return $this->hasMany(Branch::class, 'user_id', 'id'); // Adjust the foreign key if different
    }

    public function currentSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class, 'user_id')->where('status', 'active');
    }

    public function familyMembers()
    {
        return $this->hasMany(FamilyMember::class, 'user_id', 'id')->orderBy('id', 'desc');
    }

    // Supplier has many quotations
    public function favourites()
    {
        return $this->hasMany(Favourite::class, 'user_id', 'id');
    }


    public function favourite(): HasOne
    {
        return $this->hasOne(Favourite::class, 'supplier_id');
    }

    public function supplierFavourite()
    {
        return $this->hasMany(Favourite::class, 'supplier_id', 'id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'bookable_id', 'id')->where('service_type', 'doctor')->where('bookable_type', 'App\Models\User');
    }


    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable')->latest();
    }

    public function addresses()
    {
        return $this->hasMany(Address::class, 'user_id');
    }
    public function address()
    {
        return $this->hasOne(Address::class, 'user_id')->latest()->where('status', 'active');
    }


    protected function extendValidation(): array
    {
        // Only valid if the user status is "Active" (1)
        $valid = $this->status == 1;

        // Define status messages
        $messages = [
            0 => "Profile is Inactive",
            1 => "Profile is Active",   // Optional: Customize for active status
        ];

        // Check if the profile is verified; if not, override message
        $message = $messages[$this->status] ?? "Unknown Profile Status";


        return [$valid, $message];
    }

    public function cities(): HasOne
    {
        return $this->hasOne(City::class, 'id', 'city');
    }
    public function states(): HasOne
    {
        return $this->hasOne(State::class, 'id', 'state');
    }
    public function countries(): HasOne
    {
        return $this->hasOne(Country::class, 'id', 'country');
    }

    public function delivery(): HasMany
    {
        return $this->hasMany(Address::class, 'user_id');
    }

    public function ratting(): HasMany
    {
        return $this->hasMany(Review::class, 'user_id');
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function partyLedgersAccountType(): Attribute
    {
        return Attribute::make(
            get: function () {
                $data = PartyLedgerAccountReference::where('branch_id', $this->selected_branch)->first();
                if ($data) {
                    return $data->account_code;
                } else {
                    return false;
                }
            }
        );
    }

    public function branchName(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->selected_branch) {
                    return null; // If no selected branch, return null
                }

                $data = Branch::find($this->selected_branch); // Using find() instead of where->first()

                return $data ? $data->name : null;
            }
        );
    }

    // Convert created_at to Dubai timezone
    public function getCreatedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->timezone('Asia/Dubai')->toDateTimeString() : null;
    }

    // Convert updated_at to Dubai timezone
    public function getUpdatedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->timezone('Asia/Dubai')->toDateTimeString() : null;
    }

    // Convert deleted_at to Dubai timezone
    public function getDeletedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->timezone('Asia/Dubai')->toDateTimeString() : null;
    }
}
