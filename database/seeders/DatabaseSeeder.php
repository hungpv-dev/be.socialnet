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
        $this->call(UserSeeder::class);
        $this->call(ChatRoomSeeder::class);
        ChatType::insert([['id' => 1,'name' => 'Chat riêng tư'],['id' => 2,'name' => 'Chat nhóm']]);
        $this->call(MessageSeeder::class);
    }
}
