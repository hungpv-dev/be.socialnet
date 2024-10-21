<?php

use App\Http\Controllers\Auth\{
    LogoutController,
    RegisterController
};
use App\Http\Controllers\{
    ChatRoomController,
    UserController,
    FriendController,
    FriendRequestController,
    MessageController,
};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('register', [RegisterController::class, 'register']);

Route::middleware('auth:api')->group(function () {

    Route::post('/broadcasting/auth', function (Request $request) {
        return true;
    });

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [LogoutController::class, 'logout']);
    Route::post('/logout-all-driver', [LogoutController::class, 'logoutOtherFromDriver']);


    Route::prefix('chat-room')->group(function () {
        Route::get('/', [ChatRoomController::class, 'index']);
        Route::post('/', [ChatRoomController::class, 'store']);
        Route::get('/{id}', [ChatRoomController::class, 'show']);
        Route::post('/notification/{id}', [ChatRoomController::class, 'notification']);
        Route::put('/send/{id}', [ChatRoomController::class, 'send']);
    });

    Route::prefix('messages')->group(function () {
        Route::get('/', [MessageController::class, 'index']);
        Route::post('/', [MessageController::class, 'store']);
        Route::post('/send-icon/{id}', [MessageController::class, 'sendIcon']);
        Route::delete('/{id}', [MessageController::class, 'destroy']);
    });


    Route::prefix('friend/')->group(function () {
        Route::delete('remove', [FriendController::class, 'removeFriend']);
        Route::get('list', [FriendController::class, 'getFriendList']);
        Route::get('suggest', [FriendController::class, 'getSuggestFriends']);
        Route::prefix('request/')->group(function () {
            Route::post('add', [FriendRequestController::class, 'add']);
            Route::post('accept', [FriendRequestController::class, 'accept']);
            Route::delete('reject', [FriendRequestController::class, 'reject']);
            Route::delete('delete', [FriendRequestController::class, 'delete']);
            Route::get('all', [FriendRequestController::class, 'getReceivedRequests']);
            Route::get('sent', [FriendRequestController::class, 'getSentRequests']);
        });
    });

    Route::prefix('user/')->group(function () {
        Route::put('profile/update', [UserController::class, 'updateProfile']);
        Route::put('avatar/update', [UserController::class, 'updateAvatar']);
        Route::put('background/update', [UserController::class, 'updateBackground']);
    });
});
