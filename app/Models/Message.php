<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;
    protected $fillable = [
        "chat_room_id",
        "body",
        "is_seen",
        "user_id",
        "flagged",
        "files",
        "reply_to"
    ];

    protected $casts = [
        "is_seen" => 'array',
        "flagged" => 'array',
        "files" => 'array',
        'created_at' => 'datetime'
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function replyTo(){
        return $this->belongsTo(Message::class, 'reply_to');
    }
}
