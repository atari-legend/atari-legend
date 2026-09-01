<?php

namespace Database\Factories;

use App\Models\PubDev;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PubDev>
 */
class PubDevFactory extends Factory
{
    protected $model = PubDev::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
        ];
    }
}
