<?php

namespace App\Events\CommentEvent;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CommentNotification implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $from_user;
    public $to_user;
    public $message;
    public $comment_id;

    public function __construct($to_user, $message, $comment_id)
    {
        $this->from_user = auth()->user()->name;
        $this->to_user = $to_user;
        $this->message = $message;
        $this->comment_id = $comment_id;

        // Log::info( '<b>' . auth()->user()->name . '</b>' . $message );
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('comment_notification.' . $this->to_user),
        ];
    }
    public function broadcastWith()
    {
        return [
            'to_user' => $this->to_user,
            'message' => $this->message,
            'comment_id'=> $this->comment_id,
        ];
    }
}
