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
    public $friendController;
    public function __construct(FriendController $friendController)
    {
        $this->friendController = $friendController;
    }
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

            return $this->sendResponse(['message' => 'Gửi lời mời kết bạn thành công!']);
        }
    }
    //Chấp nhận lời mời kết bạn
    public function accept(Request $request)
    {
        if ($request->method() == "POST") {
            $to_user = $request->id_account;
            $user = $request->user();

            if ($user->id == $to_user) {
                return $this->sendResponse(['message' => 'Không thể chấp nhận kết bạn với chính mình'], 400);
            }
            // Kiểm tra người dùng có tồn tại
            $sender = User::find($to_user);
            if (!$sender) {
                return $this->sendResponse(['message' => 'Người dùng không tồn tại'], 404);
            }

            // Kiểm tra mối quan hệ bạn bè
            $existingFriend = Friend::where(function ($query) use ($user, $to_user) {
                $query->where('user1', min($user->id, $to_user))
                    ->where('user2', max($user->id, $to_user));
            })->first();

            // Kiểm tra yêu cầu kết bạn đã tồn tại
            $existingRequest = FriendRequests::where(function ($query) use ($user, $to_user) {
                $query->where('sender', $user->id)->where('receiver', $to_user);
            })->orWhere(function ($query) use ($user, $to_user) {
                $query->where('sender', $to_user)->where('receiver', $user->id);
            })->first();

            if ($existingFriend) {
                $existingRequest->delete();
                return $this->sendResponse(['message' => 'Đã là bạn bè'], code: 400);
            }

            if (!$existingRequest) {
                return $this->sendResponse(['message' => 'Không tìm thấy lời mời kết bạn'], 400);
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

            return $this->sendResponse(['message' => 'Chấp nhận lời mời kết bạn thành công!']);
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
                return $this->sendResponse(['message' => 'Người dùng không tồn tại'], 404);
            }

            if ($user->id == $to_user) {
                return $this->sendResponse(['message' => 'Không thể từ chối lời mời kết bạn với chính mình'], 400);
            }

            $existingRequest = FriendRequests::where(function ($query) use ($user, $to_user) {
                $query->where('sender', $to_user)->where('receiver', $user->id);
            })->first();

            if (!$existingRequest) {
                return $this->sendResponse(['message' => 'Không tìm thấy lời mời kết bạn'], 404);
            }

            $existingRequest->delete();
            if($request->user()->follower > 0){
                $request->user()->follower--;
            }
            $request->user()->save();
            return $this->sendResponse(['message' => 'Từ chối lời mời kết bạn thành công!']);
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
                return $this->sendResponse(['message' => 'Người dùng không tồn tại'], 404);
            }

            if ($user->id == $to_user) {
                return $this->sendResponse(['message' => 'Không thể xóa lời mời kết bạn với chính mình'], 400);
            }

            $existingRequest = FriendRequests::where('sender', $user->id)
                ->where('receiver', $to_user)
                ->first();

            if (!$existingRequest) {
                return $this->sendResponse(['message' => 'Không tìm thấy lời mời kết bạn đã gửi'], 400);
            }

            $existingRequest->delete();
            $receiver->follower--;
            $receiver->save();
            return $this->sendResponse(['message' => 'Xóa lời mời kết bạn thành công!']);
        }
    }

    // Lấy danh sách lời mời kết bạn đã nhận
    public function getReceivedRequests(Request $request)
    {
        $user = $request->user();
        $index = $request->index; // Chỉ mục bắt đầu

        // Lấy yêu cầu kết bạn theo chỉ mục và sắp xếp theo id giảm dần
        $requests = FriendRequests::where('receiver', $user->id)
            ->with('sender:id,name,avatar,address,hometown,relationship,follower')
            ->orderBy('id', 'desc') // Sắp xếp theo id giảm dần
            ->skip($index) // Bỏ qua số lượng yêu cầu đã có từ chỉ mục bắt đầu
            ->take($request->input('take',10)) // Lấy 10 kết quả từ chỉ mục đó
            ->get(); // Lấy dữ liệu

        // Biến đổi dữ liệu để thêm thông tin về bạn chung
        $requests->transform(function ($sender) use ($user) {
            if ($sender->sender) {
                $sender->mutualFriends = count($this->friendController->findCommonFriends($user->id, $sender->sender));
            } else {
                $sender->mutualFriends = 0;
            }
            return $sender;
        });

        return $this->sendResponse($requests);
    }

    // Lấy danh sách lời mời kết bạn đã gửi
    public function getSentRequests(Request $request)
    {
        $user = $request->user();
        $index = $request->index;

        $requests = FriendRequests::where('sender', $user->id)
            ->with('receiver:id,name,avatar,address,hometown,relationship,follower')
            ->skip($index)
            ->take(10)
            ->get();

        $requests->transform(function ($recei) use ($user) {
            if ($recei->receiver) {
                $recei->mutualFriends = count($this->friendController->findCommonFriends($user->id, $recei->receiver));
            } else {
                $recei->mutualFriends = 0;
            }
            return $recei;
        });

        return $this->sendResponse($requests);
    }
}
