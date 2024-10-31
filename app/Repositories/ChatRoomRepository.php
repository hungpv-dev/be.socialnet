<?php   
namespace App\Repositories;

use App\Http\Resources\ChatRoomResource;
use App\Models\ChatRoom;
use App\Models\ChatType;
use App\Models\User;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Auth;

class ChatRoomRepository
{
    public function createChatRoom($request)
    {
        $user_id = Auth::id();
        $data = [];
        $chat_type = $request->chat_type_id;
        try{
            $chatType = ChatType::findOrFail($chat_type);
            // Loại phòng chat
            $data['chat_type_id'] = $chatType->id;
            if($chatType->id == 1){
                // Tạo phòng chat riêng
                $friend_id = $request->input('friend_id');
                $chat_room = ChatRoom::whereJsonContains('user', ['user_'.$user_id, 'user_'.$friend_id])->first();
                if(!$chat_room){
                    $data['user'] = ['user_'.$user_id, 'user_'.$friend_id];
                    $data['admin'] = [];
                    $data['blocks'] = [];
                    $data['name'] = [
                        'user_'.$friend_id => $request->user()->name,
                        'user_'.$user_id => User::findOrFail($friend_id)->name,
                    ];
                    $data['last_remove'] = [
                        'user_'.$user_id => null,
                        'user_'.$friend_id => null,
                    ];
                    $data['last_active'] = [
                        'user_'.$user_id => now(),
                        'user_'.$friend_id => now(),
                    ];
                    $data['notification'] = ['user_'.$user_id, 'user_'.$friend_id];
                    $data['created_at'] = now();
                    $chat_room = ChatRoom::create($data);
                }
            }else{
                // Tạo phòng chat nhóm
                $user = [...$request->user, $user_id];
                foreach($user as $u){
                    $data['user'][] = 'user_'.$u;
                }
                $data['admin'] = ['user_'.$user_id];
                $data['blocks'] = [];
                $room_name = $request->input('room_name');
                $users = User::whereIn('id', $user)->get();
                foreach($users as $user){
                    $data['name']['user_'.$user->id] = $room_name;
                    $data['last_remove']['user_'.$user->id] = null;
                    $data['last_active']['user_'.$user->id] = now();
                    $data['notification'][] = 'user_'.$user->id;
                }
                $data['created_at'] = now();
                $chat_room = ChatRoom::create($data);
            }
        }catch(\Exception $e){
            return response([
                'message' => 'Không tìm thấy loại phòng chat',
                'error' => $e->getMessage(),
            ], 404);
        }
        return response([
            'data' => new ChatRoomResource($chat_room)
        ], 201);
    }

    public function notification($id)
    {
        try{
            $chatRoom = ChatRoom::findOrFail($id);
            $user_id = Auth::id();
            $check = in_array('user_'.$user_id, $chatRoom->notification);
            if($check){
                $chatRoom->notification = array_diff($chatRoom->notification, ['user_'.$user_id]);
            }else{
                $chatRoom->notification = [...$chatRoom->notification, 'user_'.$user_id];
            }
            $chatRoom->save();
        }catch(\Exception $e){
            return response([
                'message' => 'Không tìm thấy phòng chat',
                'error' => $e->getMessage(),
            ], 404);
        }
        return response([
            'data' => new ChatRoomResource($chatRoom)
        ], 200);
    }
}