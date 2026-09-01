<?php

namespace Database\Factories;

use App\Models\ArticleType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ArticleType>
 */
class ArticleTypeFactory extends Factory
{
    protected $model = ArticleType::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'Hardware', 'Coding', 'History', 'Interview special', 'Retrospective',
            ]),
        ];
    }
}
