<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user_id = auth()->user()->id;
        $response = [];
        $userIds = array_diff($this->is_seen, [$user_id]);
        $users = User::whereIn('id', $userIds)->select('id', 'name', 'avatar')->get();
        $response['id'] = $this->id;
        $response['user_send'] = $this->user;
        $response['content'] = $this->body;
        $response['files'] = $this->files;
        $response['is_seen'] = $users;
        $response['flagged'] = in_array($user_id, $this->flagged);
        $response['created_at'] = $this->created_at;
        if ($this->replyTo) {
            $response['reply_to'] = [
                'files' => corverFiles(files: $this->replyTo->files),
                'content' => $this->replyTo->body,
                'flagged' => in_array($user_id, $this->replyTo->flagged)
            ];
        } else {
            $response['reply_to'] = $this->replyTo;
        }
        return $response;
    }
}
