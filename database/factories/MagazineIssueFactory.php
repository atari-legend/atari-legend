<?php

namespace Database\Factories;

use App\Models\MagazineIssue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MagazineIssue>
 */
class MagazineIssueFactory extends Factory
{
    protected $model = MagazineIssue::class;

    public function definition(): array
    {
        return [
            'magazine_id'    => MagazineFactory::new(),
            'issue'          => fake()->unique()->numberBetween(1, 200),
            'label'          => null,
            'imgext'         => 'jpg',
            'published'      => fake()->dateTimeBetween('1987-01-01', '1993-12-31')->format('Y-m-d'),
            'archiveorg_url' => null,
            'alternate_url'  => null,
            'page_count'     => 64,
            'circulation'    => null,
        ];
    }
}
