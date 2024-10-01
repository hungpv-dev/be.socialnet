<?php

namespace Database\Factories;

use App\Models\ChatRoom;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ChatRoom>
 */
class ChatRoomFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = ChatRoom::class;
    public function definition(): array
    {
        $numberA = $this->faker->numberBetween(1, 11);
        do{
            $numberB = $this->faker->numberBetween(1, 11);
        }while($numberA == $numberB);
        return [
            'chat_type_id' => $this->faker->numberBetween(1, 2),
            'name' => [
                $numberA => $this->faker->word,
                $numberB => $this->faker->word,
            ],
            'user' => [$numberA,$numberB],
            'admin' => [],
            'last_remove' => [
                $numberA => $this->faker->dateTimeBetween('-3 days', 'now')->format('Y-m-d H:i:s'),
                $numberB => $this->faker->dateTimeBetween('-3 days', 'now')->format('Y-m-d H:i:s'),
            ],
            'last_active' => [
                $numberA => $this->faker->dateTimeBetween('-30 days', 'now')->format('Y-m-d H:i:s'),
                $numberB => $this->faker->dateTimeBetween('-30 days', 'now')->format('Y-m-d H:i:s'),
            ],
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
