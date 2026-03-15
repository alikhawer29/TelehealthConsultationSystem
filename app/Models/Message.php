<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_id',
        'sender_id',
        'receiver_id',
        'message',
        'is_read',
        'chat_type'
    ];


    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    // Relationship: A message belongs to a chat
    public function chat()
    {
        return $this->belongsTo(Chat::class);
    }

    // Relationship: A message belongs to a sender (User)
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function getSenderModel()
    {
        if ($this->sender_id == 1) {
            return Admin::find($this->sender_id);
        }
        return User::find($this->sender_id);
    }

    public function getReceiverModel()
    {
        if ($this->receiver_id == 1) {
            return Admin::find($this->receiver_id);
        }
        return User::find($this->receiver_id);
    }
}
