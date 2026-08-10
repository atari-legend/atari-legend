<?php

namespace Database\Factories;

use App\Models\System;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\System>
 */
class SystemFactory extends Factory
{
    protected $model = System::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['STE', 'Mega ST', 'TT', 'Falcon']),
        ];
    }
}
