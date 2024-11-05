<?php

namespace App\Http\Controllers;

use App\Models\Story;
use App\Models\UserStories;
use Illuminate\Http\Request;

class StoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
        } else {
            $data = [
                "text" => $validatedData['data']
            ];
        }

        Story::create([
            'user_id' => $request->user()->id,
            'file' => json_encode($data),
            'status' => $validatedData['status'],
        ]);

        return response()->json(['message' => 'Tin đã được tạo thành công'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return $this->sendResponse($id);
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

        $request->validate([
            'emoji' => 'string',
        ]);

        if ($request->user()->id !== $story->user_id) {
            $emotion = $story->emotions()->where('user_id', $request->user()->id)->first();

            if (!$emotion) {
                $story->emotions()->create([
                    'user_id' => $request->user()->id,
                    'emoji' => $request->emoji,
                    'seen' => false,
                ]);
            } else if ($request->emoji !== null && $emotion->emoji !== $request->emoji) {
                $emotion->update([
                    'emoji' => $request->emoji,
                    'seen' => false,
                ]);
            }

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
