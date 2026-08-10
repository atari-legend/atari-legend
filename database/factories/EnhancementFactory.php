<?php

namespace Database\Factories;

use App\Models\Enhancement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Enhancement>
 */
class EnhancementFactory extends Factory
{
    protected $model = Enhancement::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Graphics', 'Sound', 'Speed']),
        ];
    }
}
