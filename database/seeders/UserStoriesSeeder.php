<?php

namespace Database\Seeders;

use App\Models\UserStories;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserStoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        UserStories::factory()->count(1000)->create();
    }
}
