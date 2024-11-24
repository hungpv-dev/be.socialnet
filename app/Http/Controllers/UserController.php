<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Friend;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    protected $friendController;

    public function __construct(FriendController $friendController)
    {
        $this->friendController = $friendController;
    }
    //Lấy thông tin trang cá nhân người dùng
    public function getProfile(Request $request)
    {
        if (!$request->id)
            return $this->sendResponse(['message' => 'Đã có lỗi xảy ra!'], 404);

        $user = User::select(['id', 'name', 'avatar', 'cover_avatar', 'follower', 'friend_counts', 'address', 'hometown', 'gender', 'birthday', 'relationship'])
            ->where('id', $request->id)
            ->where('is_active', 0)
            ->whereNull('deleted_at')
            ->first();

        if (!$user) {
            return $this->sendResponse(['message' => 'Không tìm thấy tài khoản!'], 404);
        }

        $user->friend_commons = $this->friendController->findCommonFriends(auth()->user()->id, $user->id);
        $user->button = $this->friendController->checkFriendStatus($user->id);

        return $this->sendResponse($user);
    }
    public function updateProfile(Request $request)
    {
        if ($request->isMethod('PUT')) {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'address' => 'nullable|string|max:255',
                'hometown' => 'nullable|string|max:255',
                'phone' => 'nullable|max:255',
                'gender' => 'nullable|in:male,female,other',
                'birthday' => 'nullable|date',
                'relationship' => 'nullable|in:single,married,divorced,widowed',
            ]);

            if ($validator->fails()) {
                return $this->sendResponse(['errors' => $validator->errors()], 422);
            }

            $user = $request->user();
            $user->update($validator->validated());

            return $this->sendResponse('Cập nhật thông tin cá nhân thành công');
        }

        return $this->sendResponse(['error' => 'Phương thức không được hỗ trợ'], 405);
    }
    //Tìm kiếm người dùng
    public function findUser(Request $request)
    {
        if (!$request->name) {
            return $this->sendResponse(['message' => 'Vui lòng điền tên tài khoản bạn muốn tìm kiếm!'], 404);
        }
        $per_page = $request->input('per_page', 10);
        $currentUserId = auth()->user()->id;

        $query = User::where('name', 'LIKE', "%" . $request->name . "%")
            ->where('is_active', 0)
            ->whereNull('deleted_at')
            ->select('id', 'name', 'address', 'hometown', 'gender', 'relationship', 'follower', 'friend_counts', 'is_online')
            ->where('id', '!=', $currentUserId)
            ->whereNotIn('id', function ($subQuery) use ($currentUserId) {
                $subQuery->select('user_block')
                    ->from('blocks')
                    ->where('user_is_blocked', $currentUserId);
            })
            ->whereNotIn('id', function ($subQuery) use ($currentUserId) {
                $subQuery->select('user_is_blocked')
                    ->from('blocks')
                    ->where('user_block', $currentUserId);
            });

        if ($request->address) {
            $query->where('address', 'LIKE', '%' . $request->address . '%');
        }
        if ($request->hometown) {
            $query->where('hometown', 'LIKE', '%' . $request->hometown . '%');
        }
        if ($request->gender) {
            $query->where('gender', $request->gender);
        }
        if ($request->relationship) {
            $query->where('relationship', $request->relationship);
        }

        $data = $query->paginate($per_page);


        if ($request->user()) {
            foreach ($data->items() as $friend) {
                $friend->friend_commons = $this->friendController->findCommonFriends($request->user()->id, $friend->id);
                $friend->button = $this->friendController->checkFriendStatus($friend->id);
            }
        }

        return $this->sendResponse($data);
    }
    //Làm việc với avatar
    public function updateAvatar(Request $request)
    {
        $user_id = auth()->user()->id;
        $post = new Post();
        $post->user_id = $user_id;
        $post->share_id = NULL;
        $post->content = $request->input('content') ?? '';
        $post->status = $request->input('status', 'public');
        $post->type = 'avatar';
        if (!$request->hasFile('avatar')) {
            return $this->sendResponse(['message' => 'Vui lòng tải lên ảnh đại diện!'], 400);
        } else if (!$request->file('avatar')->isValid() || strpos($request->file('avatar')->getMimeType(), 'image/') !== 0) {
            return $this->sendResponse(['message' => 'Định dạng không hợp lệ!'], 400);
        }
        $path = $request->file('avatar')->store('posts/image', 'public');
        $fullPath = url('storage/' . $path);
        $post->data = ['image' => [$fullPath]];
        $post->save();
        $request->user()->avatar = $fullPath;
        $request->user()->save();

        return $this->sendResponse([
            'message' => 'Cập nhật ảnh đại diện thành công!'
        ], 200);
    }
    public function listAvatar()
    {
        $data = Post::where('user_id', auth()->user()->id)
            ->where('type', 'avatar')
            ->orderByDesc('created_at')
            ->paginate(5);

        return $this->sendResponse($data);
    }
    public function destroyAvatar(Request $request)
    {
        if ($request->isMethod("DELETE")) {
            if ($request->user()->avatar) {
                $request->user()->avatar = null;
                $request->user()->save();

                return $this->sendResponse(['message' => 'Xóa ảnh đại diện thành công!']);
            } else {
                return $this->sendResponse(['message' => 'Không có ảnh đại diện để xóa!'], 404);
            }
        }
    }
    public function updateBackground(Request $request)
    {
        $user_id = auth()->user()->id;
        $post = new Post();
        $post->user_id = $user_id;
        $post->share_id = NULL;
        $post->content = $request->input('content') ?? '';
        $post->status = $request->input('status', 'public');
        $post->type = 'background';
        if (!$request->hasFile('background')) {
            return $this->sendResponse(['message' => 'Vui lòng tải lên ảnh đại diện!'], 400);
        } else if (!$request->file('background')->isValid() || strpos($request->file('background')->getMimeType(), 'image/') !== 0) {
            return $this->sendResponse(['message' => 'Định dạng không hợp lệ!'], 400);
        }
        $path = $request->file('background')->store('posts/image', 'public');
        $fullPath = url('storage/' . $path);
        $post->data = ['image' => [$fullPath]];
        $post->save();
        $request->user()->cover_avatar = $fullPath;
        $request->user()->save();

        return $this->sendResponse([
            'message' => 'Cập nhật ảnh bìa thành công!'
        ], 200);
    }
    public function listBackground()
    {
        $data = Post::where('user_id', auth()->user()->id)
            ->where('type', 'background')
            ->orderByDesc('created_at')
            ->paginate(5);

        return $this->sendResponse($data);
    }
    public function destroyBackground(Request $request)
    {
        if ($request->isMethod("DELETE")) {
            if ($request->user()->cover_avatar) {
                $request->user()->cover_avatar = null;
                $request->user()->save();

                return $this->sendResponse(['message' => 'Xóa ảnh bìa thành công!']);
            } else {
                return $this->sendResponse(['message' => 'Không có ảnh bìa để xóa!'], 404);
            }
        }
    }
}
