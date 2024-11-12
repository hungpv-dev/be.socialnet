<?php

namespace App\Notifications\Friend;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FriendNotification extends Notification
{
    use Queueable;
    public $data = [];
    public function __construct()
    {
        $this->data = [
            'user_id' => auth()->user()->id,
            'avatar' => auth()->user()->avatar,
            'message' => '<b>' . auth()->user()->name . '</b> chấp nhận lời mời kết bạn của bạn.'
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
