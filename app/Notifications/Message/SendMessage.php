<?php

namespace App\Notifications\Message;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Slack\BlockKit\Blocks\ContextBlock;
use Illuminate\Notifications\Slack\BlockKit\Blocks\SectionBlock;
use Illuminate\Notifications\Slack\SlackMessage;

class SendMessage extends Notification
{
    use Queueable;

    public function __construct(public $message, public $room)
    {
    }

    public function via(object $notifiable): array
    {
        return ['slack'];
    }
    
    public function toSlack(object $notifiable): SlackMessage
    {
        return (new SlackMessage)
                ->headerBlock('Tin nhắn mới')
                ->text('Tin nhắn mới từ ' . $this->room->name['user_'.auth()->user()->id])
                ->contextBlock(function (ContextBlock $block) {
                    $block->text('Từ: ' . $this->room->name['user_'.auth()->user()->id]);
                })
                ->sectionBlock(function (SectionBlock $block) {
                    $block->text($this->message);
                    $block->field("*Thời gian:*\n" . now()->format('H:i d/m/Y'))->markdown();
                });
    }
}
