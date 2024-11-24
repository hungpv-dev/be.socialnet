<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CreatePost extends Notification
{
    use Queueable;

    public $data = [];
    public function __construct(public $post, public $message)
    {
        $this->data = [
            'post_id' => $this->post,
            'avatar' => auth()->user()->avatar,
            'message' => '<b>' . auth()->user()->name . '</b> ' . $this->message
        ];
    }

    public function via(object $notifiable): array
    {
        return ['broadcast', 'database'];
    }
    public function toArray(object $notifiable): array
    {
        return $this->data;
    }

}