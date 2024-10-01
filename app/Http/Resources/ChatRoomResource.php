<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatRoomResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user_id = auth()->user()->id;
        $response = [];
        $response['chat_room_id'] = $this->id;
        $userIds = array_diff( $this->user, [$user_id]);
        $users = User::whereIn('id',$userIds)->select('id','is_online','time_offline','name','avatar')->get();
        $response['name'] = $this->name[$user_id];
        $response['users'] = $users;
        $response['admins'] = $this->admin;
        $response['status'] = $users->pluck('is_online')->contains(1);
        $response['last_active'] = $this->last_active[$user_id];
        $response['last_remove'] = $this->last_remove[$user_id];
        
        $last_message = $this->lastMessage;
        if($last_message){
            $response['last_message'] = [
                'id' => $last_message->id,
                'body' => $last_message->body,
                'is_seen' => in_array($user_id, $last_message->is_seen),
                'flagged' => in_array($user_id, $last_message->flagged),
                'files' => $last_message->files,
            ];
        }else{
            $response['last_message'] = $last_message;
        }
        return $response;
    }
}
