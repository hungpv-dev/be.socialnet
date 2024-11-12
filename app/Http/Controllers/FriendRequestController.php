<?php

namespace App\Http\Controllers;

use App\Models\FriendRequests;
use App\Models\User;
use App\Models\Friend;
use App\Notifications\Friend\{
    FriendNotification,
    FriendRequestNotification
};
use Illuminate\Http\Request;

class FriendRequestController extends Controller
{
    //Gửi lời mời kết bạn
    public function add(Request $request)
    {
        if ($request->method() == "POST") {
            $to_user = $request->id_account;
            $user = $request->user();

            // Kiểm tra người dùng có tồn tại
            $receiverUser = User::find($to_user);
            if (!$receiverUser) {
                return $this->sendResponse('Người dùng không tồn tại', 404);
            }

            if ($user->id == $to_user) {
                return $this->sendResponse('Không thể kết bạn với chính mình', 400);
            }

            // Kiểm tra mối quan hệ bạn bè
            $existingFriend = Friend::where(function ($query) use ($user, $to_user) {
                $query->where('user1', $user->id)->where('user2', $to_user);
            })->orWhere(function ($query) use ($user, $to_user) {
                $query->where('user1', $to_user)->where('user2', $user->id);
            })->first();

            if ($existingFriend) {
                return $this->sendResponse('Đã là bạn bè', 400);
            }

            // Kiểm tra yêu cầu kết bạn đã tồn tại
            $existingRequest = FriendRequests::where(function ($query) use ($user, $to_user) {
                $query->where('sender', $user->id)->where('receiver', $to_user);
            })->orWhere(function ($query) use ($user, $to_user) {
                $query->where('sender', $to_user)->where('receiver', $user->id);
            })->first();

            if ($existingRequest) {
                return $this->sendResponse('Yêu cầu kết bạn đã tồn tại', 400);
            }
            FriendRequests::create([
                'sender' => $user->id,
                'receiver' => $to_user,
            ]);
            $receiverUser->notify(new FriendRequestNotification());
            $receiverUser->follower++;
            $receiverUser->save();

            return $this->sendResponse('Gửi lời mời kết bạn thành công!');
        }
    }
    //Chấp nhận lời mời kết bạn
    public function accept(Request $request)
    {
        if ($request->method() == "POST") {
            $to_user = $request->id_account;
            $user = $request->user();

            if ($user->id == $to_user) {
                return $this->sendResponse('Không thể chấp nhận kết bạn với chính mình', 400);
            }
            // Kiểm tra người dùng có tồn tại
            $sender = User::find($to_user);
            if (!$sender) {
                return $this->sendResponse('Người dùng không tồn tại', 404);
            }

            // Kiểm tra mối quan hệ bạn bè
            $existingFriend = Friend::where(function ($query) use ($user, $to_user) {
                $query->where('user1', min($user->id, $to_user))
                    ->where('user2', max($user->id, $to_user));
            })->first();

            if ($existingFriend) {
                return $this->sendResponse('Đã là bạn bè', 400);
            }

            // Kiểm tra yêu cầu kết bạn đã tồn tại
            $existingRequest = FriendRequests::where(function ($query) use ($user, $to_user) {
                $query->where('sender', $user->id)->where('receiver', $to_user);
            })->orWhere(function ($query) use ($user, $to_user) {
                $query->where('sender', $to_user)->where('receiver', $user->id);
            })->first();

            if (!$existingRequest) {
                return $this->sendResponse('Không tìm thấy lời mời kết bạn', 400);
            }
            $existingRequest->delete();
            Friend::create([
                'user1' => min($user->id, $to_user),
                'user2' => max($user->id, $to_user)
            ]);
            $sender->notify(new FriendNotification());
            $sender->follower++;
            $sender->friend_counts++;
            $sender->save();
            $request->user()->friend_counts++;
            $request->user()->save();

            return $this->sendResponse('Chấp nhận lời mời kết bạn thành công!');
        }
    }
    //Từ chối lời mời kết bạn
    public function reject(Request $request)
    {
        if ($request->method() == "DELETE") {
            $to_user = $request->id_account;
            $user = $request->user();

            // Kiểm tra người dùng có tồn tại
            $sender = User::find($to_user);
            if (!$sender) {
                return $this->sendResponse('Người dùng không tồn tại', 404);
            }

            if ($user->id == $to_user) {
                return $this->sendResponse('Không thể từ chối lời mời kết bạn với chính mình', 400);
            }

            $existingRequest = FriendRequests::where(function ($query) use ($user, $to_user) {
                $query->where('sender', $to_user)->where('receiver', $user->id);
            })->first();

            if (!$existingRequest) {
                return $this->sendResponse('Không tìm thấy lời mời kết bạn', 400);
            }

            $existingRequest->delete();
            $request->user()->follower--;
            $request->user()->save();
            return $this->sendResponse('Từ chối lời mời kết bạn thành công!');
        }
    }
    // Xóa lời mời kết bạn đã gửi
    public function delete(Request $request)
    {
        if ($request->method() == "DELETE") {
            $to_user = $request->id_account;
            $user = $request->user();

            // Kiểm tra người dùng có tồn tại
            $receiver = User::find($to_user);
            if (!$receiver) {
                return $this->sendResponse('Người dùng không tồn tại', 404);
            }

            if ($user->id == $to_user) {
                return $this->sendResponse('Không thể xóa lời mời kết bạn với chính mình', 400);
            }

            $existingRequest = FriendRequests::where('sender', $user->id)
                ->where('receiver', $to_user)
                ->first();

            if (!$existingRequest) {
                return $this->sendResponse('Không tìm thấy lời mời kết bạn đã gửi', 400);
            }

            $existingRequest->delete();
            $receiver->follower--;
            $receiver->save();
            return $this->sendResponse('Xóa lời mời kết bạn thành công!');
        }
    }

    // Lấy danh sách lời mời kết bạn đã nhận
    public function getReceivedRequests(Request $request)
    {
        if ($request->method() == "GET") {
            $user = $request->user();
            $sort = $request->input('sort', 'desc');
            $perPage = $request->input('per_page', 10); // Số lượng item trên mỗi trang

            $requests = FriendRequests::where('receiver', $user->id)
                ->with('sender:id,name,avatar')
                ->orderBy('created_at', $sort)
                ->paginate($perPage);

            return $this->sendResponse([
                'requests' => $requests->items(),
                'total' => $requests->total(),
                'current_page' => $requests->currentPage(),
                'per_page' => $requests->perPage(),
                'last_page' => $requests->lastPage(),
            ]);
        }

        return $this->sendResponse('Phương thức không được hỗ trợ', 405);
    }

    // Lấy danh sách lời mời kết bạn đã gửi
    public function getSentRequests(Request $request)
    {
        if ($request->method() == "GET") {
            $user = $request->user();
            $sort = $request->input('sort', 'desc');
            $perPage = $request->input('per_page', 10); // Số lượng item trên mỗi trang

            if (!in_array($sort, ['asc', 'desc'])) {
                $sort = 'desc';
            }

            $requests = FriendRequests::where('sender', $user->id)
                ->with('receiver:id,name,avatar')
                ->orderBy('created_at', $sort)
                ->paginate($perPage);

            return $this->sendResponse([
                'requests' => $requests->items(),
                'total' => $requests->total(),
                'current_page' => $requests->currentPage(),
                'per_page' => $requests->perPage(),
                'last_page' => $requests->lastPage(),
            ]);
        }

        return $this->sendResponse('Phương thức không được hỗ trợ', 405);
    }
}
