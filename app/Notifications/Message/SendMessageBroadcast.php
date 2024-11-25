<?php

namespace App\Notifications\Message;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class SendMessageBroadcast extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public $message, public $room)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['broadcast'];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        $messageType = !empty($this->message->files) ? 'đã gửi hình ảnh' : ': ' . $this->message->body;
        
        return new BroadcastMessage([
            'notification' => $this->room->name['user_' . $this->message->user_id] . ' ' . $messageType,
            'time' => now()->format('H:i d/m/Y'),
            'room_id' => $this->room->id
        ]);
    }
}
