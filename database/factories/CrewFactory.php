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
            'crew_name'    => fake()->unique()->lastName() . ' Crew',
            'crew_logo'    => null,
            'crew_history' => null,
        ];
    }
}
