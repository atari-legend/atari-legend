<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Website;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Website>
 */
class WebsiteFactory extends Factory
{
    protected $model = Website::class;

    /**
     * `inactive` is what the links page filters on: a submitted link stays
     * hidden until an administrator clears the flag.
     */
    public function definition(): array
    {
        return [
            'name'   => fake()->unique()->company(),
            'url'    => fake()->url(),
            'date'   => now()->timestamp,
            'user_id'        => User::factory(),
            'imgext' => null,
            'count'  => 0,
            'rate_number'    => 1,
            'rate_score'     => 5,
            'inactive'       => false,
            'description'    => fake()->sentence(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['inactive' => true]);
    }

    public function inCategory(?string $name = null): static
    {
        return $this->afterCreating(function (Website $website) use ($name) {
            $website->categories()->attach(
                WebsiteCategoryFactory::new()->create(
                    $name === null ? [] : ['name' => $name]
                )
            );
        });
    }
}
