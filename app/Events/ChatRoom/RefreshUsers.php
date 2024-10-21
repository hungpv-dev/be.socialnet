<?php

namespace App\Events\ChatRoom;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RefreshUsers implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $room;
    public function __construct($room)
    {
        $this->room = $room;
    }

    public function broadcastOn()
    {
        $channels = [];
        foreach ($this->room->user as $user) {
            $userId =  (int) str_replace('user_', '', $user); 
            $channels[] = new PrivateChannel('room.refresh-users.' . $userId);
        }
        return $channels;
    }

    public function broadcastWith(): array
    {
        return [
            'room' => $this->room
        ];
    }
}
