<?php

namespace Database\Factories;

use App\Models\GameComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GameComment>
 */
class GameCommentFactory extends Factory
{
    protected $model = GameComment::class;

    public function definition(): array
    {
        return [
            'game_id'    => GameFactory::new(),
            'text'       => fake()->sentence(),
            'user_id'    => User::factory(),
            'created_at' => now(),
        ];
    }
}
