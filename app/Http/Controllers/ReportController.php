<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{
    ChatRoom,
    Comment,
    Message,
    Post,
    ReportType,
    Report,
    Story,
    User
};
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ReportController extends Controller
{
    public function getReportType()
    {
        return response()->json(ReportType::all());
    }
    public function add(Request $request)
    {
        $type = $request->type;
        $id = $request->id;
        $report = new Report([
            'report_type_id' => $request->report_type,
            'content' => $request->content,
            'status' => 0,
            'user_id' => auth()->user()->id,
        ]);
        try {
            if($type === 'post') $model = Post::findOrFail($id);
            else if($type === 'user') $model = User::findOrFail($id);
            else if($type === 'comment') $model = Comment::findOrFail($id);
            else if($type === 'story') $model = Story::findOrFail($id);
            else if($type === 'room') $model = ChatRoom::findOrFail($id);
            else if($type === 'message') $model = Message::findOrFail($id);

            $model->reports()->save($report);

            return response()->json(['message' => 'Gửi báo cáo thành công!']);
        } catch (ModelNotFoundException $th) {
            return response()->json(['message' => 'Không tìm thấy bản ghi'], 404);
        }
    }
    public function destroy($id){
        $report = Report::findOrFail($id);
        $report->delete();

        return response()->json(['message' => 'Xóa báo cáo thành công!']);
    }
    //Lấy danh sách đơn tố cáo đã gửi
    // Thiếu xử lý lấy dữ liệu mục tiêu bị báo cáo
    public function myReport(Request $request){
        $sort = $request->sort ?? 'desc';

        $reports = Report::where('user_id', auth()->user()->id)
            ->with('report_type:id,name')
            ->with('reportable');

        if($request->status) $reports->where('status', $request->status);

        $reports = $reports->orderBy('created_at', $sort)
            ->paginate(5);

        return response()->json([$reports]);
    }
    public function show($id){
        $report = Report::with('reportable')->findOrFail($id);

        return response()->json($report);
    }
}
