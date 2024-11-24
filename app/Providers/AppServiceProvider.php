<?php

namespace App\Providers;

use App\Models\ChatRoom;
use App\Models\User;
use App\Observers\ChatRoomObserver;
use App\Observers\UserObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        
    }

    public function boot(): void
    {
        User::observe(UserObserver::class);
        ChatRoom::observe(ChatRoomObserver::class);
    }
}
