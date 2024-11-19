<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function list(Request $request)
    {
        $index = $request->input('index', 0);
        return $request->user()
            ->notifications()
            ->orderBy('updated_at', 'desc')
            ->skip($index)
            ->take(10)
            ->get();
    }
    public function seen(Request $request)
    {
        Notification::where('notifiable_id', $request->user()->id)
            ->where('is_seen', 0)
            ->update(['is_seen' => 1]);

        return response()->json(['message' => 'Thao tác thành công!']);
    }
    public function read(Request $request)
    {
        $noti = Notification::find($request->id);
        if ($noti) $noti->update(['is_read' => 1, 'is_seen' => 1]);

        return response()->json(['message' => 'Thao tác thành công!']);
    }
    public function readAll(Request $request)
    {
        $request->user()->notifications()->update(['is_read' => 1, 'is_seen' => 1]);

        return response()->json(['message' => 'Thao tác thành công!']);
    }
}
