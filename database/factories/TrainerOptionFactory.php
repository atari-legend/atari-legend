<?php

namespace Database\Factories;

use App\Models\TrainerOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TrainerOption>
 */
class TrainerOptionFactory extends Factory
{
    protected $model = TrainerOption::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Infinite lives', 'Infinite energy', 'Level skip']),
        ];
    }
}
