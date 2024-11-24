<?php

namespace App\Http\Controllers;

use App\Events\ChatRoom\PushMessage;
use App\Events\ChatRoom\SendIcon;
use App\Http\Resources\MessageResource;
use App\Models\ChatRoom;
use App\Models\Message;
use App\Models\User;
use App\Repositories\MessageRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    public function __construct(private MessageRepository $messageRepository){}
    public function index(Request $request)
    {
        return $this->messageRepository->getMessages($request);
    }

    public function store(Request $request)
    {   
        return $this->messageRepository->createMessage($request);
    }

    public function destroy($id,Request $request)
    {
        return $this->messageRepository->deleteMessage($id,$request);
    }

    public function sendIcon(Request $request,$id){
        try{
            $user_id = Auth::id();
            $message = Message::findOrFail($id);
            $userEmotions = $message->emotions()->where("user_id",$user_id)->first();
            if($userEmotions) {
                if($userEmotions->emoji == $request->input('emojis')) {
                    $userEmotions->delete();
                }else{
                    $userEmotions->emoji = $request->input('emojis');
                    $userEmotions->created_at = now();
                    $userEmotions->save();
                }
            }else{
                $message->emotions()->create([
                    'user_id' => $user_id,
                    'created_at' => now(),
                    'emoji' => $request->input('emojis')
                ]);
            }
            broadcast(new SendIcon($message->chat_room_id,message: $message));
            return $this->sendResponse([
                'message' => 'Thêm icon thành công!'
            ],201);
        }catch(ModelNotFoundException $e){
            return $this->sendResponse([
                'message' => 'Không tìm thấy tin nhắn'
            ],404);
        }
    }
}
