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
        "created_at",
        "reply_to",
        'is_nofi',
    ];

    public $timestamps = false;
    protected $casts = [
        "is_seen" => 'array',
        "flagged" => 'array',
        "files" => 'array',
        'is_nofi' => 'boolean'
        // 'created_at' => 'datetime'
    ];

    public function emotions(){
        return $this->morphMany(Emotion::class, 'emotionable');
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function replyTo(){
        return $this->belongsTo(Message::class, 'reply_to');
    }
}
