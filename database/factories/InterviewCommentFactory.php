<?php

namespace Database\Factories;

use App\Models\InterviewComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InterviewComment>
 */
class InterviewCommentFactory extends Factory
{
    protected $model = InterviewComment::class;

    public function definition(): array
    {
        return [
            'interview_id' => InterviewFactory::new(),
            'text'         => fake()->sentence(),
            'user_id'      => User::factory(),
            'created_at'   => now(),
        ];
    }
}
