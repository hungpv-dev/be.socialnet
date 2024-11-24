<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Trả về một response JSON thành công.
     * @param  mixed  $response  Dữ liệu muốn trả về
     * @param  number  $code  Status code muốn trả về
     * @return \Illuminate\Http\JsonResponse
     */

    public function sendResponse($response, $code = 200)
    {
        return response()->json($response, $code);
    }
    // Hàm xóa file khi có file mới được tải lên
    public function deleteFile($url)
    {
        $filePath = public_path(str_replace(url('storage/'), 'storage/', $url));
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
}
