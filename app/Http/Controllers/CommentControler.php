<?php

namespace App\Http\Controllers;

use App\Events\CommentEvent\CommentNotification;
use App\Jobs\CommentJob\ProcessCommentFileUpload;
use App\Jobs\CommentJob\SendCommentNotification;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CommentControler extends Controller
{

    public function index() {}
    public function store(Request $request)
    {
        //Báo lỗi nếu không truyền lên nội dung và id bài post
        //Báo lỗi nếu không tồn tại bình luận của parent_id
        if (!$request->content || !$request->post_id || ($request->parent_id && !Comment::find($request->parent_id))) {
            return response()->json(['message' => 'Đã xảy ra lỗi'], 400);
        }
        $post = Post::find($request->post_id);
        if (!$post) {
            return response()->json(['message' => 'Bài viết không tồn tại'], 404);
        }

        $data = [];
        if ($request->hasFile('content')) {
            $file = $request->file('content');
            if ($file->getSize() > 2048000) { // 2MB
                return response()->json(['message' => 'Kích thước tệp quá lớn'], 400);
            }
            if (!$file->isValid()) {
                return response()->json(['message' => 'File không hợp lệ'], 400);
            }

            $mimeType = $file->getMimeType();
            if (strpos($mimeType, 'image/') === 0) {
                $path = $file->store('comments/images', 'public');
                $data['image'] = url('storage/' . $path);
            } else if (strpos($mimeType, 'video/') === 0) {
                $path = $file->store('comments/videos', 'public');
                $data['video'] = url('storage/' . $path);
            } else {
                return response()->json(['message' => 'File phải là hình ảnh hoặc video'], 400);
            }
        } else {
            $data['text'] = $request->content;
        }
        $comment = Comment::create([
            'user_id' => $request->user()->id,
            'post_id' => $request->post_id,
            'content' => json_encode($data),
            'parent_id' => $request->parent_id ?? null
        ]);
        $post->increment('comment_count');
        // Gửi thông báo
        if ($request->parent_id) {
            $parentComment = Comment::find($request->parent_id);
            if ($parentComment) {
                $to_user = $parentComment->user_id;
                $message = " đã phản hồi bình luận của bạn.";
            } else {
                return response()->json(['message' => 'Bình luận cha không tồn tại'], 404);
            }
        } else {
            $to_user = $post->user_id;
            $message = " đã bình luận về bài viết của bạn.";
        }

        // Gửi thông báo nếu người dùng không phải là chính mình
        if ($to_user != auth()->user()->id) {
            broadcast(new CommentNotification($to_user, $message, $comment->id));
        }

        return response()->json(['message' => 'Bình luận thành công!'], 201);
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
                $data['image'] = url('storage/' . $path);
            } else if (strpos($mimeType, 'video/') === 0) {
                // Xóa file cũ nếu có
                if (isset($data['video'])) {
                    $this->deleteFile($data['video']);
                }
                $path = $file->store('comments/videos', 'public');
                $data['video'] = url('storage/' . $path);
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
        if ($comment->user_id != auth()->user()->id || Post::find($comment->post_id)->user_id != auth()->user()->id) {
            return response()->json(['message' => 'Bạn không có quyền xóa bình luận này!'], 400);
        }

        // Xóa tất cả các bình luận con
        $this->deleteChildrenComments($comment->id);

        $comment->delete();

        return response()->json(['message' => 'Xóa bình luận thành công'], 200);
    }
    private function deleteChildrenComments($parentId)
    {
        $childrenComments = Comment::where('parent_id', $parentId)->get();

        foreach ($childrenComments as $child) {
            $this->deleteChildrenComments($child->id);
            $child->delete();
        }
    }
}
