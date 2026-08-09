<?php

namespace Database\Factories;

use App\Models\CopyProtection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CopyProtection>
 */
class CopyProtectionFactory extends Factory
{
    protected $model = CopyProtection::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Manual lookup', 'Code wheel', 'Dongle']),
        ];
    }
}
