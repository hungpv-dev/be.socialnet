<?php

namespace App\Http\Controllers;

use App\Http\Resources\MessageResource;
use App\Models\ChatRoom;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        $user_id = Auth::id();
        try {
            $chat_room_id = $request->chat_room_id;
            $room = ChatRoom::whereJsonContains('user', $user_id)->findOrFail($chat_room_id);
            $messages = Message::where('chat_room_id', $room->id)
                ->with('user:id,name,avatar,is_online', 'replyTo')
                ->orderBy('created_at', 'desc')
                ->simplePaginate(perPage: 20)
                ->reverse();
            return MessageResource::collection($messages);
        } catch (\Exception $e) {
            return $this->sendResponse([
                'message' => 'Không tìm thấy phòng chat',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    public function store(Request $request)
    {
        $user_id = Auth::id();
        try {
            $chat_room_id = $request->chat_room_id;
            ChatRoom::whereJsonContains('user', $user_id)->findOrFail($chat_room_id);
            $fileDetails = []; 
            if($request->hasFile('files')){
                $files = $request->file('files');
                foreach($files as $file){
                    $mimeType = $file->getClientMimeType(); 
                    if (strpos($mimeType, 'image/') === 0) {
                        $filePath = $file->store('public/messages');
                        $fileDetails[] = url(str_replace('public/', 'storage/', $filePath));
                    }
                }
            }

            $content = $request->input('content','');
            $rep = $request->input('reply_to');
            $listMessage = [];
            if(!empty($fileDetails)){
                $data = [
                    'chat_room_id' => $chat_room_id,
                    'user_id' => $user_id,
                    'body' => '',
                    'is_seen' => [$user_id],
                    'flagged' => [],
                    'files' => $fileDetails,
                ];
                if($rep && $rep !== "null"){
                    $data['reply_to'] = $rep;
                }
                $message = Message::create($data);
                $listMessage[] = new MessageResource($message);
            }
            if($content && $content !== ''){
                $data = [
                    'chat_room_id' => $chat_room_id,
                    'user_id' => $user_id,
                    'body' => $content,
                    'is_seen' => [$user_id],
                    'flagged' => [],
                    'files' => [],
                ];
                if($rep && $rep !== "null"){
                    $data['reply_to'] = $rep;
                }
                $message = Message::create($data);
                $listMessage[] = new MessageResource($message);
            }
            return $this->sendResponse($listMessage, 200);
        } catch (\Exception $e) {
            return $this->sendResponse([
                'message' => 'Không tìm thấy phòng chat',
                'error' => $e->getMessage(),
            ], 404);
        }
    }
}
