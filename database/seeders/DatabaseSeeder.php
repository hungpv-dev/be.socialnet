<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\ChatType;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // $this->call(UserSeeder::class);
        ChatType::insert([['id' => 1,'name' => 'Chat riêng tư'],['id' => 2,'name' => 'Chat nhóm']]);
        // $this->call(ChatRoomSeeder::class);
        // $this->call(MessageSeeder::class);
        // $this->call(FriendRequestSeeder::class);
        // $this->call(FriendSeeder::class);
        // $this->call(BlockSeeder::class);
        // $this->call(UserStoriesSeeder::class);
        // $this->call(PostSeeder::class);
        // $this->call(NotificationSeeder::class);
        // $this->call(ReportTypeSeeder::class);
        // $this->call(ReportSeeder::class);
    }
}
