<?php

namespace App\Notifications\Story;

use App\Models\UserStories;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmotionNotification extends Notification
{
    use Queueable;
    public $data = [];
    public function __construct(public $story, public $message)
    {
        $this->data = [
            'story_id' => $this->story,
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
