<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\ResetPassword\SendOTP;
use App\Models\ResetPassword;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ResetPasswordController extends Controller
{
    public function sendToken(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return $this->sendResponse("Tài khoản không tồn tại!", 404);
        }
        
        $otp = rand(100000, 999999);
        // Lưu mã OTP vào cache với thời gian hết hạn (ví dụ: 10 phút)
        Cache::put('otp_' . $request->email, $otp, now()->addMinutes(10));
        //Thực hiện gửi token cho người dùng tại đây
        Mail::to($user->email)->queue(new SendOTP($user, $otp));

        return $this->sendResponse("OTP đã được gửi!");
    }
    public function checkToken(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp'   => 'required|numeric'
        ]);
    
        $cachedOTP = Cache::get('otp_' . $request->email);
    
        if (!$cachedOTP || $cachedOTP != $request->otp) {
            return response()->json(['message' => 'Invalid or expired OTP.'], 400);
        }
    
        return response()->json(true);
    }
    public function resetPassword(Request $request) {
        $request->validate([
            'email' => 'required|email',
            'new_password' => 'required|min:6',
            'c_new_password' => 'required|min:6|same:new_password',
        ]);
    
        // Đảm bảo người dùng đã xác thực OTP trước khi đặt lại mật khẩu
        $cachedOTP = Cache::get('otp_' . $request->email);
        if (!$cachedOTP) {
            return response()->json(['message' => 'Please verify the OTP first.'], 400);
        }
    
        // Đặt lại mật khẩu cho người dùng
        $user = User::where('email', $request->email)->first();
    
        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }
    
        $user->password = Hash::make($request->new_password);
        $user->save();
    
        // Xóa mã OTP sau khi sử dụng
        Cache::forget('otp_' . $request->email);
    
        return response()->json(['message' => 'Password has been reset successfully.']);
    }
}
