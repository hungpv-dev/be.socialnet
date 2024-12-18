<?php

namespace App\Http\Controllers;


use App\Jobs\LogActivityJob;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Notifications\Comment\CommentPostNotification;
use App\Notifications\Comment\RepCommentNotification;
use Illuminate\Http\Request;
use App\Events\CommentEvent\CommentNotification;

class CommentControler extends Controller
{

    public function index() {}
    public function store(Request $request)
    {
        //Báo lỗi nếu không truyền lên nội dung và id bài post
        //Báo lỗi nếu không tồn tại bình luận của parent_id
        if (!$request->post_id || ($request->parent_id && !Comment::find($request->parent_id))) {
            return response()->json(['message' => 'Đã xảy ra lỗi'], 400);
        }
        $post = Post::find($request->post_id);
        if (!$post) {
            return response()->json(['message' => 'Bài viết không tồn tại'], 404);
        }

        $data = [];
        if ($request->hasFile('files')) {
            $file = $request->file('files');
            if ($file->getSize() > 2048000) { // 2MB
                return response()->json(['message' => 'Kích thước tệp quá lớn'], 400);
            }
            if (!$file->isValid()) {
                return response()->json(['message' => 'File không hợp lệ'], 400);
            }

            $mimeType = $file->getMimeType();
            if (strpos($mimeType, 'image/') === 0) {
                $path = $file->store('comments/images', 'public');
                $data['image'] = custom_url('storage/' . $path);
            } else if (strpos($mimeType, 'video/') === 0) {
                $path = $file->store('comments/videos', 'public');
                $data['video'] = custom_url('storage/' . $path);
            } else {
                return response()->json(['message' => 'File phải là hình ảnh hoặc video'], 400);
            }
        }
        $data['text'] = $request->input('text', '');
        $comment = Comment::create([
            'user_id' => $request->user()->id,
            'post_id' => $request->post_id,
            'content' => $data,
            'parent_id' => $request->parent_id ?? null
        ]);
        $post = Post::find($comment->post_id);
        $post->comment_count++;
        $post->save();
        if ($comment->parent_id) {
            $parentComment = Comment::find($comment->parent_id)->user_id;
            $parentCommentUser = User::find($parentComment);
            if ($parentCommentUser && $parentComment !== auth()->user()->id) {
                $parentCommentUser->notify(new RepCommentNotification($post->id, $comment->id));
            }
        } else {
            $postUser = User::find($post->user_id);
            if ($postUser && $post->user_id !== auth()->user()->id) {
                $postUser->notify(new CommentPostNotification($post->id, $comment->id));
            }
        }

        $user = $request->user();
        $request->parent_id ? LogActivityJob::dispatch('comment', $user, $post, [
            'user' => $user->name,
            'avatar' => $user->avatar,
            'created_at' => now(),
        ], "đã bình luận một bài viết.") : LogActivityJob::dispatch('comment', $user, $post, [
            'user' => $user->name,
            'avatar' => $user->avatar,
            'created_at' => now(),
        ], "đã trả lời một bình luận.");;

        return response()->json([
            'data' => $comment->load(['user:id,name,avatar', 'parent:id,user_id', 'parent.user']),
            'message' => 'Bình luận thành công!'
        ], 201);
    }
    public function show(string $id)
    {
        //
    }
    public function update(Request $request, $id)
    {
        // Kiểm tra xem bình luận có tồn tại không
        $comment = Comment::find($id);
        if (!$comment) {
            return response()->json(['message' => 'Bình luận không tồn tại'], 404);
        }

        // Kiểm tra xem người dùng có quyền chỉnh sửa bình luận (chỉ người tạo bình luận mới có thể chỉnh sửa)
        if ($comment->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Bạn không có quyền chỉnh sửa bình luận này'], 403);
        }

        // Kiểm tra nếu có tệp đính kèm và xử lý
        $data = json_decode($comment->content, true);  // Lấy dữ liệu cũ
        if ($request->hasFile('content')) {
            $file = $request->file('content');

            // Kiểm tra kích thước và tính hợp lệ của file
            if ($file->getSize() > 2048000) { // 2MB
                return response()->json(['message' => 'Kích thước tệp quá lớn'], 400);
            }
            if (!$file->isValid()) {
                return response()->json(['message' => 'File không hợp lệ'], 400);
            }

            $mimeType = $file->getMimeType();
            if (strpos($mimeType, 'image/') === 0) {
                // Xóa file cũ nếu có
                if (isset($data['image'])) {
                    $this->deleteFile($data['image']);
                }
                $path = $file->store('comments/images', 'public');
                $data['image'] = custom_url('storage/' . $path);
            } else if (strpos($mimeType, 'video/') === 0) {
                // Xóa file cũ nếu có
                if (isset($data['video'])) {
                    $this->deleteFile($data['video']);
                }
                $path = $file->store('comments/videos', 'public');
                $data['video'] = custom_url('storage/' . $path);
            } else {
                return response()->json(['message' => 'File phải là hình ảnh hoặc video'], 400);
            }
        }

        // Cập nhật nội dung văn bản (nếu có)
        if ($request->content) {
            $data['text'] = $request->content;
        }

        // Cập nhật bình luận
        $comment->content = json_encode($data);
        $comment->save();

        return response()->json(['message' => 'Bình luận đã được cập nhật thành công'], 200);
    }

    public function destroy(string $id)
    {
        $comment = Comment::find($id);
        if (! $comment) {
            return response()->json(['message' => 'Không tìm thấy bình luận!'], 404);
        }
        $post = Post::findOrFail($comment->post_id);
        if ($comment->user_id != auth()->user()->id) {
            return response()->json(['message' => 'Bạn không có quyền xóa bình luận này!'], 400);
        }

        // Xóa tất cả các bình luận con
        $this->deleteChildrenComments($comment->id);

        $comment->delete();
        $post->comment_count = $post->comments()->count();
        $post->save();

        return response()->json(['message' => 'Xóa bình luận thành công'], 200);
    }
    public function deleteChildrenComments($parentId)
    {
        $childrenComments = Comment::where('parent_id', $parentId)->get();

        foreach ($childrenComments as $child) {
            $this->deleteChildrenComments($child->id);
            $child->delete();
        }
    }
    public function getComments(Request $request)
    {
        $type = $request->type;
        if ($type === 'post') {
            $post = Post::find($request->id);
            if (!$post) {
                return response()->json(['message' => 'Không tìm thấy bài viết!'], 404);
            }
            $commentsQuery = Comment::where('post_id', $request->id)
                ->whereNull('parent_id');
        } elseif ($type === 'comment') {
            $commentParent = Comment::find($request->id);
            if (!$commentParent) {
                return response()->json(['message' => 'Không tìm thấy bình luận!'], 404);
            }
            $commentsQuery = Comment::with(['parent:id,user_id', 'parent.user'])->where('parent_id', $request->id);
        } else {
            return response()->json(['message' => 'Loại yêu cầu không hợp lệ!'], 400);
        }

        $comments = $commentsQuery
            ->with(['user:id,name,avatar', 'user_emotion'])
            ->withCount('emotions')
            ->select(['id', 'content', 'parent_id', 'user_id', 'created_at'])
            ->selectRaw('(SELECT COUNT(*) FROM comments AS child_comments WHERE child_comments.parent_id = comments.id) AS countChildren')
            ->orderBy('created_at', 'desc')
            ->paginate(5);

        return response()->json($comments);
    }
}
