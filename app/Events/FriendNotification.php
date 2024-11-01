<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FriendNotification
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $sender;
    public $receiver;
    public $message;
    public function __construct($sender, $receiver, $message)
    {
        $this->sender = $sender;
        $this->receiver = $receiver;
        $this->message = $message;

        // Log::info('FriendNotification created', [
        //     'sender' => $this->sender,
        //     'receiver' => $this->receiver,
        //     'message' => $this->message,
        // ]);
    }
    public function broadcastOn()
    {
        return new PrivateChannel('friend-notification'. $this->receiver);
    }
    public function broadcastWith(): array
    {
        return [
            'sender' => $this->sender,
            'message' => $this->message,
        ];
    }
}
