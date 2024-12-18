<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function getUserActivityLog(Request $request)
    {
        $user = $request->user();

        $activities = Activity::where('causer_id', $user->id)
            ->orderByDesc('created_at')
            ->skip($request->index)
            ->take(10)
            ->get();

        return response()->json($activities);
    }
}
