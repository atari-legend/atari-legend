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

    /**
     * `news_date` is a unix timestamp in an integer column, as on the legacy
     * site. The home page orders on it, so it has to be a real value.
     */
    public function definition(): array
    {
        return [
            'news_headline' => fake()->sentence(5),
            'news_text'     => fake()->paragraph(),
            'news_image_id' => null,
            'user_id'       => User::factory(),
            'news_date'     => now()->timestamp,
        ];
    }
}
