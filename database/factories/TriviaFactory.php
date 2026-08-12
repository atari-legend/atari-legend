<?php

namespace Database\Factories;

use App\Models\Trivia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Trivia>
 */
class TriviaFactory extends Factory
{
    protected $model = Trivia::class;

    public function definition(): array
    {
        return [
            'trivia_text' => fake()->sentence(),
        ];
    }
}
