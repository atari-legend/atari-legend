<?php

namespace Database\Factories;

use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Review>
 */
class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition(): array
    {
        return [
            'game_id'      => GameFactory::new(),
            'user_id'      => User::factory(),
            'text'         => fake()->paragraph(),
            'published_at' => now(),
            'submission'   => Review::REVIEW_PUBLISHED,
            'draft'        => false,
        ];
    }

    /**
     * Drafts are hidden from the public site by the `nondraft` middleware.
     */
    public function draft(): static
    {
        return $this->state(fn () => ['draft' => true]);
    }

    /**
     * A review nobody has published yet - what the public submission form
     * creates.
     */
    public function unpublished(): static
    {
        return $this->state(fn () => ['submission' => Review::REVIEW_UNPUBLISHED]);
    }

    /**
     * Every review the site renders belongs to a game and has a score, so most
     * tests want `ReviewFactory::new()->forGame($game)->scored()`.
     */
    public function forGame(?int $gameId = null): static
    {
        return $this->state(fn () => ['game_id' => $gameId ?? GameFactory::new()]);
    }

    public function scored(int $graphics = 4, int $sound = 4, int $gameplay = 4, int $overall = 4): static
    {
        return $this->state(fn () => [
            'graphics' => $graphics,
            'sound'    => $sound,
            'gameplay' => $gameplay,
            'overall'  => $overall,
        ]);
    }
}
