<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostResource;
use App\Models\Friend;
use App\Models\Post;
use App\Models\User;
use App\Notifications\CreatePost;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $post = Post::query();
        $user = Auth::user();

        $ids = $request->input("ids",'');
        $ids = explode(',', $ids);

        $post->with(
            'post_share',
            'post_share.user:id,name,avatar',
            'user:id,name,avatar',
            'user_emotion'
        );
        
        $friendIds = $this->getFriendIds($user);

        $post->whereNotIn('id',$ids);
        $post->whereIn('user_id',$friendIds);

        $post->where('type','post')
            ->whereIn('status',['public', 'friend'])
            ->where('is_active','1');
        $post->orderBy('created_at', 'desc');
        
        return $this->sendResponse($post->take(5)->get());
    }
    public function getFriendIds($user){
        $friendIds = Friend::where('user1', $user->id)->pluck('user2')
            ->merge(Friend::where('user2', $user->id)->pluck('user1'))
            ->unique()
            ->reject(fn($id) => $id == $user->id);
        return $friendIds;
    }
    public function store(Request $request)
    {
        $user_id = auth()->user()->id;
        $user = Auth::user();
        $post = new Post();
        if ($request->has('share')) {
            $share = $request->input('share');
            $postShare = Post::find($share);
            if ($postShare) {
                $postShare->share_count++;
                $postShare->save();
                $post->share_id = $postShare->id;
            }
        }
        $post->user_id = $user_id;
        $post->content = $request->input('content', '');
        $post->status = $request->input('status', 'public');
        $listFiles = (new FileController($request->file('files')))->posts();
        $post->data = $listFiles;
        $post->save();

        $ids = $this->getFriendIds($user);
        $users = User::whereIn('id', $ids)->get();
        foreach($users as $friend) {
            try {
                $notification = new CreatePost($post, $user);
                $friend->notify($notification);
            } catch (\Exception $e) {
                Log::error('Notification error: ' . $e->getMessage());
                continue;
            }
        }
        
        $post->load(
            'post_share',
            'post_share.user:id,name,avatar',
            'user:id,name,avatar',
            'user_emotion'
        );
        return $this->sendResponse([
            'data' => $post,
            'message' => 'Thêm mới bài viết thành công!'
        ], 200);
    }
    
    public function update(Request $request, $id)
    {
        try {
            $post = Post::findOrFail($id);

            $this->authorize('update', $post);

            $post->fill($request->only(['content', 'status']));

            if ($request->hasFile('files')) {
                $post->data = (new FileController($request->file('files')))->posts();
            }

            $post->save();

            return $this->sendResponse([
                'post' => $post,
                'message' => 'Cập nhật bài viết thành công!'
            ], 200);
        } catch (ModelNotFoundException $e) {
            return $this->sendResponse([
                'message' => 'Không tìm thấy bài viết!'
            ], 404);
        } catch (AuthorizationException $e) {
            return $this->sendResponse([
                'message' => 'Bạn không phải tác giả của bài viết này!'
            ], 403); 
        }
    }

    public function destroy(Post $post){
        try {
            $this->authorize('destroy', $post);
            $post->delete();
            return $this->sendResponse([
                'post' => $post,
                'message' => 'Đã xóa bài viết thành công!'
            ], 200);
        } catch (AuthorizationException $e) {
            return $this->sendResponse([
                'message' => 'Bạn không phải tác giả của bài viết này!'
            ], 403); 
        }
    }
}
