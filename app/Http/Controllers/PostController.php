<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index()
    {
        $post = Post::query();

        $post->with(
            'post_share',
            'post_share.user:id,name,avatar',
            'user:id,name,avatar'
        );
        $post->where('type','post');
        $post->orderBy('created_at', 'desc');
        
        return $this->sendResponse(PostResource::collection($post->simplePaginate(5)));
    }
    public function store(Request $request)
    {
        $user_id = auth()->user()->id;
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
