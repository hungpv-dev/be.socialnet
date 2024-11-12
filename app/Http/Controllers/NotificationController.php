<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $index = $request->input('index', 0);
        $query = $request->user()->notifications();
        if ($request->has('count')) {
            return $this->sendResponse($query->whereNull('read_at')->count());
        }
        return $query->orderBy('updated_at', 'desc')
            ->skip($index)
            ->take(10)
            ->get();
    }
}
