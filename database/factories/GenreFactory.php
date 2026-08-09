<?php

namespace Database\Factories;

use App\Models\Genre;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Genre>
 */
class GenreFactory extends Factory
{
    protected $model = Genre::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'Shoot-em-up', 'Platform', 'Puzzle', 'Adventure', 'Racing', 'Strategy',
            ]),
        ];
    }
}
