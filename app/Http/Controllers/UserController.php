<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Friend;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
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

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar,
            'friends_count' => $friendsCount,
        ]);
    }

    public function getUserPosts($userId)
    {
        $posts = User::findOrFail($userId)->posts()->latest()->take(10)->get();
        return response()->json($posts);
    }

    public function getUserStories($userId)
    {
        $stories = User::findOrFail($userId)
            ->stories()
            ->where('created_at', '>=', now()->subDay())
            ->latest()
            ->get();
        return response()->json($stories);
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
        return response()->json($friends);
    }

    public function updateProfile(Request $request)
    {
        if ($request->isMethod('PUT')) {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'address' => 'nullable|string|max:255',
                'hometown' => 'nullable|string|max:255',
                'gender' => 'nullable|in:male,female,other',
                'birthday' => 'nullable|date',
                'relationship' => 'nullable|in:single,married,divorced,widowed',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $user = $request->user();
            $user->update($validator->validated());

            return response()->json('Cập nhật thông tin cá nhân thành công');
        }

        return response()->json(['error' => 'Phương thức không được hỗ trợ'], 405);
    }
    public function updateAvatar(Request $request)
    {
        if ($request->isMethod('POST')) {
            //Đăng thành bài viết và cập nhật vào bảng users
        }

        return response()->json(['error' => 'Phương thức không được hỗ trợ'], 405);
    }

    public function updateBackground(Request $request)
    {
        if ($request->isMethod('POST')) {
            //Đăng thành bài viết và cập nhật vào bảng users
        }

        return response()->json(['error' => 'Phương thức không được hỗ trợ'], 405);
    }
}
