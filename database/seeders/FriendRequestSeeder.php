<?php

namespace Database\Seeders;

use App\Models\FriendRequests;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FriendRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        FriendRequests::factory()->count(100)->create();
    }
}
