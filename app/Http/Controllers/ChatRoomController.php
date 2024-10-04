<?php

namespace App\Http\Controllers;

use App\Http\Resources\ChatRoomResource;
use App\Models\ChatRoom;
use App\Models\ChatType;
use App\Repositories\ChatRoomRepository;
use App\Repositories\MessageRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class ChatRoomController extends Controller
{
    public function __construct(
        private ChatRoomRepository $chatRoomRepository,
        private MessageRepository $messageRepository
    ){}

    public function index(){
        $user_id = Auth::id();
        $chatrooms = ChatRoom::whereJsonContains('user', 'user_'.$user_id)
        ->with('lastMessage')
        ->simplePaginate(perPage: 15);
        return ChatRoomResource::collection($chatrooms);
    }

    public function store(Request $request){
        return $this->chatRoomRepository->createChatRoom($request);
    }

    public function show($id)
    {
        $user_id = Auth::id();
        try {
            $room = ChatRoom::whereJsonContains('user', 'user_'.$user_id)->findOrFail(id: $id);
            return new ChatRoomResource($room);
        } catch (\Exception $e) {
            return $this->sendResponse([
                'message' => 'Không tìm thấy phòng chat',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    public function notification($id){
        return $this->chatRoomRepository->notification($id);
    }

    public function send($id){
        return $this->messageRepository->send($id);
    }
}
