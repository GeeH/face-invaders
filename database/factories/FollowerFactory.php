<?php

namespace Database\Factories;

use App\Models\Follower;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Follower>
 */
class FollowerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $login = fake()->unique()->userName();

        return [
            'user_id' => User::factory(),
            'provider' => 'fake',
            'provider_user_id' => (string) fake()->unique()->numberBetween(10_000, 999_999_999),
            'username' => strtolower($login),
            'display_name' => $login,
            'avatar_url' => fake()->imageUrl(300, 300),
            'followed_at' => fake()->dateTimeBetween('-3 years'),
            'synced_at' => now(),
        ];
    }
}
