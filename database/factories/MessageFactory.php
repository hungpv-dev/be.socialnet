<?php

namespace Database\Factories;

use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Message>
 */
class MessageFactory extends Factory
{
    protected $model = Message::class;
    public function definition(): array
    {
        $isSeenCount = $this->faker->boolean(70) ? $this->faker->numberBetween(1, 5) : 0;
        $flaggedCount = $this->faker->boolean(70) ? $this->faker->numberBetween(1, 5) : 0;
        return [
            'chat_room_id' => \App\Models\ChatRoom::inRandomOrder()->first()->id,
            'body' => $this->faker->text(),
            'is_seen' => $isSeenCount ? $this->faker->randomElements(range(1, 11), $isSeenCount) : [], 
            'flagged' => $flaggedCount ? $this->faker->randomElements(range(1, 11), $flaggedCount) : [], 
            'files' => $this->faker->boolean(50) ? [$this->faker->imageUrl()] : [],
            'reply_to' => $this->faker->boolean(50) ? 
            optional(\App\Models\Message::inRandomOrder()->first())->id : null,
            'user_id' => \App\Models\User::inRandomOrder()->first()->id
        ];
    }
}
