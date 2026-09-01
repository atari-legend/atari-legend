<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Article>
 */
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        return [
            'user_id'         => User::factory(),
            'article_type_id' => ArticleTypeFactory::new(),
            'draft'           => false,
            'title'   => fake()->sentence(4),
            'text'    => fake()->paragraph(),
            'date'    => now()->timestamp,
            'intro'   => fake()->sentence(),
        ];
    }

    public function titled(string $title): static
    {
        return $this->state(fn () => ['title' => $title]);
    }

    public function draft(): static
    {
        return $this->state(fn () => ['draft' => true]);
    }
}
