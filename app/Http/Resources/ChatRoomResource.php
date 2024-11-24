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
        $response['avatar'] = $this->avatar;
        $userIds = array_map(function($id){
            return (int) str_replace('user_', '', $id);
        },$this->user);
        $users = User::whereIn('id',$userIds)->select('id','name','time_offline','is_online','avatar')->get();
        $response['name'] = $this->name['user_'.$user_id] ?? '';
        $response['online'] = $users->where('id','!=',$user_id)->pluck('is_online')->contains(1);
        $response['notification'] = in_array('user_'.$user_id, $this->notification) ? true : false;
        $response['block'] = $this->blocks;
        $response['users'] = $users->where('id','!=',$user_id)->values();
        $idAdmin = array_map(function($id){
            return (int) str_replace('user_', '', $id);
        }, array: $this->admin);
        $response['admin'] = $users->whereIn('id', $idAdmin)->values();
        $response['outs'] = $this->outs;

        $lastActive = $this->last_active['user_'.$user_id];

        $lastRemove = $this->last_remove['user_'.$user_id] ?? now()->format('Y-m-d H:i:s');
        $last_message = $this->messages()->whereBetween('created_at', [$lastActive, $lastRemove])->latest('id')->first();
        
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
