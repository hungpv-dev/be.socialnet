<?php

use App\Events\ChatRoom\PushMessage;
use App\Events\ChatRoom\SendMessage;
use App\Events\UserStatusUpdated;
use App\Http\Resources\ChatRoomResource;
use App\Models\ChatRoom;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use GuzzleHttp\Client as HttpClient;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
Route::get('/test', function () {
    dd(url('storage'));
});

Route::get('/update-ip', function () {
    try {
        $oldValue = '14.225.208.59'; // Giá trị cần thay thế
        $newValue = 'localhost';    // Giá trị thay thế
        $database = DB::select('SELECT DATABASE() AS db_name')[0]->db_name;
        $tables = DB::select('SHOW TABLES');
        $tableKey = "Tables_in_{$database}";

        foreach ($tables as $table) {
            $tableName = $table->$tableKey;

            // Lấy danh sách các cột của bảng
            $columns = DB::select("SHOW COLUMNS FROM {$tableName}");

            foreach ($columns as $column) {
                $columnName = $column->Field;
                $columnType = $column->Type;

                // Thay thế trong các cột kiểu VARCHAR hoặc TEXT
                if (str_contains($columnType, 'varchar') || str_contains($columnType, 'text')) {
                    DB::statement("UPDATE {$tableName} SET `{$columnName}` = REPLACE(`{$columnName}`, ?, ?)", [$oldValue, $newValue]);
                }

                // Thay thế trong các cột kiểu JSON
                if (str_contains($columnType, 'json')) {
                    $rows = DB::select("SELECT id, `{$columnName}` FROM {$tableName} WHERE JSON_CONTAINS(`{$columnName}`, '\"{$oldValue}\"')");

                    foreach ($rows as $row) {
                        $jsonData = json_decode($row->$columnName, true);

                        // Thay thế giá trị trong JSON
                        array_walk_recursive($jsonData, function (&$value) use ($oldValue, $newValue) {
                            if ($value === $oldValue) {
                                $value = $newValue;
                            }
                        });

                        // Cập nhật lại giá trị JSON đã thay thế
                        $updatedJson = json_encode($jsonData);
                        DB::table($tableName)->where('id', $row->id)->update([$columnName => $updatedJson]);
                    }
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => "Tất cả các giá trị '{$oldValue}' đã được thay thế thành '{$newValue}', bao gồm cả các cột JSON.",
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Có lỗi xảy ra trong quá trình thay thế.',
            'error' => $e->getMessage(),
        ], 500);
    }
});
Route::get('/update-json-ip', function () {
    try {
        $oldValue = '14.225.208.59';
        $newValue = 'localhost';
        $database = DB::select('SELECT DATABASE() AS db_name')[0]->db_name;
        $tables = DB::select('SHOW TABLES');
        $tableKey = "Tables_in_{$database}";

        foreach ($tables as $table) {
            $tableName = $table->$tableKey;

            // Lấy danh sách các cột của bảng
            $columns = DB::select("SHOW COLUMNS FROM {$tableName}");

            foreach ($columns as $column) {
                $columnName = $column->Field;
                $columnType = $column->Type;

                // Kiểm tra nếu cột là JSON
                if (str_contains($columnType, 'json')) {
                    // Lấy các hàng có dữ liệu JSON chứa giá trị cần thay thế
                    $rows = DB::table($tableName)->select('id', $columnName)->get();

                    foreach ($rows as $row) {
                        if (!empty($row->$columnName)) {
                            // Giải mã JSON thành mảng PHP
                            $jsonData = json_decode($row->$columnName, true);

                            if (json_last_error() === JSON_ERROR_NONE) {
                                // Thay thế giá trị trong mảng
                                array_walk_recursive($jsonData, function (&$value) use ($oldValue, $newValue) {
                                    if (is_string($value) && str_contains($value, $oldValue)) {
                                        $value = str_replace($oldValue, $newValue, $value);
                                    }
                                });

                                // Chuyển đổi mảng trở lại JSON
                                $updatedJson = json_encode($jsonData);

                                // Cập nhật lại cơ sở dữ liệu
                                DB::table($tableName)->where('id', $row->id)->update([$columnName => $updatedJson]);
                            }
                        }
                    }
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => "Đã thay thế tất cả giá trị '{$oldValue}' thành '{$newValue}' trong các cột JSON.",
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Có lỗi xảy ra trong quá trình thay thế.',
            'error' => $e->getMessage()
        ]);
    }
});