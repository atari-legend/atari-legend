<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\Comment;
use App\Models\Game;
use App\Models\Interview;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Comment>
 */
class CommentFactory extends Factory
{
    protected $model = Comment::class;

    /**
     * `timestamp` is a unix timestamp held in a varchar column, which is what
     * the controllers write - anything date-shaped there sorts wrongly.
     *
     * A bare comment is attached to nothing, and `type`, `target` and
     * `target_id` all throw in that state because each walks the four pivot
     * tables looking for a match. Use one of the `on*()` states for anything
     * that reads those.
     */
    public function definition(): array
    {
        return [
            'comment'   => fake()->sentence(),
            'user_id'   => User::factory(),
            'timestamp' => time(),
        ];
    }

    public function onGame(?Game $game = null): static
    {
        return $this->afterCreating(
            fn (Comment $comment) => $comment->games()->attach($game ?? Game::factory()->create())
        );
    }

    public function onReview(?Review $review = null): static
    {
        return $this->afterCreating(
            fn (Comment $comment) => $comment->reviews()->attach($review ?? Review::factory()->create())
        );
    }

    public function onArticle(?Article $article = null): static
    {
        return $this->afterCreating(
            fn (Comment $comment) => $comment->articles()->attach($article ?? Article::factory()->create())
        );
    }

    public function onInterview(?Interview $interview = null): static
    {
        return $this->afterCreating(
            fn (Comment $comment) => $comment->interviews()->attach($interview ?? Interview::factory()->create())
        );
    }
}
