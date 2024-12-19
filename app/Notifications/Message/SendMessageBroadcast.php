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
    public function __construct(public $message, public $room, public $user_id)
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
        $messageType = !empty($this->message->files) ? 'đã gửi hình ảnh' : ': ' . ($this->message ? $this->message->body : '');

        $name = optional($this->room)->name['user_' . $this->user_id] ?? '';

        return new BroadcastMessage([
            'notification' => $name . ' ' . $messageType,
            'time' => now()->format('H:i d/m/Y'),
            'room_id' => $this->room->id
        ]);
    }
}
