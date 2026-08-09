<?php

namespace Database\Factories;

use App\Models\Resolution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Resolution>
 */
class ResolutionFactory extends Factory
{
    protected $model = Resolution::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Low', 'Medium', 'High']),
        ];
    }
}
