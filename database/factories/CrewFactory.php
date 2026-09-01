<?php

namespace Database\Factories;

use App\Models\Crew;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Crew>
 */
class CrewFactory extends Factory
{
    protected $model = Crew::class;

    public function definition(): array
    {
        return [
            'name'    => fake()->unique()->lastName() . ' Crew',
            'logo'    => null,
            'history' => null,
        ];
    }
}
