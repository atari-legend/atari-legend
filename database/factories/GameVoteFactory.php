<?php

namespace Database\Factories;

use App\Models\GameVote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GameVote>
 */
class GameVoteFactory extends Factory
{
    protected $model = GameVote::class;

    /**
     * Votes keep timestamps, and the game page prints how long ago the visitor
     * cast theirs - so a row written without them breaks the page. Going
     * through the model rather than inserting by hand is what keeps them set.
     */
    public function definition(): array
    {
        return [
            'game_id' => GameFactory::new(),
            'user_id' => User::factory(),
            'score'   => fake()->numberBetween(0, 4),
        ];
    }

    public function scored(int $score): static
    {
        return $this->state(fn () => ['score' => $score]);
    }
}
