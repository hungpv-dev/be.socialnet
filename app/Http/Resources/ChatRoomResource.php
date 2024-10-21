<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class ChatRoomResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user_id = Auth::id();
        $response = [];
        $response['chat_room_id'] = $this->id;
        $response['chat_room_type'] = $this->chat_type_id;
        $userIds = array_map(function($id){
            return (int) str_replace('user_', '', $id);
        }, array: array_diff( $this->user, ['user_'.$user_id]));
        $users = User::whereIn('id',$userIds)->select('id','name','time_offline','is_online','avatar')->get();
        $response['name'] = $this->name['user_'.$user_id] ?? '';
        $response['online'] = $users->pluck('is_online')->contains(1);
        $response['notification'] = in_array('user_'.$user_id, $this->notification) ? true : false;
        $response['block'] = in_array('user_'.$user_id, $this->blocks) ? true : false;
        $response['users'] = $users;
        $last_message = $this->lastMessage;
        if($last_message){
            $response['last_message'] = [
                'id' => $last_message->id,
                'body' => $last_message->body,
                'is_seen' => in_array('user_'.$user_id, $last_message->is_seen),
                'flagged' => in_array('user_'.$user_id, $last_message->flagged),
                'files' => $last_message->files,
                'created_at' => $last_message->created_at,
            ];
        }else{
            $response['last_message'] = $last_message;
        }
        return $response;
    }
}
