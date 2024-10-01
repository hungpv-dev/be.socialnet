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


    Route::apiResource('/chat-room',ChatRoomController::class);
    Route::apiResource('/messages',MessageController::class);
});
