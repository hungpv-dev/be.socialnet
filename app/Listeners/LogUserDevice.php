<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Laravel\Passport\Events\AccessTokenCreated;

class LogUserDevice
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(AccessTokenCreated $event): void
    {
        // Xử lý lấy địa chỉ thông tin người dùng lưu vào db
        $userId = $event->userId;
        Log::debug($userId);
        // $deviceInfo = Request::header('X-Device-Info');
        // $clientIp = Request::header('X-Client-Ip');
        // Log::debug($deviceInfo,$clientIp);

    }
}
