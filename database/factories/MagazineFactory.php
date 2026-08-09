<?php

namespace Database\Factories;

use App\Models\Magazine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Magazine>
 */
class MagazineFactory extends Factory
{
    protected $model = Magazine::class;

    public function definition(): array
    {
        return [
            'name'        => fake()->unique()->words(2, true),
            'location_id' => null,
        ];
    }
}
