<?php

use App\Events\Messages\MessagesSendEvent;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
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
    // Redis::set('test_key', 'Hello, Redis!');
    return Redis::get('test_key');
});

Route::get('/test', function () {
    $message = Message::find(1010);
    broadcast(new MessagesSendEvent($message));
    return 'ok';
});
