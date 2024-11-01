<?php

namespace App\Observers;

use App\Events\ChatRoom\RefreshUsers;
use App\Models\ChatRoom;
class ChatRoomObserver
{
    public function created(ChatRoom $room): void
    {
        broadcast(new RefreshUsers($room));
    }
}
