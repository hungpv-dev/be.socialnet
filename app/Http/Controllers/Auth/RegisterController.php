<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
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

        User::create($data);
        
        return $this->sendResponse([
            'success' => 'Tạo tài khoản thành công!',
        ],201);

    }
}
