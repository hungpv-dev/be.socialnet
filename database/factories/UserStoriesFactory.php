<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Story;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserStories>
 */
class UserStoriesFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::inRandomOrder()->first()->id,
            'story_id' => Story::inRandomOrder()->first()->id, // Lấy ngẫu nhiên một ID từ bảng stories
            'seen' => false,
            'emoji' => 'haha',
        ];
    }
}
