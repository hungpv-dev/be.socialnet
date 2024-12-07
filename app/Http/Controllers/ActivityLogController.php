<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function getUserActivityLog(Request $request)
    {
        $user = $request->user(); // Lấy người dùng hiện tại

        // Lấy lịch sử hoạt động của người dùng, giới hạn theo số lượng và sắp xếp theo ngày giảm dần
        $activities = Activity::where('causer_id', $user->id) // Lọc theo người dùng
            ->orderByDesc('created_at') // Sắp xếp theo thời gian
            ->skip($request->index)
            ->take(10) // Giới hạn kết quả trang (10 bản ghi mỗi trang)
            ->get();

        // Trả về kết quả dưới dạng JSON 
        return response()->json($activities);
    }
}
