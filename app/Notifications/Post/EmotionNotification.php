<?php

namespace App\Notifications\Post;

use App\Models\UserStories;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmotionNotification extends Notification
{
    use Queueable;
    public $data = [];
    public function __construct(public $id, public $message, public $type)
    {
        $this->data = [
            'avatar' => auth()->user()->avatar,
            'message' => '<b>' . auth()->user()->name . '</b> ' . $this->message
        ];
        if($type == 1){
            $this->data['post_id'] = $this->id;
        }else{
            $this->data['comment_id'] = $this->id;
        }
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
