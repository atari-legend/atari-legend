<?php

namespace Database\Factories;

use App\Models\ArticleComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ArticleComment>
 */
class ArticleCommentFactory extends Factory
{
    protected $model = ArticleComment::class;

    public function definition(): array
    {
        return [
            'article_id' => ArticleFactory::new(),
            'text'       => fake()->sentence(),
            'user_id'    => User::factory(),
            'created_at' => now(),
        ];
    }
}
