<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
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
            'twitch_id' => (string) fake()->unique()->numberBetween(10_000, 999_999_999),
            'twitch_login' => strtolower($login),
            'display_name' => $login,
            'avatar_url' => fake()->imageUrl(300, 300),
            'twitch_access_token' => Str::random(30),
            'twitch_refresh_token' => Str::random(50),
            'twitch_token_expires_at' => now()->addHours(4),
            'twitch_scopes' => config('services.twitch.scopes'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the user's Twitch access token has expired.
     */
    /**
     * A streamer who can edit the global game balance.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => ['is_admin' => true]);
    }

    public function withExpiredToken(): static
    {
        return $this->state(fn (array $attributes) => [
            'twitch_token_expires_at' => now()->subMinute(),
        ]);
    }
}
