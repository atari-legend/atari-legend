<?php

namespace Database\Factories;

use App\Models\MenuSet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MenuSet>
 */
class MenuSetFactory extends Factory
{
    protected $model = MenuSet::class;

    /**
     * `menus_sort` is constrained to 'asc' or 'desc' at the database level, so
     * it cannot be left to chance.
     */
    public function definition(): array
    {
        return [
            'name'       => fake()->unique()->lastName() . ' Menus',
            'menus_sort' => 'asc',
        ];
    }

    public function sortedDescending(): static
    {
        return $this->state(fn () => ['menus_sort' => 'desc']);
    }
}
