<?php

namespace Database\Factories;

use App\Models\Menu;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Menu>
 */
class MenuFactory extends Factory
{
    protected $model = Menu::class;

    public function definition(): array
    {
        return [
            'number'      => fake()->unique()->numberBetween(1, 500),
            'issue'       => null,
            'date'        => null,
            'version'     => '1.0',
            'menu_set_id' => MenuSetFactory::new(),
        ];
    }
}
