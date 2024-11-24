<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Story;
use App\Models\UserStories;
use App\Notifications\Story\CreateNotification;
use Illuminate\Http\Request;
use App\Notifications\Story\EmotionNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class StoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        $friendIds = (new PostController)->getFriendIds($user);
        $friendStories = User::whereIn('id', $friendIds)
            ->whereHas('stories', function($query) {
                $query->where('created_at', '>=', now()->subHours(24))
                    ->where('status', '!=', 'private');
            })
            ->with(['stories' => function($query) {
                $query->where('created_at', '>=', now()->subHours(24))
                    ->where('status', '!=', 'private')
                    ->orderBy('created_at', 'asc');
            }, 'stories.user_emotion'])
            ->get();

        $userStories = User::where('id', $user->id)
            ->whereHas('stories', function($query) {
                $query->where('created_at', '>=', now()->subHours(24));
            })
            ->with(['stories' => function($query) {
                $query->where('created_at', '>=', now()->subHours(24))
                    ->orderBy('created_at', 'asc');
            }, 'stories.user_emotion'])
            ->get();
        $friendStories = $userStories->concat($friendStories);
        
        return $this->sendResponse($friendStories);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'data' => 'required',
            'status' => 'required|in:public,friend,private',
        ]);

        if ($request->hasFile('data')) {
            $file = $request->file('data');
            if ($file) {
                $filePath = $file->store('public/stories');
                $path = url(str_replace('public/', 'storage/', $filePath));
                $mimeType = $file->getMimeType();
                $type = strpos($mimeType, 'video') === 0 ? 'video' : 'image';
                $data = [
                    $type => $path
                ];
            } else {
                return response()->json(['message' => 'File không hợp lệ'], 400);
            }
        }
        $story = Story::create([
            'user_id' => $request->user()->id,
            'file' => $data,
            'status' => $validatedData['status'],
        ]);

        $friendIds = (new PostController())->getFriendIds(Auth::user());
        $users = User::whereIn('id', $friendIds)->get();
        foreach ($users as $friend) {
            try {
                $notification = new CreateNotification($story->id, 'đã đăng một tin mới');
                $friend->notify($notification);
            } catch (\Exception $e) {
                Log::error('Lỗi gửi thông báo: ' . $e->getMessage());
                continue;
            }
        }

        $userStories = User::where('id', Auth::id())
            ->whereHas('stories', function($query) {
                $query->where('created_at', '>=', now()->subHours(24));
            })
            ->with(['stories' => function($query) {
                $query->where('created_at', '>=', now()->subHours(24))
                    ->orderBy('created_at', 'asc');
            }, 'stories.user_emotion'])
            ->first();

        return response()->json([
            'data' => $userStories,
            'message' => 'Tin đã được tạo thành công'
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $story = Story::findOrFail($id);

        return $this->sendResponse($story);
    }

    /**
     * Update the specified resource in storage.
     */
    //Cập nhật trạng thái của tin
    public function update(Request $request, string $id)
    {
        if ($request->isMethod("PUT")) {
            $story = Story::find($id);
            if (!$story) {
                return $this->sendResponse(['message' => 'Không tìm thấy tin'], 404);
            }
            if ($request->user()->id !== $story->user_id) {
                return $this->sendResponse(['message' => 'Bạn không có quyền cập nhật tin này'], 403);
            }

            $validatedData = $request->validate([
                'status' => 'required|in:public,friend,private',
            ]);

            $story->update($validatedData);

            return $this->sendResponse(['message' => 'Cập nhật trạng thái tin thành công'], 200);
        } else {
            return $this->sendResponse(['message' => 'Method not allowed'], 405);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id, Request $request)
    {
        $story = Story::find($id);

        if (!$story) {
            return $this->sendResponse(['message' => 'Không tìm thấy tin'], 404);
        }

        if ($request->user()->id !== $story->user_id) {
            return $this->sendResponse(['message' => 'Bạn không có quyền xóa tin này'], 403);
        }

        $story->delete();

        return $this->sendResponse(['message' => 'Xóa tin thành công'], 200);
    }
    public function emotion(Request $request, string $id)
    {
        $story = Story::findOrFail($id);

        // $request->validate([
        //     'emoji' => 'string',
        // ]);

        if ($request->user()->id !== $story->user_id) {
            // Kiểm tra xem người dùng đã tương tác với câu chuyện này chưa
            $emotion = UserStories::where('user_id', $request->user()->id)
                ->where('story_id', $story->id)
                ->first();

            // Nếu chưa có cảm xúc, tạo mới
            if (!$emotion) {
                UserStories::create([
                    'story_id' => $story->id,
                    'user_id' => $request->user()->id,
                    'emoji' => $request->emoji,
                    'seen' => false,
                ]);
                $story->user_count++;
            } elseif ($request->emoji !== '') { // Xử lý khi emoji không phải null hoặc chuỗi rỗng
                $emotion->update([
                    'emoji' => $request->emoji,
                    'seen' => false,
                ]);
            }

            // Cập nhật thông báo nếu emoji được chọn
            $existingNotification = $story->user->notifications()
                ->where('data->story_id', $story->id)
                ->first();

            if ($existingNotification) {
                // Nếu có thông báo cũ, cập nhật thông báo
                $emotionCount = UserStories::where('story_id', $story->id)->whereNotNull('emoji')->count();
                $message = $emotionCount > 1
                    ? 'và những người khác đã bày tỏ cảm xúc về tin của bạn.'
                    : 'đã bày tỏ cảm xúc về tin của bạn.';

                $existingNotification->update([
                    'data' => [
                        'story_id' => $story->id,
                        'avatar' => auth()->user()->avatar,
                        'message' => '<b>' . auth()->user()->name . '</b> ' . $message,
                    ]
                ]);
            } else {
                // Nếu không có thông báo, gửi thông báo mới
                $emotionCount = UserStories::where('story_id', $story->id)->whereNotNull('emoji')->count();
                $message = $emotionCount > 1
                    ? 'và những người khác đã bày tỏ cảm xúc về tin của bạn.'
                    : 'đã bày tỏ cảm xúc về tin của bạn.';

                $story->user->notify(new EmotionNotification($story->id, $message));
            }

            $story->save();

            return $this->sendResponse(['message' => 'Đã cập nhật biểu cảm'], 200);
        }


        return $this->sendResponse(['message' => 'Bạn không thể thêm biểu cảm vào tin của chính mình'], 403);
    }
    public function getListViewer(Request $request)
    {
        $story = Story::find($request->id);
        if (!$story) {
            return $this->sendResponse(['message' => 'Tin không tồn tại!'], 404);
        }

        if ($story->user_id !== $request->user()->id) {
            return $this->sendResponse(['message' => 'Bạn không có quyền truy cập!'], 403);
        }

        $user_stories = UserStories::where('story_id', $story->id)
            ->with(['user:id,name,avatar'])
            ->get();

        UserStories::where('story_id', $story->id)
            ->where('seen', false)
            ->update(['seen' => true]);

        return $this->sendResponse([
            'data' => $user_stories,
            'count' => $user_stories->count()
        ], 200);
    }
}
