<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\User;
use Illuminate\Http\Request;

class BlockController extends Controller
{
    public function isUserBlocked($user1Id, $user2Id)
    {
        return Block::where('user_block', $user1Id)
            ->where('user_is_blocked', $user2Id)
            ->exists();
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $blocks = Block::where('user_block', $request->user()->id)
            ->with('userIsBlocked:id,name,avatar')
            ->paginate(10);

        return $this->sendResponse($blocks);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $currentUser = $request->user();
        $userToBlock = User::find($request->id_account);

        if (!$userToBlock) {
            return $this->sendResponse(['message' => 'Người dùng không tồn tại.',], 422);
        }

        if ($currentUser->id === $userToBlock->id) {
            return $this->sendResponse(['message' => 'Không thể tự chặn chính mình.',], 422);
        }

        if (Block::where('user_block', $currentUser->id)->where('user_is_blocked', $userToBlock->id)->exists()) {
            return $this->sendResponse(['message' => 'Người dùng đã bị chặn từ trước.']);
        }

        $block = Block::create([
            'user_block' => $currentUser->id,
            'user_is_blocked' => $userToBlock->id,
        ]);

        return $this->sendResponse(['message' => 'Người dùng đã bị chặn thành công.']);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id, Request $request)
    {
        $currentUser = $request->user();
        $userToUnblock = User::findOrFail($id);

        $block = Block::where('user_block', $currentUser->id)
            ->where('user_is_blocked', $userToUnblock->id)
            ->first();

        if (!$block) {
            return $this->sendResponse(['message' => 'Không tìm thấy thông tin chặn người dùng.'], 404);
        }

        $block->delete();

        return $this->sendResponse(['message' => 'Bỏ chặn người dùng thành công.']);
    }
    public function listBlockId()
    {
        $blockedMeIds = Block::where('user_block', auth()->user()->id)
            ->pluck('user_is_blocked')
            ->unique()
            ->values();

        $iBlockedIds = Block::where('user_is_blocked', auth()->user()->id)
            ->pluck('user_block')
            ->unique()
            ->values();

        $allBlockedIds = $blockedMeIds->merge($iBlockedIds)->unique()->values();

        return $allBlockedIds;
    }
}
