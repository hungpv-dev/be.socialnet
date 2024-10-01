<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatRoom extends Model
{
    use HasFactory;
    protected $table = 'chat_rooms';

    protected $fillable = [
        'chat_type_id',
        'name',
        'user',
        'admin',
        'last_remove',
        'last_active',
    ];

    protected $casts = [
        'name' => 'array',
        'admin' => 'array',
        'user' => 'array',
        'last_active' => 'array',
        'last_remove' => 'array',
    ];

    public function lastMessage()
    {
        return $this->hasOne(Message::class, 'chat_room_id')->latest();
    }


    public function chat_type()
    {
        return $this->belongsTo(ChatType::class);
    }
}
