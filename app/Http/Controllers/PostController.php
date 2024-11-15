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
    public $friendController;
    public function __construct(FriendController $friendController)
    {
        $this->friendController = $friendController;
    }
    public function index(Request $request)
    {
        $post = Post::query();
        $user = Auth::user();

        $ids = $request->input("ids", '');
        $ids = explode(',', $ids);

        $post->with(
            'post_share',
            'post_share.user:id,name,avatar',
            'user:id,name,avatar',
            'user_emotion'
        );

        $friendIds = $this->getFriendIds($user);

        $post->whereNotIn('id', $ids);
        $post->whereIn('user_id', $friendIds);

        $post->where('type', 'post')
            ->whereIn('status', ['public', 'friend'])
            ->where('is_active', '1');
        $post->orderBy('created_at', 'desc');

        return $this->sendResponse($post->take(5)->get());
    }
    public function getFriendIds($user)
    {
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
        foreach ($users as $friend) {
            try {
                $notification = new CreatePost($post, $user);
                $friend->notify($notification);
            } catch (\Exception $e) {
                Log::error('Notification error: ' . $e->getMessage());
                continue;
            }
        }


        return $this->sendResponse([
            'data' => $post,
            'message' => 'Thêm mới bài viết thành công!'
        ], 200);
    }
    public function show($id)
    {
        try {
            $post = Post::with(
                'post_share',
                'post_share.user:id,name,avatar',
                'user:id,name,avatar',
                'user_emotion'
            )
                ->where('id', $id)
                ->where('type', 'post')
                ->firstOrFail();

            if (auth()->user()->id == $post->user_id) {
                $allowedStatuses = ['private', 'public', 'friend'];
            } elseif ($this->friendController->checkFriendStatus($post->user_id) == ["friend", "chat"]) {
                $allowedStatuses = ['public', 'friend'];
            } else {
                $allowedStatuses = ['public'];
            }

            if (!in_array($post->status, $allowedStatuses)) {
                return response()->json(['message' => 'Bạn không có quyền truy cập bài viết này!'], 403);
            }

            return response()->json($post);
        } catch (ModelNotFoundException $th) {
            return response()->json(['message' => 'Không tìm thấy bài viết!'], 404);
        }
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

    public function destroy(Post $post)
    {
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
    public function getPostByUser($id)
    {
        $posts = Post::with(
            'post_share',
            'post_share.user:id,name,avatar',
            'user:id,name,avatar',
            'user_emotion'
        )
            ->where('user_id', $id)
            ->where('type', 'post')
            ->where('is_active', '1');

        if (auth()->user()->id == $id)
            $posts = $posts->whereIn('status', ['private', 'public', 'friend']);
        else if (auth()->user()->id != $id && $this->friendController->checkFriendStatus($id) == ["friend", "chat"])
            $posts = $posts->whereIn('status', ['public', 'friend']);
        else
            $posts = $posts->where('status', 'public');

        $posts = $posts->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return response()->json($posts);
    }
}
