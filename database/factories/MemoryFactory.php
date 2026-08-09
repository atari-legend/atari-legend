<?php

namespace Database\Factories;

use App\Models\Memory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Memory>
 */
class MemoryFactory extends Factory
{
    protected $model = Memory::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['512 KB', '1 MB', '2 MB', '4 MB']),
        ];
    }
}
