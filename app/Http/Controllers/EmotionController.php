<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use App\Notifications\Post\EmotionNotification;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmotionController extends Controller
{

    public function index(Request $request){
        $id = $request->input('id');
        $type = $request->input('type');
        
        try {
            if ($type === 'post') {
                $model = Post::findOrFail($id);
            } else {
                $model = Comment::findOrFail($id);
            }
            
            $emotions = $model->emotions()->with('user:id,name,avatar')->paginate(10);
            return $this->sendResponse($emotions);
        } catch (ModelNotFoundException $e) {
            return $this->sendResponse(['message' => 'Không tìm thấy đối tượng!'], 404);
        }
    }
    public function store(Request $request){
        $id = $request->input("id");
        $type = $request->input("type");
        $user_id = Auth::id();
        
        try{
            if($type === 'post'){
                $model = Post::findOrFail($id);
            }else{
                $model = Comment::findOrFail($id);
                $id = $model->post_id;
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
                $typeN = 2;
                $message = 'đã bày bỏ cảm xúc về bình luận của bạn!';
                if($type === 'post'){
                    $typeN = 1;
                    if($model->type == 'avatar') {
                        $message = 'đã bày tỏ cảm xúc về ảnh đại diện của bạn!';
                    } else if($model->type == 'background') {
                        $message = 'đã bày tỏ cảm xúc về ảnh bìa của bạn!';
                    } else {
                        $message = 'đã bày tỏ cảm xúc về bài viết của bạn!';
                    }
                    $model->emoji_count++;
                }
                $model->emotions()->create([
                    'user_id' => $user_id,
                    'created_at' => now(),
                    'emoji' => $request->input('emoji')
                ]);
                if($model->user->id != $user_id){
                    $model->user->notify(new EmotionNotification($id, $message, $typeN));
                }
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
