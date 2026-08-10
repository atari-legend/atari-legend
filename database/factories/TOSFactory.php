<?php

namespace Database\Factories;

use App\Models\TOS;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TOS>
 */
class TOSFactory extends Factory
{
    protected $model = TOS::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['1.00', '1.02', '1.04', '2.06']),
        ];
    }
}
