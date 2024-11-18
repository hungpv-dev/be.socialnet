<?php

namespace App\Http\Controllers;

use App\Models\Comment;
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
                $model = Post::findOrFail($id);
            }else{
                $model = Comment::findOrFail($id);
            }
            $userEmotions = $model->emotions()->where("user_id",$user_id)->first();
            if($userEmotions) {
                if($userEmotions->emoji == $request->input('emoji')) {
                    $userEmotions->delete();
                    if($type === 'post'){
                        $model->emoji_count--;
                    }
                }else{
                    $userEmotions->emoji = $request->input('emoji');
                    $userEmotions->created_at = now();
                    $userEmotions->save();
                }
            }else{
                if($type === 'post'){
                    $model->emoji_count++;
                }
                $model->emotions()->create([
                    'user_id' => $user_id,
                    'created_at' => now(),
                    'emoji' => $request->input('emoji')
                ]);
            }
            $model->save();
            // broadcast(new SendIcon($message->chat_room_id,message: $message));
            return $this->sendResponse([
                'post' => $model,
                'message' => 'Thêm icon thành công!'
            ],200);
        }catch(ModelNotFoundException $e){
            return $this->sendResponse([
                'message' => 'Không tìm bản ghi'
            ],404);
        }
    }
}
