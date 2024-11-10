<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmotionController extends Controller
{
    public function store(Request $request){
        $id = $request->input("id");
        $type = $request->input("type");
        $user_id = Auth::id();
        try{
            if($type === 'post'){
                $post = Post::findOrFail($id);
                $userEmotions = $post->emotions()->where("user_id",$user_id)->first();
                if($userEmotions) {
                    if($userEmotions->emoji == $request->input('emoji')) {
                        $userEmotions->delete();
                        $post->emoji_count--;
                    }else{
                        $userEmotions->emoji = $request->input('emoji');
                        $userEmotions->created_at = now();
                        $userEmotions->save();
                    }
                }else{
                    $post->emoji_count++;
                    $post->emotions()->create([
                        'user_id' => $user_id,
                        'created_at' => now(),
                        'emoji' => $request->input('emoji')
                    ]);
                }
                $post->save();
            }
            // broadcast(new SendIcon($message->chat_room_id,message: $message));
            return $this->sendResponse([
                'post' => $post,
                'message' => 'Thêm icon thành công!'
            ],200);
        }catch(ModelNotFoundException $e){
            return $this->sendResponse([
                'message' => 'Không tìm bản ghi'
            ],404);
        }
    }
}
