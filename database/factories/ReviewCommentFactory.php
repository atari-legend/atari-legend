<?php

namespace Database\Factories;

use App\Models\ReviewComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ReviewComment>
 */
class ReviewCommentFactory extends Factory
{
    protected $model = ReviewComment::class;

    public function definition(): array
    {
        return [
            'review_id'  => ReviewFactory::new(),
            'text'       => fake()->sentence(),
            'user_id'    => User::factory(),
            'created_at' => now(),
        ];
    }
}
