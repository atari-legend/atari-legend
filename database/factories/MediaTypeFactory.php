<?php

namespace Database\Factories;

use App\Models\MediaType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MediaType>
 */
class MediaTypeFactory extends Factory
{
    protected $model = MediaType::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['3.5" DD floppy disk', 'Cartridge', 'Hard disk']),
        ];
    }
}
