<?php

namespace App\Http\Controllers;

use App\Events\ChatRoom\RefreshUsers;
use App\Http\Resources\ChatRoomResource;
use App\Models\ChatRoom;
use App\Models\ChatType;
use App\Models\Message;
use App\Models\User;
use App\Repositories\ChatRoomRepository;
use App\Repositories\MessageRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChatRoomController extends Controller
{
    public function __construct(
        private ChatRoomRepository $chatRoomRepository,
        private MessageRepository $messageRepository
    ){}

    public function index(Request $request){
        $index = $request->input('index', 0);
        $user_id = Auth::id();
        $chatrooms = ChatRoom::from('chat_rooms','c')
        ->whereJsonContains('user', 'user_'.$user_id)
        ->select('c.*', DB::raw('MAX(m.created_at) as latest_message'))
        ->join('messages as m', function($join) use ( $user_id) {
            $join->on('m.chat_room_id', '=', 'c.id')
                 ->whereBetween('m.created_at',[
                    DB::raw("c.last_active->>'$.user_".$user_id."'"),
                    DB::raw("CASE WHEN c.last_remove->>'$.user_" . $user_id . "' = 'null' THEN '" . now() . "' ELSE c.last_remove->>'$.user_" . $user_id . "' END")
                 ]);
        })
        ->groupBy('c.id')
        ->orderBy('latest_message','desc')
        ->skip($index)
        ->take(15)
        ->get();
        return ChatRoomResource::collection($chatrooms);
    }
    public function search(Request $request){
        $name = $request->input('name', '');
        $user_id = Auth::id();
        $chatrooms = ChatRoom::from('chat_rooms','c')
        ->whereJsonContains('user', 'user_'.$user_id)
        ->select('c.*', DB::raw('MAX(m.created_at) as latest_message'))
        ->join('messages as m', function($join) use ( $user_id) {
            $join->on('m.chat_room_id', '=', 'c.id')
                 ->whereBetween('m.created_at',[
                    DB::raw("c.last_active->>'$.user_".$user_id."'"),
                    DB::raw("CASE WHEN c.last_remove->>'$.user_" . $user_id . "' = 'null' THEN '" . now() . "' ELSE c.last_remove->>'$.user_" . $user_id . "' END")
                 ]);
        })
        ->groupBy('c.id')
        ->orderBy('latest_message','desc')
        ->take(15)
        ->get();
        return ChatRoomResource::collection($chatrooms);
    }
    public function images($id){
        try {
            $user_id = Auth::id();
            $room = ChatRoom::whereJsonContains('user', 'user_'.$user_id)->findOrFail($id);
            $lastActive = $room->last_active['user_'.$user_id];
            $lastRemove = $room->last_remove['user_'.$user_id] ?? now();

            $messages = Message::where('chat_room_id',$id)
            ->whereJsonLength('files', '>', 0)
            ->orderBy('created_at','desc')
            ->whereBetween('created_at',[$lastActive,$lastRemove])
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

    public function outGroup($id,Request $request){
        $user_id = Auth::id();
        try {
            $room = ChatRoom::whereJsonContains('user', 'user_'.$user_id)->findOrFail($id);
            DB::beginTransaction();
            $content = 'đã rời khỏi nhóm';
            if($request->has('id')){
                $content = 'đã bị '.$request->user()->name.' kích khỏi nhóm';
                $user_id = $request->id;
            }
            $user = User::findOrFail($user_id);
            $content = $user->name. ' ' .$content;
            // Thêm user_id vào mảng outs
            $outs = $room->outs ?? [];
            $removes = $room->last_remove ?? [];
            $removes['user_'.$user_id] = now()->format('Y-m-d H:i:s');
            if (!in_array('user_'.$user_id, $outs)) {
                $outs[] = 'user_'.$user_id;
            }

            $room->update(['outs' => $outs,'last_remove'=> $removes]);
            createNofiMessage($room->id,$content);
            DB::commit();
            broadcast(new RefreshUsers($room));
            return $this->sendResponse([
                'message' => 'Rời nhóm thành công'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendResponse([
                'message' => 'Không thể rời nhóm',
                'error' => $e->getMessage()
            ], 404);
        }
    }
}
