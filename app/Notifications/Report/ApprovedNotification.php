<?php

namespace App\Notifications\Report;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApprovedNotification extends Notification
{
    use Queueable;

    public $data = [];
    public function __construct(public $report_id)
    {
        $this->data = [
            // 'user_id' => auth()->user()->id,
            'avatar' => "https://media.istockphoto.com/id/1221750570/vi/vec-to/c%E1%BA%A3nh-b%C3%A1o-d%E1%BA%A5u-ch%E1%BA%A5m-than-v%E1%BB%81-tr%C6%B0%E1%BB%9Dng-h%E1%BB%A3p-kh%E1%BA%A9n-c%E1%BA%A5p.jpg?s=612x612&w=0&k=20&c=v0E0M28hEWODCRg0uZRf_9o-bcw09J05nnPcOXW-REE=",
            'report_id' => $this->report_id,
            'message' => 'Đơn tố cáo của bạn đã bị từ chối!'
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
