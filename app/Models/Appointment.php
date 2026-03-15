<?php

namespace App\Models;

use App\Core\Traits\Fileable;
use App\Core\Traits\Filterable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Appointment extends Model
{
    use HasFactory, Filterable, Fileable, SoftDeletes, Notifiable;

    protected $fillable = [
        'booking_id',
        'slot_id',
        'user_id',
        'service_type',
        'session_type',
        'bookable_id',
        'bookable_type',
        'status',
        'appointment_status',
        'request_date',
        'request_start_time',
        'request_end_time',
        'appointment_date',
        'appointment_start_time',
        'appointment_end_time',
        'session_code',
        'is_live',
        'amount',
        'payment_status',
        'family_member_id',
        'provider',
        'address_id',
        'reason',
        'report_status',
        'notes',
        'provider_reason',
        'host_join_url',
        'guest_join_url',
        'host_cipher',
        'guest_cipher',
        'meetingId',
        'sipAddress',
        'webLink',
        'password',
        'host_key',
        'meeting_number',
        'payment_type',
        'zoho_chat_id',
        'is_custom'
    ];

    protected $hidden = [
        'updated_at',
        'deleted_at',
    ];

    protected $appends = ['booking_status', 'bookable_name'];

    public function getBookableNameAttribute()
    {
        if ($this->bookable instanceof \App\Models\User) {
            return trim("{$this->bookable->first_name} {$this->bookable->last_name}");
        }

        if ($this->bookable instanceof \App\Models\Service) {
            return $this->bookable->name;
        }

        if ($this->bookable instanceof \App\Models\ServiceBundle) {
            return $this->bookable->bundle_name;
        }

        return null;
    }




    public function serviceProvider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider');
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'address_id');
    }

    public function bundleServices(): HasMany
    {
        return $this->hasMany(BundleService::class, 'bundle_id', 'bookable_id');
    }

    public function chat(): HasMany
    {
        return $this->hasMany(Chat::class, 'appointment_id', 'id')
            ->where(function ($query) {
                $userId = Auth::id(); // or pass user ID dynamically
                $query->where('sender_id', $userId)
                    ->orWhere('receiver_id', $userId);
            });
    }

    public function chats(): HasOne
    {
        return $this->hasOne(Chat::class, 'appointment_id', 'id')
            ->where(function ($query) {
                $userId = Auth::id(); // or pass user ID dynamically
                $query->where('sender_id', $userId)
                    ->orWhere('receiver_id', $userId);
            });
    }




    public function slot(): BelongsTo
    {
        return $this->belongsTo(Slot::class, 'slot_id');
    }

    public function familyMember(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class, 'family_member_id');
    }


    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'appointment_id');
    }


    public function userable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function prescription()
    {
        return $this->hasMany(Prescription::class, 'appointment_id', 'id');
    }

    public function bookable()
    {
        return $this->morphTo();
    }
    public function packageable()
    {
        return $this->morphTo();
    }

    public function payment()
    {
        return $this->hasOne(Payment::class, 'payable_id')->where('payable_type', 'App\Models\Appointment');
    }

    public function bookingStatus(): Attribute
    {
        return Attribute::make(get: function () {
            $today = now()->format('Y-m-d');
            $currentTime = now()->format('H:i');
            //past date
            if ($today > $this->appointment_date) {
                return 'Past';
                //future date
            } elseif ($today < $this->appointment_date) {
                return 'Upcoming';
            } else {
                //current date and time
                if ($currentTime >= $this->appointment_start_time && $currentTime <= $this->appointment_end_time) {
                    return 'In-Progress';
                } elseif ($currentTime > $this->appointment_start_time) {
                    return 'Past';
                } else {
                    return 'Upcoming';
                }
            }
        });
    }

    public function providerUser()
    {
        return $this->belongsTo(User::class, 'bookable_id');
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

    public function attendedLogs()
    {
        return $this->hasMany(CheckSessionAttendedUserLog::class, 'appointment_id');
    }
}
