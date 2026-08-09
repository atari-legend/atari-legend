<?php

namespace Database\Factories;

use App\Models\Review;
use App\Models\ReviewScore;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Review>
 */
class ReviewFactory extends Factory
{
    protected $model = Review::class;

    /**
     * `review_date` is a unix timestamp in an integer column, the way the
     * legacy site wrote it, not a datetime.
     */
    public function definition(): array
    {
        return [
            'user_id'     => User::factory(),
            'review_text' => fake()->paragraph(),
            'review_date' => now()->timestamp,
            'review_edit' => Review::REVIEW_PUBLISHED,
            'draft'       => false,
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
        return $this->state(fn () => ['review_edit' => Review::REVIEW_UNPUBLISHED]);
    }

    /**
     * Every review the site renders belongs to a game and has a score, so most
     * tests want `ReviewFactory::new()->forGame($game)->scored()`.
     */
    public function forGame(?int $gameId = null): static
    {
        return $this->afterCreating(function (Review $review) use ($gameId) {
            $review->games()->attach($gameId ?? GameFactory::new()->create()->getKey());
        });
    }

    public function scored(int $graphics = 4, int $sound = 4, int $gameplay = 4, int $overall = 4): static
    {
        return $this->afterCreating(function (Review $review) use ($graphics, $sound, $gameplay, $overall) {
            $score = new ReviewScore();
            $score->review_graphics = $graphics;
            $score->review_sound = $sound;
            $score->review_gameplay = $gameplay;
            $score->review_overall = $overall;

            $review->score()->save($score);
        });
    }
}
