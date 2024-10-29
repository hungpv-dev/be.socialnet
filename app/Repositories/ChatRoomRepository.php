<?php   
namespace App\Repositories;

use App\Http\Resources\ChatRoomResource;
use App\Models\ChatRoom;
use App\Models\ChatType;
use App\Models\User;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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
                if($request->has('avatar')){
                    $avatar = $request->file('avatar');
                    $path = $avatar->store('rooms/avatar','public');
                    $fullPath = url('storage/' . $path);
                    $data['avatar'] = $fullPath;
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

    public function namesUpdate($request,$room){
        $id = Auth::id();
        $names = $room->name;
        $newName = [];
        foreach($names as $k =>  $val){
            if($k == 'user_'.$id){
                $newName[$k] = $request->theirNickname == '' ? $val : $request->theirNickname;
            }else{
                $newName[$k] = $request->myNickname == '' ? $val : $request->myNickname;
            }
        }
        $room->name = $newName;
        $room->save();
        return $room;
    }

    public function blockUpdate($request,$room){
        $block = 'user_'.$request->block;
        $list = $room->blocks;
        $check = in_array($block, haystack: $list);
        if($check){
            $list = array_diff($list, [$block]);
        }else{
            $list = [...$list,$block];
        }
        $room->blocks = $list;
        $room->save();
        return $room;
    }
    public function avatarUpdate($request,$room){
        if($request->has('avatar')){
            // Xóa ảnh cũ nếu có
            if($room->avatar) {
                $oldPath = str_replace(url('storage/'), '', $room->avatar);
                Storage::disk('public')->delete($oldPath);
            }
            
            $avatar = $request->file('avatar');
            $path = $avatar->store('rooms/avatar','public');
            $fullPath = url('storage/' . $path);
            $room->avatar = $fullPath;
            $room->save();
        }
        return $room;
    }
}