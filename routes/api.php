<?php

use App\Http\Controllers\Auth\{
    LogoutController,
    RegisterController,
    ResetPasswordController
};

use App\Http\Controllers\Admin;

use App\Http\Controllers\{
    ActivityLogController,
    BlockController,
    ChatRoomController,
    CommentControler,
    EmotionController,
    CommentController,
    PostController,
    UserController,
    FriendController,
    FriendRequestController,
    MessageController,
    StoryController,
    NotificationController,
};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->get('/user/activity-log', [ActivityLogController::class, 'getUserActivityLog']);

Route::post('register', [RegisterController::class, 'register']);
Route::post('register/verify', [RegisterController::class, 'verify']);

Route::prefix('password/')->group(function () {
    Route::post('forgot', [ResetPasswordController::class, 'sendToken']);
    Route::post("check/token", [ResetPasswordController::class, 'checkToken']);
    Route::post("reset", [ResetPasswordController::class, 'resetPassword']);
});

// Route::post('register', [RegisterController::class, 'register']);
// Route::post('/register', 'AuthController@register');

Route::middleware(['auth:api'])->group(function () {

    Route::post('/broadcasting/auth', function (Request $request) {
        return Broadcast::auth($request);
    });

    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/friend-ids', function (Request $request) {
        return (new PostController())->getFriendIds($request->user());
    });

    Route::prefix('notifications')->group(function () {
        Route::get('', [NotificationController::class, 'list']);
        Route::get('unseen-count', [NotificationController::class, 'unseenCount']);
        Route::post('seen', [NotificationController::class, 'seen']);
        Route::post('read', [NotificationController::class, 'read']);
        Route::post('read/all', [NotificationController::class, 'readAll']);
    });

    Route::get('/posts/by/user/{id}', [PostController::class, 'getPostByUser']);
    Route::apiResource('/posts', PostController::class);

    Route::apiResource('/emotions', EmotionController::class);


    Route::post('/logout', [LogoutController::class, 'logout']);
    Route::post('/logout-all-driver', [LogoutController::class, 'logoutOtherFromDriver']);

    Route::post('/change-status', [LogoutController::class, 'changeStatus']);


    Route::prefix('chat-room')->group(function () {
        Route::get('/', [ChatRoomController::class, 'index']);
        Route::get('/search', [ChatRoomController::class, 'search']);
        Route::get('/images/{id}', [ChatRoomController::class, 'images']);
        Route::post('/', [ChatRoomController::class, 'store']);
        Route::get('/{id}', [ChatRoomController::class, 'show']);
        Route::put('/{id}', [ChatRoomController::class, 'update']);
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
        Route::post('find', [FriendController::class, 'findFriend']);
        Route::delete('remove', [FriendController::class, 'removeFriend']);
        Route::get('list/{id}', [FriendController::class, 'getFriendList']);
        Route::get('suggest', [FriendController::class, 'getSuggestFriends']);
        Route::get('common/list/{id}', [FriendController::class, 'listCommonFriends']);

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
        Route::post('find', [UserController::class, 'findUser']);
        Route::post('login', [UserController::class, 'isLogin']);
        Route::get('{id}', [UserController::class, 'getProfile']);
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

    Route::prefix('story/{id}/')->group(function () {
        Route::get('viewer', [StoryController::class, 'getListViewer']);
        Route::post('emotion', [StoryController::class, 'emotion']);
    });

    Route::get('comments/by/{type}/{id}', [CommentControler::class, 'getComments']);

    Route::resource('story', StoryController::class);
    Route::resource('blocks', BlockController::class);
    Route::resource('comments', CommentControler::class);
});

Route::prefix('admin')->group(function(){
    Route::resource('reports/type', Admin\ReportTypeController::class);
    Route::resource('reports', Admin\ReportController::class);
    Route::resource('users', Admin\UserController::class);
});
