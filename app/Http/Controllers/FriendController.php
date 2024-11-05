<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Friend;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\FriendRequests;

class FriendController extends Controller
{
    public function checkFriendStatus($user)
    {
        $mine = auth()->user()->id;
        if ($mine == $user) {
            return ['Thêm vào tin', 'Chỉnh sửa trang cá nhân'];
        }

        $friendship = Friend::where(function ($query) use ($user, $mine) {
            $query->where('user1', $user)->where('user2', $mine)
                ->orWhere('user1', $mine)->where('user2', $user);
        })->first();

        if ($friendship) {
            return ['Bạn bè', 'Nhắn tin'];
        }

        if (FriendRequests::where('sender', $mine)->where('receiver', $user)->first()) {
            return ['Hủy kết bạn', 'Nhắn tin'];
        }
        if (FriendRequests::where('sender', $user)->where('receiver', $mine)->first()) {
            return ['Chấp nhận', 'Từ chối', 'Nhắn tin'];
        }

        return ['Thêm bạn bè', 'Nhắn tin'];
    }
    //Tìm kiếm bạn bè
    public function findFriend(Request $request)
    {
        if (!$request->name) {
            return $this->sendResponse(['message' => 'Vui lòng điền tên tài khoản bạn muốn tìm kiếm!'], 404);
        }

        $per_page = $request->input('per_page', 10);
        $user = $request->user();

        $friendIds = Friend::where('user1', $user->id)->pluck('user2')
            ->merge(Friend::where('user2', $user->id)->pluck('user1'))
            ->unique()
            ->reject(fn($id) => $id == $user->id);


        $query = User::whereIn('id', $friendIds)
            ->where('is_active', 0)
            ->whereNull('deleted_at')
            ->select('id', 'name','avatar', 'address', 'hometown', 'gender', 'relationship', 'follower', 'friend_counts', 'is_online')
            ->where('name', 'LIKE', "%" . $request->name . "%")
            ->whereNotIn('id', function ($subQuery) use ($user) {
                $subQuery->select('user_block')->from('blocks')->where('user_is_blocked', $user->id);
            })
            ->whereNotIn('id', function ($subQuery) use ($user) {
                $subQuery->select('user_is_blocked')->from('blocks')->where('user_block', $user->id);
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
                $friend->friend_common = $this->findCommonFriends($request->user()->id, $friend->id);
            }
        }

        return $this->sendResponse($data);
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
            $friendUser->follower--;
            $friendUser->friend_counts--;
            $friendUser->save();
            $request->user()->follower--;
            $request->user()->friend_counts--;
            $request->user()->save();
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
        $user = $request->user();
        $perPage = $request->input('per_page', 10);

        // Lấy danh sách ID bạn bè hiện tại của người dùng
        $friendIds = Friend::where('user1', $user->id)
            ->orWhere('user2', $user->id)
            ->get(['user1', 'user2'])
            ->flatMap(fn($friend) => [$friend->user1, $friend->user2])
            ->reject(fn($id) => $id == $user->id)
            ->unique()
            ->values();

        // Lấy danh sách ID của bạn bè của bạn bè
        $friendsOfFriendsIds = Friend::whereIn('user1', $friendIds)
            ->orWhereIn('user2', $friendIds)
            ->get(['user1', 'user2'])
            ->flatMap(fn($friend) => [$friend->user1, $friend->user2])
            ->reject(fn($id) => $id == $user->id || $friendIds->contains($id))
            ->unique()
            ->values();

        // Lấy ID của những người có chung quê quán
        $friendsWithSameHometownIds = User::where('hometown', $user->hometown)
            ->pluck('id')
            ->reject(fn($id) => $id == $user->id)
            ->unique()
            ->values();

        // Lấy ID của những người có chung nơi ở
        $friendsWithSameAddressIds = User::where('address', $user->address)
            ->pluck('id')
            ->reject(fn($id) => $id == $user->id)
            ->unique()
            ->values();

        // Lấy danh sách ID của các yêu cầu kết bạn đã gửi hoặc nhận
        $friendRequestIds = FriendRequests::where('sender', $user->id)
            ->orWhere('receiver', $user->id)
            ->get(['sender', 'receiver'])
            ->flatMap(fn($request) => [$request->sender, $request->receiver])
            ->unique()
            ->values();

        // Danh sách ID gợi ý kết bạn kết hợp các điều kiện trên
        $arraySuggestIds = $friendsWithSameAddressIds
            ->merge($friendsWithSameHometownIds)
            ->merge($friendsOfFriendsIds)
            ->reject(fn($id) => $friendIds->contains($id) || $friendRequestIds->contains($id))
            ->unique()
            ->values();

        // Danh sách ID cần loại trừ (bạn bè và các yêu cầu kết bạn)
        $arrayNotSuggestIds = $friendIds
            ->merge($friendRequestIds)
            ->unique()
            ->values();

        // Lấy tất cả người dùng gợi ý
        $allSuggestedFriends = User::whereNotIn('id', $arrayNotSuggestIds)
            ->whereIn('id', $arraySuggestIds)
            ->select(['id', 'name', 'avatar'])
            ->paginate($perPage);

        return $this->sendResponse($allSuggestedFriends);
    }


    public function findCommonFriends($user1, $user2)
    {
        if ($user1 == $user2) {
            return [];
        }

        $user1Friends = $this->getUserFriends($user1);
        $user2Friends = $this->getUserFriends($user2);

        return array_values(array_filter(array_intersect($user1Friends, $user2Friends)));
    }

    private function getUserFriends($userId)
    {
        return Friend::where(function ($query) use ($userId) {
            $query->where('user1', $userId)
                ->orWhere('user2', $userId);
        })
            ->get()
            ->map(function ($friendship) use ($userId) {
                return $friendship->user1 == $userId ? $friendship->user2 : $friendship->user1;
            })
            ->filter()
            ->toArray();
    }
}
