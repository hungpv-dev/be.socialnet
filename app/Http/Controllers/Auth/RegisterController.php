<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Jobs\LogActivityJob;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Client;
use Illuminate\Support\Facades\Http;

class RegisterController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $clientId = $request->input('client_id');
        $clientSecret = $request->input('client_secret');

        $client = Client::where('id', $clientId)->first();

        if (!$client || !hash_equals($client->secret, $clientSecret)) {
            return $this->sendResponse(['error' => 'Không thể xác thực client'], 401);
        }

        $data = $request->only('email','password','name');
        $data['password'] = Hash::make($request->input('password'));

        $user = User::create($data);

        LogActivityJob::dispatch(
            'user_account',            // Tên hành động (log name)
            $user,                     // Causer (người thực hiện hành động)
            $user,                     // Subject (người bị tác động)
            [
                'name' => $user->name,
                'email' => $user->email,  // Các thuộc tính cần ghi lại
                'created_at' => now(),    // Thời gian tạo
            ],
            "đã tạo một tài khoản." // Nội dung log
        );

        return $this->sendResponse([
            'success' => 'Tạo tài khoản thành công!',
        ],201);

    }
    public function verify(Request $request){
        if(!$request->email)
            return $this->sendResponse(['message'=> 'Đã có lỗi xảy ra!'],404);
        if(!$request->otp)
            return $this->sendResponse(['message' => 'Vui lòng nhập OTP'], 404);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->sendResponse(['message' => 'Không tìm thấy người dùng'], 404);
        }

        if ($user->email_verified_at) {
            return $this->sendResponse(['message' => 'Email đã được xác thực trước đó'], 400);
        }

        $cachedOTP = Cache::get('otp_verify_' . $request->email);
        if (!$cachedOTP) {
            return $this->sendResponse(['message' => 'Mã OTP đã hết hạn'], 400);
        }

        if ($cachedOTP !== $request->otp) {
            return $this->sendResponse(['message' => 'Mã OTP không hợp lệ'], 400);
        }

        $user->email_verified_at = now();
        $user->is_active = 1;
        $user->save();

        Cache::forget('otp_verify_' . $request->email);

        return $this->sendResponse(['message' => 'Xác nhận tài khoản thành công!']);
    }
}
