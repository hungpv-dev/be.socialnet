<?php

namespace App\Repositories;

use App\Events\ChatRoom\DestroyMesssage;
use App\Events\ChatRoom\PushMessage;
use App\Events\ChatRoom\RefreshUsers;
use App\Events\ChatRoom\SendMessage;
use App\Http\Resources\ChatRoomResource;
use App\Http\Resources\MessageResource;
use App\Models\ChatRoom;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MessageRepository
{
    public function getMessages($request)
    {
        $index = $request->input('index', 0);
        $user_id = Auth::id();
        try {
            $chat_room_id = $request->chat_room_id;
            $room = ChatRoom::whereJsonContains('user', 'user_' . $user_id)->findOrFail($chat_room_id);
            $lastActive = $room->last_active['user_'.$user_id];
            $lastRemove = $room->last_remove['user_'.$user_id] ?? now()->format('Y-m-d H:i:s');
            $messages = Message::where('chat_room_id', $room->id)
                ->with('user:id,name,avatar,is_online', 'replyTo','emotions:id,user_id,emotionable_id,emotionable_type,emoji') 
                ->orderBy('id', 'desc')
                ->whereBetween('created_at',[$lastActive,$lastRemove])
                ->skip($index)
                ->take(20)
                ->get();
            return MessageResource::collection($messages);
        } catch (\Exception $e) {
            return response([
                'message' => 'Không tìm thấy phòng chat',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    public function createMessage($request)
    {
        $user_id = Auth::id();
        try {
            $chat_room_id = $request->chat_room_id;
            $room = ChatRoom::whereJsonContains('user', 'user_' . $user_id)->findOrFail($chat_room_id);
            $fileDetails = [];
            if ($request->hasFile('files')) {
                $files = $request->file('files');
                foreach ($files as $file) {
                    $mimeType = $file->getClientMimeType();
                    if (strpos($mimeType, 'image/') === 0) {
                        $filePath = $file->store('public/messages');
                        $fileDetails[] = url(str_replace('public/', 'storage/', $filePath));
                    }
                }
            }

            $content = $request->input('content', '');
            $rep = $request->input('reply_to');
            $listMessage = [];
            if (!empty($fileDetails)) {
                $data = [
                    'chat_room_id' => $room->id,
                    'user_id' => $user_id,
                    'body' => '',
                    'created_at' => now()->format('Y-m-d H:i:s'),
                    'is_seen' => ['user_' . $user_id],
                    'flagged' => [],
                    'files' => $fileDetails,
                ];
                if ($rep && $rep !== "null") {
                    $data['reply_to'] = $rep;
                }
                $message = Message::create($data);
                $listMessage[] = new MessageResource($message);
            }
            if ($content && $content !== '') {
                $data = [
                    'chat_room_id' => $room->id,
                    'user_id' => $user_id,
                    'body' => $content,
                    'created_at' => now()->format('Y-m-d H:i:s'),
                    'is_seen' => ['user_' . $user_id],
                    'flagged' => [],
                    'files' => [],
                ];
                if ($rep && $rep !== "null") {
                    $data['reply_to'] = $rep;
                }
                $message = Message::create($data);
                $listMessage[] = new MessageResource($message);
            }
            broadcast(new PushMessage($chat_room_id, $listMessage));
            broadcast(new RefreshUsers($room));
            return response($listMessage, 200);
        } catch (\Exception $e) {
            return response([
                'message' => 'Không tìm thấy phòng chat',
                'error' => $e->getMessage(),
            ], 404);
        }
    }
    public function send($id)
    {
        try {
            $userId = 'user_' . Auth::id();
            $chatRoom = ChatRoom::with('lastMessage')
                ->whereJsonContains('user', $userId)
                ->findOrFail($id);

                DB::table('messages')
            ->where('chat_room_id', $chatRoom->id)
            ->whereJsonContains('is_seen', $userId)
            ->update([
                'is_seen' => DB::raw("JSON_REMOVE(is_seen, JSON_UNQUOTE(JSON_SEARCH(is_seen, 'one', '$userId')))")
            ]);
            $lastMessage = Message::where('chat_room_id', $chatRoom->id)
                ->orderBy('id', 'desc')
                ->first();
            if ($lastMessage) {
                $isSeenArray = $lastMessage->is_seen;
                $check = in_array($userId, $isSeenArray);
                if (!$check) {
                    $isSeenArray[] = $userId;
                    $lastMessage->is_seen = $isSeenArray;
                    $lastMessage->save();
                    broadcast(new SendMessage($chatRoom->id,Auth::id(), new MessageResource($lastMessage)))->toOthers();
                }
            }
        } catch (\Exception $e) {
            return response([
                'message' => 'Không tìm thấy phòng chat',
                'error' => $e->getMessage(),
            ], 404);
        }
        return response([
            'message' => 'Xem tin nhắn thành công'
        ], 200);
    }

    public function deleteMessage($id,$request)
    {
        $all = $request->input('all',false);
        try {
            // Xóa cá nhân
            $message = Message::findOrFail($id);
            $chatRoom = ChatRoom::findOrFail($message->chat_room_id);
            if($all){
                $message->flagged = $chatRoom->user;
                $message->save();
                broadcast(new DestroyMesssage($chatRoom->id,$message))->toOthers();
            }else{
                $message->flagged = array_merge($message->flagged, ['user_' . Auth::id()]);
                $message->save();
            }
            return response([
                'data' => new MessageResource($message),
                'message' => 'Xóa tin nhắn thành công'
            ], 200);
        } catch (\Exception $e) {
            return response([
                'message' => 'Không tìm thấy tin nhắn',
                'error' => $e->getMessage(),
            ], 404);
        }
    }
}
