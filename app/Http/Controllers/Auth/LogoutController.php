<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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
}
