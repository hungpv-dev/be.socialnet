<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Notification>
 */
class NotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $isRead = $this->faker->boolean();
        return [
            'id' => $this->faker->uuid(),
            'type' => $this->faker->randomElement(['App\Notifications\OrderShipped', 'App\Notifications\NewMessage']),
            'notifiable_id' => $this->faker->randomNumber(),
            'notifiable_type' => $this->faker->randomElement(['App\Models\User']),
            'data' => json_encode([
                'message' => $this->faker->sentence(),
                'url' => $this->faker->url(),
            ]),
            'is_seen' => $isRead ? true : $this->faker->boolean(),
            'is_read' => $isRead,
        ];
    }
}
