<?php

use App\Events\ChatRoom\PushMessage;
use App\Events\ChatRoom\SendMessage;
use App\Http\Resources\ChatRoomResource;
use App\Models\ChatRoom;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

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
Route::get('/user', function () {
    User::all();
    // // Redis::set('test_key', 'Hello, Redis!');
    // return Redis::get('test_key');
});

Route::get('/test', function () {
    return response()->json(now());
    // $newRoom = ChatRoom::find(15);
    // $roomResource = new ChatRoomResource($newRoom);
    // return $roomResource;
});
