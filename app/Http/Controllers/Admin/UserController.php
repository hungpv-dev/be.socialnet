<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $perpage = $request->input('per_page', 10);
        $sort = $request->input('sort', 'desc');
        $status = $request->input('status', 2);
        $isAdmin = $request->input('is_admin', 2);
        $name = $request->name;

        $users = User::query()
            ->when($status != 2, function ($query) use ($status) {
                return $query->where('is_active', $status);
            })
            ->when($isAdmin != 2, function ($query) use ($isAdmin) {
                return $query->where('is_admin', $isAdmin);
            })
            ->when(!empty($name), function ($query) use ($name) {
                return $query->where('name', 'like', '%' . $name . '%');
            })
            ->orderBy('created_at', $sort)
            ->paginate($perpage);

        return response()->json($users);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
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
    public function destroy(string $id)
    {
        //
    }
}
