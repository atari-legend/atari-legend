<?php

namespace Database\Factories;

use App\Models\Emulator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Emulator>
 */
class EmulatorFactory extends Factory
{
    protected $model = Emulator::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Hatari', 'Steem', 'SainT']),
        ];
    }
}
