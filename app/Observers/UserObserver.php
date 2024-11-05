<?php

namespace App\Observers;

use App\Mail\Register;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        $oldOtpKey = 'otp_verify_' . $user->email;
        if (Cache::has($oldOtpKey)) {
            Cache::forget($oldOtpKey);
        }

        $otp = random_int(100000, 999999);
        Cache::put($oldOtpKey, $otp, now()->addMinutes(15));
        Mail::to($user->email)->queue(new Register($user, $otp));
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        //
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
