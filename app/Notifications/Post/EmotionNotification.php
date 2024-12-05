<?php

namespace App\Notifications\Post;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class EmotionNotification extends Notification
{
    use Queueable;
    public $data = [];

    public function __construct($post_id, $message, $type)
    {
        $this->data = [
            'post_id' => $post_id,
            'avatar' => auth()->user()->avatar,
            'message' => '<b>' . auth()->user()->name . '</b> ' . $message,
            'type' => $type
        ];
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return $this->data;
    }
}
