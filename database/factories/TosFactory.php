<?php

namespace Database\Factories;

use App\Models\Tos;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tos>
 */
class TosFactory extends Factory
{
    protected $model = Tos::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['1.00', '1.02', '1.04', '2.06']),
        ];
    }
}
