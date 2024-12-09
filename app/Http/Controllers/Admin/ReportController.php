<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\Report;
use App\Models\Comment;
use App\Models\UserStories;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\CommentControler;
use App\Notifications\Report\ApprovedNotification;
use App\Notifications\Report\DeclinedNotification;
use App\Notifications\Report\IsReportNotification;

class ReportController extends Controller
{
    public $commentControler;
    public function __construct(CommentControler $commentControler)
    {
        $this->commentControler = $commentControler;
    }
    public function index(Request $request)
    {
        $paginate = $request->input('paginate', 10);
        $sort = $request->input('sort', 'desc');
        $status = $request->input('status');
        $type = $request->input('type');
        $user = $request->input('user');

        $reports = Report::with('report_type:id,name')
            ->with('user')
            ->with('reportable')
            ->when($status !== null, function ($query) use ($status) {
                return $query->where('status', filter_var($status, FILTER_VALIDATE_BOOLEAN) ? 1 : 0);
            })
            ->when($type, function ($query) use ($type) {
                switch ($type) {
                    case 'user':
                        return $query->whereHas('reportable', function ($q) {
                            $q->where('reportable_type', 'App\Models\User');
                        });
                    case 'comment':
                        return $query->whereHas('reportable', function ($q) {
                            $q->where('reportable_type', 'App\Models\Comment');
                        });
                    case 'post':
                        return $query->whereHas('reportable', function ($q) {
                            $q->where('reportable_type', 'App\Models\Post');
                        });
                    case 'story':
                        return $query->whereHas('reportable', function ($q) {
                            $q->where('reportable_type', 'App\Models\Story');
                        });
                    case 'room':
                        return $query->whereHas('reportable', function ($q) {
                            $q->where('reportable_type', 'App\Models\ChatRoom');
                        });
                    case 'message':
                        return $query->whereHas('reportable', function ($q) {
                            $q->where('reportable_type', 'App\Models\Message');
                        });
                    default:
                        return $query;
                }
            })
            ->when($user, function ($query) use ($user) {
                return $query->where('user_id', $user);
            })
            ->orderBy('created_at', $sort)
            ->paginate($paginate);
        return response()->json($reports);
    }

    public function store(Request $request)
    {
        //
    }

    public function show(string $id)
    {
        try {
            $report = Report::with('reportable')->findOrFail($id);

            return response()->json($report);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Không tìm thấy báo cáo!'], 404);
        }
    }

    public function update(Request $request, string $id)
    {
        //User + Post -> chuyển trạng thái thành khóa
        //Comment + Story -> Xóa (Message + ChatRoom)
        try {
            if (!$request->status || !in_array($request->status, ['pending', 'approved', 'declined'])) {
                return response()->json(['message' => "Trạng thái không hợp lệ!"], 400);
            }
            $report = Report::findOrFail($id);
            // return response()->json($report->reportable);

            // if ($report->status != "pending") {
            //     return response()->json(['message' => "Đơn tố cáo đã được xử lý trước đó!"], 400);
            // }

            if ($request->status == "approved") {
                switch ($report->reportable_type) {
                    case 'App\Models\User':
                        $report->reportable->update(['is_active' => 1]);
                        $report->reportable->notify(new IsReportNotification($report->id, "Tài khoản của bạn đã bị khóa do vi phạm!"));
                        break;
                    case 'App\Models\Post':
                        $report->reportable->update(['is_active' => 0]);
                        User::find($report->reportable->user_id)->notify(new IsReportNotification($report->id, "Bài viết của bạn đã bị xóa do vi phạm!"));
                        break;
                    case 'App\Models\Comment':
                        $this->commentControler->deleteChildrenComments($report->reportable->id);
                        User::find($report->reportable->user_id)->notify(new IsReportNotification($report->id, "Bình luận của bạn đã bị xóa do vi phạm!"));
                    case 'App\Models\Story':
                        $userStory = UserStories::where('story_id', $report->reportable->id)->get();
                        foreach ($userStory as $item) {
                            $item->delete();
                        }
                        User::find($report->reportable->user_id)->notify(new IsReportNotification($report->id, "Tin của bạn đã bị xóa do vi phạm!"));
                    case 'App\Models\Message':
                        User::find($report->reportable->user_id)->notify(new IsReportNotification($report->id, "Tin nhắn của bạn đã bị xóa do vi phạm!"));
                    case 'App\Models\ChatRoom':
                        $report->reportable->delete();
                        break;
                    default:
                        return response()->json(['message' => "Đã xảy ra lỗi!"], 400);
                }

                $report->status = 'approved';
                $report->save();

                //Gửi thông báo đến người dùng gửi đơn tố cáo
                User::find($report->user_id)->notify(new ApprovedNotification($report->id));
                //Gửi thông báo đến mục tiêu bị tố cáo (Trong switch_case)

                return response()->json(['message' => "Báo cáo đã được phê duyệt!"], 200);
            } else if ($request->status == "declined") {
                $report->status = 'declined';
                $report->save();
                //Gửi thông báo tới người tố cáo
                User::find($report->user_id)->notify(new DeclinedNotification($report->id));

                return response()->json(['message' => "Báo cáo đã bị từ chối!"], 200);
            }

            return response()->json(['message' => "Thao tác xảy ra lỗi!"], 400);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Không tìm thấy báo cáo!'], 404);
        }
    }

    public function destroy(string $id)
    {
        try {
            $report = Report::findOrFail($id);
            $report->delete();

            return response()->json(['message' => 'Xóa báo cáo thành công!']);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Có lỗi xảy ra khi xóa báo cáo!'], 500);
        }
    }
}
