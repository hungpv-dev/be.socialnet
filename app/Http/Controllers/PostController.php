<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use App\Models\Friend;
use Illuminate\Http\Request;
use App\Notifications\CreatePost;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PostController extends Controller
{
    public $friendController;
    public function __construct()
    {
        $this->friendController = new FriendController;
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

        $post
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
        $userShare = 0;
        $postShare = null;
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
        if($postShare && $postShare->user->id != $user_id){
            $userShare = $postShare->user->id;
            $postShare->user->notify(new CreatePost($post->id,'đã chia sẻ bài viết của bạn'));
        }
        $ids = $this->getFriendIds($user);
        $users = User::whereIn('id', $ids)->get();
        foreach ($users as $friend) {
            try {
                if($userShare == $friend->id){
                    continue;
                }
                $notification = new CreatePost($post->id, 'đã thêm bài viết mới!');
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
                // ->where('type', 'post')
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

            // Cập nhật nội dung và trạng thái
            $post->content = $request->input('content', $post->content);
            $post->status = $request->input('status', $post->status);

            // Xử lý files
            if ($request->hasFile('files')) {
                $newFiles = (new FileController($request->file('files')))->posts();
                // Nếu có keep_files, kết hợp với files mới
                if ($request->has('keep_files')) {
                    $keepFiles = json_decode($request->keep_files, true);
                    if (is_array($keepFiles) && isset($keepFiles['image']) && isset($keepFiles['video'])) {
                        $currentFiles = $post->data;
                        $keptFiles = [
                            'image' => array_filter($currentFiles['image'] ?? [], function($file) use ($keepFiles) {
                                return in_array($file, $keepFiles['image']);
                            }),
                            'video' => array_filter($currentFiles['video'] ?? [], function($file) use ($keepFiles) {
                                return in_array($file, $keepFiles['video']);
                            })
                        ];
                        
                        // Kết hợp files giữ lại với files mới
                        $post->data = [
                            'image' => array_merge($keptFiles['image'], $newFiles['image'] ?? []),
                            'video' => array_merge($keptFiles['video'], $newFiles['video'] ?? [])
                        ];
                    } else {
                        $post->data = $newFiles;
                    }
                } else {
                    $post->data = $newFiles;
                }
            } else if ($request->has('keep_files')) {
                // Nếu chỉ có keep_files mà không có files mới
                $keepFiles = json_decode($request->keep_files, true);
                if (is_array($keepFiles) && isset($keepFiles['image']) && isset($keepFiles['video'])) {
                    $currentFiles = $post->data;
                    $post->data = $keepFiles;
                }
            }

            $post->save();

            return $this->sendResponse([
                'data' => $post->load('post_share',
                'post_share.user:id,name,avatar',
                'user:id,name,avatar',
                'user_emotion'),
                'add' => $request->all(),
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
    public function getPostByUser($id,Request $request)
    {
        $posts = Post::with(
            'post_share',
            'post_share.user:id,name,avatar',
            'user:id,name,avatar',
            'user_emotion'
        )
            ->where('user_id', $id)
            // ->where('type', 'post')
            ->where('is_active', '1');

        if (auth()->user()->id == $id)
            $posts = $posts->whereIn('status', ['private', 'public', 'friend']);
        else if (auth()->user()->id != $id && $this->friendController->checkFriendStatus($id) == ["friend", "chat"])
            $posts = $posts->whereIn('status', ['public', 'friend']);
        else
            $posts = $posts->where('status', 'public');

        $limit = 5; // Số lượng bài viết mỗi lần tải
        $offset = $request->input('offset', 0); // Vị trí bắt đầu lấy dữ liệu
        
        $posts = $posts->orderBy('created_at', 'desc')
            ->skip($offset)
            ->take($limit)
            ->get();

        $hasMore = $posts->count() == $limit; // Kiểm tra còn dữ liệu không
        
        return response()->json([
            'posts' => $posts,
            'hasMore' => $hasMore,
            'nextOffset' => $offset + $limit
        ]);
    }
}
