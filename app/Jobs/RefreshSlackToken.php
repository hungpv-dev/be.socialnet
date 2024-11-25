<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class RefreshSlackToken implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $response = Http::post('https://slack.com/api/oauth.v2.access', [
            'refresh_token' => env('SLACK_REFRESH_TOKEN'),
            'grant_type' => 'refresh_token'
        ]);

        if ($response->successful()) {
            $data = $response->json();
            // Cập nhật token mới vào .env hoặc database
            $this->updateTokens($data['access_token'], $data['refresh_token']);
        }
    }

    private function updateTokens($accessToken, $refreshToken) 
    {
        $envFile = base_path('.env');
        $envContent = file_get_contents($envFile);
        
        // Cập nhật access token
        $envContent = preg_replace(
            '/SLACK_BOT_USER_OAUTH_TOKEN=.*/',
            'SLACK_BOT_USER_OAUTH_TOKEN=' . $accessToken,
            $envContent
        );
        
        // Cập nhật refresh token
        $envContent = preg_replace(
            '/SLACK_REFRESH_TOKEN=.*/',
            'SLACK_REFRESH_TOKEN=' . $refreshToken, 
            $envContent
        );
        
        file_put_contents($envFile, $envContent);
        // Logic cập nhật token
    }
}
