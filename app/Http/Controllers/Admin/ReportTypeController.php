<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReportType;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReportTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = ReportType::query();

        if ($request->asc === 'asc') {
            $query->orderBy('created_at', 'ASC');
        } elseif ($request->asc === 'desc') {
            $query->orderBy('created_at', 'DESC');
        }

        $perPage = $request->perpage ?? 10;

        $type = $query->paginate($perPage);

        return response()->json($type);
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Vui lòng nhập thông tin hợp lệ!', 'errors' => $validator->errors()], 400);
        }

        if (ReportType::where('name', $request->type)->exists()) {
            return response()->json(['message' => 'Thông tin đã tồn tại!'], 422);
        }

        ReportType::create(['name' => $request->type]);

        return response()->json(['message' => 'Thêm mới thành công!'], 201);
    }
    public function show(string $id)
    {
        //
    }
    public function update(Request $request, string $id)
    {
        try {
            $type = ReportType::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'type' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json(['message' => 'Vui lòng nhập thông tin hợp lệ!', 'errors' => $validator->errors()], 400);
            }

            if ($request->type !== $type->name && ReportType::where('name', $request->type)->exists()) {
                return response()->json(['message' => 'Thông tin đã tồn tại!'], 422);
            }

            $type->update(['name' => $request->type]);

            return response()->json(['message' => 'Cập nhật thành công!'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Thông tin không tồn tại'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Đã xảy ra lỗi, vui lòng thử lại sau!'], 500);
        }
    }
    public function destroy(string $id)
    {
        try {
            $type = ReportType::findOrFail($id);
            $type->delete();

            return response()->json(['message' => 'Xóa thành công!']);
        } catch (ModelNotFoundException $th) {
            return response()->json(['message' => "Thông tin không tồn tại"], 404);
        }
    }
}
