<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * Every column of the legacy `users` table is set, even the ones nothing
     * reads. Models are strict about missing attributes, and `actingAs()`
     * clears `wasRecentlyCreated`, so a column the factory leaves out throws as
     * soon as a view touches it.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'userid'            => fake()->unique()->userName(),
            'email'             => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            // Legacy credentials, only set once a user changes their password
            'sha512_password'   => null,
            'salt'              => null,
            'remember_token'    => Str::random(10),
            'permission'        => User::PERMISSION_USER,
            'inactive'          => User::ACTIVE,
            // Unix timestamps held in varchar columns, as the legacy site wrote
            // them. `OnlineUsers` compares these numerically.
            'join_date'         => (string) now()->timestamp,
            'last_visit'        => (string) now()->timestamp,
            'avatar_ext'        => null,
            'user_website'      => null,
            'user_fb'           => null,
            'user_twitter'      => null,
            'user_af'           => null,
            'karma'             => 0,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user is an administrator.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'permission' => User::PERMISSION_ADMIN,
        ]);
    }
}
