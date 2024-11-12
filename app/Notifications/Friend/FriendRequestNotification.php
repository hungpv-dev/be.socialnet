<?php

namespace App\Notifications\Friend;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class FriendRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;
    public $data = [];
    public function __construct()
    {
        $this->data = [
            'user_id' => auth()->user()->id,
            'avatar' => auth()->user()->avatar,
            'message' => '<b>' . auth()->user()->name . '</b> đã gửi cho bạn lời mời kết bạn'
        ];
    }
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }
    public function toArray($notifiable):array{
        return $this->data;
    }
}
