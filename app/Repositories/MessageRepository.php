<?php

namespace App\Repositories;

use App\Models\ChatRoom;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MessageRepository
{
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
                    'is_seen' => DB::raw("JSON_REMOVE(is_seen, '$.{$userId}')")
                ]);

            // Update lastMessage only if necessary
            if ($chatRoom->lastMessage) {
                $isSeenArray = $chatRoom->lastMessage->is_seen ?? [];
                $check = in_array($userId, $isSeenArray);
                if (!$check) {
                    $isSeenArray[] = $userId;
                    $chatRoom->lastMessage->is_seen = $isSeenArray;
                    $chatRoom->lastMessage->save();
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
}
