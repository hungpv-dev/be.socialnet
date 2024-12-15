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
        $key = $request->input('key');

        $users = User::query()
            ->when($status != 2, function ($query) use ($status) {
                return $query->where('is_active', $status);
            })
            ->when($isAdmin != 2, function ($query) use ($isAdmin) {
                return $query->where('is_admin', $isAdmin);
            })
            ->when(!empty($key), function ($query) use ($key) {
                return $query->where('name', 'like', '%' . $key . '%')
                    ->orWhere('email', 'like', '%' . $key . '%');
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
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $isActive = $request->input('is_active', $user->is_active);
        $isAdmin = $request->input('is_admin', $user->is_admin);

        // Check if there are changes before saving
        if ($user->is_active !== $isActive || $user->is_admin !== $isAdmin) {
            $user->is_active = $isActive;
            $user->is_admin = $isAdmin;
            $user->save();

        }
        return response()->json([
            'user' => $user,
            'message' => 'Cập nhật tài khoản thành công!'
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
