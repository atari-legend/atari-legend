<?php

namespace Database\Factories;

use App\Models\NewsSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NewsSubmission>
 */
class NewsSubmissionFactory extends Factory
{
    protected $model = NewsSubmission::class;

    /**
     * `news_image_id` is NOT NULL DEFAULT 0 rather than a nullable foreign key:
     * zero means "no image", and the model has no relation for it.
     *
     * The model has no `$fillable`, which only works because factories create
     * unguarded. Anything else has to assign the attributes one by one.
     */
    public function definition(): array
    {
        return [
            'news_headline' => fake()->sentence(),
            'news_text'     => fake()->paragraph(),
            'news_image_id' => 0,
            'user_id'       => User::factory(),
            'news_date'     => time(),
        ];
    }
}
