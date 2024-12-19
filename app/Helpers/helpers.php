<?php

use App\Events\ChatRoom\PushMessage;
use App\Http\Resources\MessageResource;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;

function corverFiles($files){
    $checkFile = 'none';
    if(!empty($files)){
        $checkFile == 'images';
    }
    return $checkFile;
}

function createNofiMessage($roomId, $content){
    $user_id = Auth::id();
    $data = [
        'chat_room_id' => $roomId,
        'user_id' => $user_id,
        'body' => $content,
        'created_at' => now()->format('Y-m-d H:i:s'),
        'is_seen' => ['user_' . $user_id],
        'flagged' => [],
        'files' => [],
        'is_nofi' => true,
    ];
    $message = Message::create($data);
    $message->is_nofi = true;
    broadcast(new PushMessage($roomId, [new MessageResource($message)]));
}

if (!function_exists('custom_url')) {
    function custom_url($path = null, $parameters = [], $secure = null)
    {
        $url = url($path, $parameters, $secure);
        return str_replace('localhost', 'localhost', $url);
    }
}