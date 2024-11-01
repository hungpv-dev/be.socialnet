<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user_id = Auth::id();
        $response = [];
        $userIds = array_map(function($id){
            return (int) str_replace('user_', '', $id);
        }, array:  $this->is_seen);
        $users = User::whereIn('id', $userIds)->select('id', 'name', 'avatar')->get();
        $response['message_id'] = $this->id;
        $response['user_send'] = [
            'id' => $this->user->id,
            'name' => $this->user->name,
            'is_online' => $this->user->is_online,
            'time_offline' => $this->user->time_offline,
            'avatar' => $this->user->avatar,
        ];
        $response['content'] = $this->body;
        $response['files'] = $this->files;
        $response['is_seen'] = $users;
        $response['is_nofi'] = $this->is_nofi;
        $response['emotions'] = $this->emotions;
        $response['flagged'] = in_array('user_'.$user_id, $this->flagged);
        $response['created_at'] = $this->created_at;
        if ($this->replyTo) {
            $response['reply_to'] = [
                'files' => $this->replyTo->files,
                'content' => $this->replyTo->body,
                'flagged' => in_array('user_'.$user_id, $this->replyTo->flagged)
            ];
        } else {
            $response['reply_to'] = $this->replyTo;
        }
        return $response;
    }
}
