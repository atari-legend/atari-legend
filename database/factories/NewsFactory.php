<?php

namespace Database\Factories;

use App\Models\News;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\News>
 */
class NewsFactory extends Factory
{
    protected $model = News::class;

    public function definition(): array
    {
        return [
            'headline'      => fake()->sentence(5),
            'text'          => fake()->paragraph(),
            'news_image_id' => null,
            'user_id'       => User::factory(),
            'published_at'  => now(),
        ];
    }
}
