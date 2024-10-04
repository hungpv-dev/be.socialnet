<?php

use App\Http\Controllers\Auth\{
    LogoutController,
    RegisterController
};
use App\Http\Controllers\ChatRoomController;
use App\Http\Controllers\MessageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('register',[RegisterController::class,'register']);

Route::middleware('auth:api')->group(function(){
    
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    Route::post('/logout', [LogoutController::class,'logout']);
    Route::post('/logout-all-driver', [LogoutController::class,'logoutOtherFromDriver']);


    Route::prefix('chat-room')->group(function(){
        Route::get('/',[ChatRoomController::class,'index']);
        Route::post('/',[ChatRoomController::class,'store']);
        Route::get('/{id}',[ChatRoomController::class,'show']);
        Route::post('/notification/{id}',[ChatRoomController::class,'notification']);
        Route::put('/send/{id}',[ChatRoomController::class,'send']);
    });

    Route::prefix('messages')->group(function(){
        Route::get('/',[MessageController::class,'index']);
        Route::post('/',[MessageController::class,'store']);
    });
});
