<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Location>
 */
class LocationFactory extends Factory
{
    protected $model = Location::class;

    /**
     * `type` is constrained to 'Continent' or 'Country' at the database level.
     */
    public function definition(): array
    {
        return [
            'name'           => fake()->unique()->country(),
            'type'           => 'Country',
            'continent_code' => null,
            'country_iso2'   => null,
            'country_iso3'   => null,
        ];
    }

    public function continent(): static
    {
        return $this->state(fn () => [
            'name'           => 'Europe',
            'type'           => 'Continent',
            'continent_code' => 'EU',
        ]);
    }
}
