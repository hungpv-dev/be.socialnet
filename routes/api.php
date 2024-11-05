<?php

use App\Http\Controllers\Auth\{
    LogoutController,
    RegisterController,
    ResetPasswordController
};

use App\Http\Controllers\PostController;

use App\Http\Controllers\{
    BlockController,
    ChatRoomController,
    CommentControler,
    UserController,
    FriendController,
    FriendRequestController,
    MessageController,
    StoryController
};

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



Route::post('register',[RegisterController::class,'register']);
Route::post('register/verify', [RegisterController::class, 'verify']);

Route::prefix('password/')->group(function () {
    Route::post('forgot', [ResetPasswordController::class, 'sendToken']);
    Route::post("check/token", [ResetPasswordController::class, 'checkToken']);
    Route::post("reset", [ResetPasswordController::class, 'resetPassword']);
});

// Route::post('register', [RegisterController::class, 'register']);
// Route::post('/register', 'AuthController@register');

Route::middleware('auth:api')->group(function () {

    Route::post('/broadcasting/auth', function (Request $request) {
        return true;
    });


    Route::get('/user', function (Request $request) {
        return $request->user();
    });


    Route::apiResource('/posts',PostController::class);


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
        Route::prefix('avatar/')->group(function () {
            Route::post('update', [UserController::class, 'updateAvatar']);
            Route::get('list', [UserController::class, 'listAvatar']);
            Route::delete('destroy', [UserController::class, 'destroyAvatar']);
        });
        Route::prefix('background/')->group(function () {
            Route::post('update', [UserController::class, 'updateBackground']);
            Route::get('list', [UserController::class, 'listBackground']);
            Route::delete('destroy', [UserController::class, 'destroyBackground']);
        });
    });

    Route::prefix('story/')->group(function () {
        Route::get('{id}/viewer', [StoryController::class, 'getListViewer']);
    });
    Route::resource('story', StoryController::class);
    Route::resource('blocks', BlockController::class);

    Route::resource('comments', CommentControler::class);


});

