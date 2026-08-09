<?php

namespace Database\Factories;

use App\Models\WebsiteCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WebsiteCategory>
 */
class WebsiteCategoryFactory extends Factory
{
    protected $model = WebsiteCategory::class;

    public function definition(): array
    {
        return [
            'website_category_name' => fake()->unique()->words(2, true),
        ];
    }
}
