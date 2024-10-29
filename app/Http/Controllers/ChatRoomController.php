<?php

namespace App\Http\Controllers;

use App\Events\ChatRoom\RefreshUsers;
use App\Http\Resources\ChatRoomResource;
use App\Models\ChatRoom;
use App\Models\ChatType;
use App\Models\Message;
use App\Repositories\ChatRoomRepository;
use App\Repositories\MessageRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
    public function images($id){
        try {
            $user_id = Auth::id();
            ChatRoom::whereJsonContains('user', 'user_'.$user_id)->findOrFail($id);
            $messages = Message::where('chat_room_id',$id)
            ->whereJsonLength('files', '>', 0)
            ->orderBy('created_at','desc')
            ->paginate(6);
            return response()->json([
                'data' => $messages
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Không tìm thấy phòng chat',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function store(Request $request){
        return $this->chatRoomRepository->createChatRoom($request);
    }

    public function show($id,Request $request)
    {
        $user_id = Auth::id();
        try {
            $room = ChatRoom::whereJsonContains('user', 'user_'.$user_id)->findOrFail(id: $id);
            return $request->has('default') ? $room : new ChatRoomResource($room);
        } catch (\Exception $e) {
            return $this->sendResponse([
                'message' => 'Không tìm thấy phòng chat',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    public function update(Request $request, $id){
        $user_id = Auth::id();
        try {
            $type = $request->type.'Update';
            $room = ChatRoom::whereJsonContains('user', 'user_'.$user_id)->findOrFail(id: $id);
            DB::beginTransaction();
            $res = $this->chatRoomRepository->$type($request,$room);
            broadcast(new RefreshUsers($res));
            DB::commit();
            return $this->sendResponse([
                'message' => 'Thay đổi thông tin thành công',
                'res' => $res
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
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
