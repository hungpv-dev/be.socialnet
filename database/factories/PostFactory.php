<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        return [
            'user_id' => User::inRandomOrder()->first()->id,
            'content' => $this->faker->text(200),
            'data' => json_encode([
                'images' => $this->faker->imageUrl(),
            ]),
            'share_id' => null,
            'status' => $this->faker->randomElement(['public', 'friend', 'private']),
            'is_active' => $this->faker->boolean(),
            'type' => $this->faker->randomElement(['avatar', 'background', 'post']),
            'emoji_count' => $this->faker->numberBetween(0, 100),
            'comment_count' => $this->faker->numberBetween(0, 50),
            'share_count' => $this->faker->numberBetween(0, 30),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
