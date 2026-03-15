<?php

namespace App\Models;

use App\Core\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Chat extends Model
{
    use HasFactory, Filterable;

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'chat_type',
        'appointment_id',
        'type',
        'is_live'
    ];

    // Relationship: A chat has many messages
    public function messages()
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'asc');
    }

    // Relationship: A chat belongs to a sender (User)
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    // Relationship: A chat belongs to a receiver (User)
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    // Relationship: A chat belongs to a purchase order
    public function purchaseOrder()
    {
        return $this->belongsTo(Appointment::class, 'appointment_id', 'id');
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class, 'appointment_id', 'id');
    }

    public function lastMessage()
    {
        return $this->hasOne(Message::class)->latest(); // Get the last message in this chat
    }

    public function unreadMessagesCount()
    {
        return $this->hasMany(Message::class, 'chat_id', 'id')
            ->where('is_read', false)
            ->where('receiver_id', auth()->id()); // only unread messages *to* current user
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
}
