<?php

namespace Database\Factories;

use App\Models\PublisherDeveloper;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PublisherDeveloper>
 */
class PublisherDeveloperFactory extends Factory
{
    protected $model = PublisherDeveloper::class;

    public function definition(): array
    {
        return [
            'pub_dev_name' => fake()->unique()->company(),
        ];
    }
}
