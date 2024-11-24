<?php

namespace App\Http\Controllers\Auth;

use App\Events\UserStatusUpdated;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Passport\RefreshToken;
use Laravel\Passport\Token;

class LogoutController extends Controller
{
    public function logout(Request $request)
    {
        $token =  $request->user()->token();
        $tokenId = $token['id'];
        $token->revoke();
        $this->revokeRefreshToken([$tokenId]);
        return $this->sendResponse(['message' => 'Đăng xuất thành công'],200);
    }

    public function logoutOtherFromDriver(Request $request)
    {
        $tokensId =  $request->user()->tokens->pluck('id');
        Token::whereIn('id', $tokensId)->update(['revoked' => true]);
        $this->revokeRefreshToken($tokensId);

        return $this->sendResponse(['message' => 'Đăng xuất thành công'],200);
    }

    private function revokeRefreshToken($tokenId){
        RefreshToken::whereIn('access_token_id', $tokenId)->update(['revoked' => true]);
    }

    public function changeStatus(Request $request){
        $user = User::findOrFail($request->user_id);
        $user->is_online = $request->input('is_online', false);
        if(!$user->is_online){
            $user->time_offline = now();
        }
        $user->save();
        broadcast(new UserStatusUpdated($user));
        return $this->sendResponse([
            'message' => 'Cập nhật trạng thái thành công!'
        ], 200);
    }
}
