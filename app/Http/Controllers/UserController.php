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
    public function getUserBasicInfo($userId)
    {
        $user = User::findOrFail($userId);

        $friendsCount = Friend::where(function ($query) use ($userId) {
            $query->where('user1', $userId)
                ->orWhere('user2', $userId);
        })->count();

        return $this->sendResponse([
            'id' => $user->id,
            'name' => $user->name,
            'avatar' => $user->avatar,
            'friends_count' => $friendsCount,
        ]);
    }

    public function getUserPosts($userId)
    {
        $posts = User::findOrFail($userId)->posts()->latest()->take(10)->get();
        return $this->sendResponse($posts);
    }

    public function getUserStories($userId)
    {
        $stories = User::findOrFail($userId)
            ->stories()
            ->where('created_at', '>=', now()->subDay())
            ->latest()
            ->get();
        return $this->sendResponse($stories);
    }

    public function getUserFriends($userId)
    {
        $friends = Friend::where(function ($query) use ($userId) {
            $query->where('user1', $userId)
                ->orWhere('user2', $userId);
        })
            ->with(['user1:id,name,avatar', 'user2:id,name,avatar'])
            ->take(20)
            ->get()
            ->map(function ($friendship) use ($userId) {
                $friend = $friendship->user1->id === $userId ? $friendship->user2 : $friendship->user1;
                return [
                    'id' => $friend->id,
                    'name' => $friend->name,
                    'avatar' => $friend->avatar,
                ];
            });
        return $this->sendResponse($friends);
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
    //Làm việc với avatar
    public function updateAvatar(Request $request)
    {
        $user_id = auth()->user()->id;
        $post = new Post();
        $post->user_id = $user_id;
        $post->share_id = NULL;
        $post->content = $request->input('content', '');
        $post->status = $request->input('status', 'public');
        $post->type = 'avatar';
        if (!$request->hasFile('avatar')) {
            return $this->sendResponse(['message' => 'Vui lòng tải lên ảnh đại diện!'], 400);
        } else if (!$request->file('avatar')->isValid() || strpos($request->file('avatar')->getMimeType(), 'image/') !== 0) {
            return $this->sendResponse(['message' => 'Định dạng không hợp lệ!'], 400);
        }
        $path = $request->file('avatar')->store('posts/image', 'public');
        $fullPath = url('storage/' . $path);
        $post->data = ['image' => $fullPath];
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
    public function destroyAvatar(Request $request){
        if($request->isMethod("DELETE")){
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
        $post->content = $request->input('content', '');
        $post->status = $request->input('status', 'public');
        $post->type = 'background';
        if (!$request->hasFile('background')) {
            return $this->sendResponse(['message' => 'Vui lòng tải lên ảnh đại diện!'], 400);
        } else if (!$request->file('background')->isValid() || strpos($request->file('background')->getMimeType(), 'image/') !== 0) {
            return $this->sendResponse(['message' => 'Định dạng không hợp lệ!'], 400);
        }
        $path = $request->file('background')->store('posts/image', 'public');
        $fullPath = url('storage/' . $path);
        $post->data = ['image' => $fullPath];
        $post->save();
        $request->user()->cover_avatar = $fullPath;
        $request->user()->save();

        return $this->sendResponse([
            'message' => 'Cập nhật ảnh bìa thành công!'
        ], 200);
    }
    public function listBackground(){
        $data = Post::where('user_id', auth()->user()->id)
            ->where('type', 'background')
            ->orderByDesc('created_at')
            ->paginate(5);

        return $this->sendResponse($data);
    }
    public function destroyBackground(Request $request){
        if($request->isMethod("DELETE")){
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
