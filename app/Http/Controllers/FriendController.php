<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Friend;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\FriendRequests;
use Illuminate\Support\Facades\Log;

class FriendController extends Controller
{
    public function checkFriendStatus($user1, $user2)
    {
        if ($user1 == $user2) {
            return ['Thêm vào tin', 'Chỉnh sửa trang cá nhân'];
        }

        $friendship = Friend::where(function ($query) use ($user1, $user2) {
            $query->where('from_user', $user1)->where('to_user', $user2)
                ->orWhere('from_user', $user2)->where('to_user', $user1);
        })->first();

        if (!$friendship) {
            return ['Thêm bạn bè', 'Nhắn tin'];
        }

        if ($friendship->is_accepted) {
            return ['Bạn bè', 'Nhắn tin'];
        }

        if ($friendship->from_user == $user1) {
            return ['Hủy kết bạn', 'Nhắn tin'];
        }

        return ['Chấp nhận', 'Từ chối'];
    }
    // Xóa mối quan hệ bạn bè
    public function removeFriend(Request $request)
    {
        if ($request->method() == "DELETE") {
            $friend_id = $request->id_account;
            $user = $request->user();

            // Kiểm tra người dùng có tồn tại
            $friendUser = User::find($friend_id);
            if (!$friendUser) {
                return $this->sendResponse('Người dùng không tồn tại', 404);
            }

            if ($user->id == $friend_id) {
                return $this->sendResponse('Không thể xóa mối quan hệ bạn bè với chính mình', 400);
            }

            // Tìm mối quan hệ bạn bè
            $friendship = Friend::where(function ($query) use ($user, $friend_id) {
                $query->where('user1', $user->id)->where('user2', $friend_id);
            })->orWhere(function ($query) use ($user, $friend_id) {
                $query->where('user1', $friend_id)->where('user2', $user->id);
            })->first();

            if (!$friendship) {
                return $this->sendResponse('Không tìm thấy mối quan hệ bạn bè', 400);
            }

            $friendship->delete();
            return $this->sendResponse('Xóa mối quan hệ bạn bè thành công!');
        }

        return $this->sendResponse('Phương thức không được hỗ trợ', 405);
    }
    // Lấy danh sách bạn bè
    public function getFriendList(Request $request)
    {
        if ($request->method() == "GET") {
            $user = $request->user();
            $sort = $request->input('sort', 'desc');
            $perPage = $request->input('per_page', 10);

            if (!in_array($sort, ['asc', 'desc'])) {
                $sort = 'desc';
            }

            $friends = Friend::where(function ($query) use ($user) {
                $query->where('user1', $user->id)
                    ->orWhere('user2', $user->id);
            })
                ->with(['user1:id,name,avatar', 'user2:id,name,avatar'])
                ->orderBy('created_at', $sort)
                ->paginate($perPage);

            $friendsList = $friends->map(function ($friendship) use ($user) {
                if (!is_object($friendship->user1) || !is_object($friendship->user2)) {
                    return null;
                }

                $friend = $friendship->user1->id === $user->id ? $friendship->user2 : $friendship->user1;

                return [
                    'id' => $friend->id,
                    'name' => $friend->name,
                    'avatar' => $friend->avatar,
                    'created_at' => $friendship->created_at,
                ];
            })->filter();

            return $this->sendResponse([
                'friends' => $friends->values(),
                'total' => $friends->total(),
                'current_page' => $friends->currentPage(),
                'last_page' => $friends->lastPage(),
                'per_page' => $friends->perPage(),
            ]);
        }

        return $this->sendResponse('Phương thức không được hỗ trợ', 405);
    }
    // Gợi ý lời mời kết bạn
    public function getSuggestFriends(Request $request)
    {
        if ($request->method() == "GET") {
            $user = $request->user();
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);

            // Lấy danh sách ID bạn bè hiện tại của người dùng
            $friendIds = Friend::where('user1', $user->id)
                ->orWhere('user2', $user->id)
                ->get(['user1', 'user2'])
                ->flatMap(function ($friend) use ($user) {
                    return [$friend->user1, $friend->user2];
                })
                ->reject(function ($id) use ($user) {
                    return $id == $user->id;
                })
                ->unique()
                ->values();

            // Lấy danh sách ID của các yêu cầu kết bạn đã gửi hoặc nhận
            $friendRequestIds = FriendRequests::where('sender', $user->id)
                ->orWhere('receiver', $user->id)
                ->get(['sender', 'receiver'])
                ->flatMap(function ($request) {
                    return [$request->sender, $request->receiver];
                })
                ->unique()
                ->values();

            // Lấy tất cả người dùng gợi ý
            $allSuggestedFriends = User::whereNotIn('id', $friendIds)
                ->whereNotIn('id', $friendRequestIds)
                ->where('id', '!=', $user->id)
                ->get(['id', 'name', 'avatar']);

            // Random lại danh sách
            $shuffledFriends = $allSuggestedFriends->shuffle();

            // Phân trang thủ công
            $total = $shuffledFriends->count();
            $suggestedFriends = $shuffledFriends->forPage($page, $perPage);

            return $this->sendResponse([
                'suggested_friends' => $suggestedFriends->values(),
                'total' => $total,
                'current_page' => $page,
                'per_page' => $perPage,
                'last_page' => ceil($total / $perPage),
            ]);
        }

        return $this->sendResponse('Phương thức không được hỗ trợ', 405);
    }

    public function findCommonFriends($user1, $user2)
    {
        if ($user1 == $user2) {
            return [];
        }

        $user1Friends = $this->getUserFriends($user1);
        $user2Friends = $this->getUserFriends($user2);

        return array_values(array_intersect($user1Friends, $user2Friends));
    }

    private function getUserFriends($userId)
    {
        return Friend::where(function ($query) use ($userId) {
            $query->where('from_user', $userId)
                ->orWhere('to_user', $userId);
        })
        ->where('is_accepted', 1)
        ->get()
        ->map(function ($friendship) use ($userId) {
            return $friendship->from_user == $userId ? $friendship->to_user : $friendship->from_user;
        })
        ->toArray();
    }
}
