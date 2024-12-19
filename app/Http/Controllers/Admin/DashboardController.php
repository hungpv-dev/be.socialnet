<?php

namespace App\Http\Controllers\Admin;

use App\Models\Post;
use App\Models\User;
use App\Models\Report;
use App\Models\Comment;
use App\Models\Message;
use Illuminate\Http\Request;
// use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;
use Carbon\Carbon;

class DashboardController extends Controller
{

    public function getStatistics(Request $request)
{
    $timeFilter = $request->input('time_filter', '7_days'); // Mặc định là 7 ngày

    $startDate = match ($timeFilter) {
        '7_days' => Carbon::now()->subDays(7),
        '30_days' => Carbon::now()->subDays(30),
        '3_months' => Carbon::now()->subMonths(3),
        '6_months' => Carbon::now()->subMonths(6),
        '1_year' => Carbon::now()->subYear(),
        default => Carbon::now()->subDays(7),
    };

    $dates = collect();
    switch ($timeFilter) {
        case '30_days':
        case '3_months':
        case '6_months':
        case '1_year':
        case '7_days':
            for ($i = $startDate->diffInDays(Carbon::now()); $i >= 0; $i--) {
                $dates->push(Carbon::now()->subDays($i)->format('Y-m-d'));
            }
            break;
        default:
            for ($i = $startDate->diffInDays(Carbon::now()); $i > 0; $i--) {
                $dates->push(Carbon::now()->subDays($i)->format('Y-m-d'));
            }
            break;
    }

    $statistics = [
        'new_users' => [],
        'new_messages' => [],
        'new_posts' => [],
        'new_comments' => [],
        'new_reports' => [],
    ];

    foreach ($dates as $date) {
        if ($timeFilter == '7_days' || $timeFilter == '30_days' || $timeFilter == '3_months') {
            $start = Carbon::parse($date)->startOfDay();
            $end = Carbon::parse($date)->endOfDay();
        } else {
            $start = Carbon::parse($date)->startOfMonth();
            $end = Carbon::parse($date)->endOfMonth();
        }
        $statistics['new_users'][] = User::whereDate('created_at',$date)->count();
        $statistics['new_messages'][] = Message::whereDate('created_at', $date)->count();
        $statistics['new_posts'][] = Post::whereDate('created_at', $date)->count();
        $statistics['new_comments'][] = Comment::whereDate('created_at', $date)->count();
        $statistics['new_reports'][] = Report::whereDate('created_at', $date)->count();
    }

    return response()->json([
        'time_filter' => $timeFilter,
        'start_date' => $startDate->toDateString(),
        'dates' => $dates->toArray(),
        'statistics' => $statistics,
    ]);
}

}
