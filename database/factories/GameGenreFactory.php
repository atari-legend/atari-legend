<?php

namespace Database\Factories;

use App\Models\GameGenre;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GameGenre>
 */
class GameGenreFactory extends Factory
{
    protected $model = GameGenre::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'Shoot-em-up', 'Platform', 'Puzzle', 'Adventure', 'Racing', 'Strategy',
            ]),
        ];
    }
}
