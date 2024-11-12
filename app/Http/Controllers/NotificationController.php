<?php

namespace App\Http\Controllers;


use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function list(Request $request)
    {
        $data = $request->user()->notifications()->orderBy('updated_at', 'desc')->paginate(10);

        $request->user()->notifications()->update(['is_seen' => 1]);

        return response()->json($data);
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
