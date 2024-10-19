<?php

use App\Http\Controllers\Auth\{
    LogoutController,
    RegisterController
};
use App\Http\Controllers\{
    BlockController,
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

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [LogoutController::class, 'logout']);
    Route::post('/logout-all-driver', [LogoutController::class, 'logoutOtherFromDriver']);


    Route::apiResource('/chat-room', ChatRoomController::class);
    Route::apiResource('/messages', MessageController::class);

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

    Route::resource('blocks', BlockController::class);
});
