<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function getActivities(Request $request)
    {

        $search = $request->input('search', '');
        $page = $request->input('page', 1);

        $userIds = User::where('name', 'like', '%' . $search . '%')
            ->pluck('id');

        $activities = Activity::when($search, function ($query) use ($userIds) {
            return $query->whereIn('causer_id', $userIds);
        })
            ->orderByDesc('created_at')
            ->paginate(10, ['*'], 'page', $page);

        return response()->json($activities);
    }
}
