<?php

namespace App\Http\Controllers;

use App\Http\Resources\ChatRoomResource;
use App\Models\ChatRoom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class ChatRoomController extends Controller
{
    public function index(){
        $user_id = Auth::id();
        $chatrooms = ChatRoom::whereJsonContains('user', $user_id)
        ->with('lastMessage')
        ->simplePaginate(perPage: 15);
        return ChatRoomResource::collection($chatrooms);
    }

    public function show($id)
    {
        $user_id = Auth::id();
        try {
            $room = ChatRoom::whereJsonContains('user', $user_id)->findOrFail(id: $id);
            return new ChatRoomResource($room);
        } catch (\Exception $e) {
            return $this->sendResponse([
                'message' => 'Không tìm thấy phòng chat',
                'error' => $e->getMessage(),
            ], 404);
        }
    }
}
