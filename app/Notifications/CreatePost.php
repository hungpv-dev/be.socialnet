<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CreatePost extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public $data = []; // Khởi tạo mảng rỗng

    public function __construct(public $post, public $user) 
    {
        // Khởi tạo data ngay trong constructor
        $this->data = [
            'post' => $this->post,
            'user' => $this->user,
        ];
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return $this->data;
    }

}