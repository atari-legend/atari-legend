<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\ArticleText;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Article>
 */
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    /**
     * As with interviews, the title and body live in a separate text row that
     * every view assumes exists.
     */
    public function definition(): array
    {
        return [
            'user_id'         => User::factory(),
            'article_type_id' => ArticleTypeFactory::new(),
            'draft'           => false,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Article $article) {
            if ($article->texts()->exists()) {
                return;
            }

            // forceCreate: ArticleText does not list article_id as fillable
            ArticleText::forceCreate([
                'article_id'    => $article->getKey(),
                'article_title' => fake()->sentence(4),
                'article_text'  => fake()->paragraph(),
                'article_date'  => now()->timestamp,
                'article_intro' => fake()->sentence(),
            ]);
        });
    }

    public function titled(string $title): static
    {
        return $this->afterCreating(function (Article $article) use ($title) {
            $article->texts()->first()->update(['article_title' => $title]);
        });
    }

    public function draft(): static
    {
        return $this->state(fn () => ['draft' => true]);
    }
}
