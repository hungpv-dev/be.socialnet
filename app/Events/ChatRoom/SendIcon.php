<?php

namespace App\Events\ChatRoom;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SendIcon implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $chat_room_id;
    public $message;
    public function __construct($chat_room_id, $message)
    {
        $this->chat_room_id = $chat_room_id;
        $this->message = $message;
    }

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('room.push-message.' . $this->chat_room_id);
    }

    public function broadcastWith(): array
    {
        return [
            'message' => $this->message,
            'icon' => $this->message->emotions,
        ];
    }
}
