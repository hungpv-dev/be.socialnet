<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'phone' => $this->faker->phoneNumber,
            'avatar' => $this->faker->imageUrl(200, 200, 'people'),
            'cover_avatar' => $this->faker->imageUrl(800, 200, 'nature'),
            'authentication' => $this->faker->randomElement([1, 2]),
            'email_verified_at' => now(),
            'time_offline' => now(),
            'password' => Hash::make('password'), // Mật khẩu mã hóa
            'is_online' => $this->faker->boolean,
            'is_active' => $this->faker->boolean,
            'address' => $this->faker->address,
            'hometown' => $this->faker->city,
            'gender' => $this->faker->randomElement(['male', 'female', 'other']),
            'birthday' => $this->faker->date(),
            'relationship' => $this->faker->randomElement(['single', 'married', 'divorced', 'widowed']),
            'follower' => $this->faker->numberBetween(0, 1000),
            'friend_counts' => $this->faker->numberBetween(0, 500),
            'is_admin' => false,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     *
     * @return $this
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
