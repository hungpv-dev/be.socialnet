<?php

namespace App\Notifications\Comment;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RepCommentNotification extends Notification
{
    use Queueable;
    public $data = [];
    public function __construct(public $post, public $comment)
    {
        $this->data = [
            'post_id' => $this->post,
            'comment_id' => $this->comment,
            'avatar' => auth()->user()->avatar,
            'message' => '<b>' . auth()->user()->name . '</b> đã phản hồi bình luận của bạn.'
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
