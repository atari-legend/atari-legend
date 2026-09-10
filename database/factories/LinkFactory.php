<?php

namespace Database\Factories;

use App\Models\Link;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Link>
 */
class LinkFactory extends Factory
{
    protected $model = Link::class;

    /**
     * `inactive` is what the links page filters on: a submitted link stays
     * hidden until an administrator clears the flag.
     */
    public function definition(): array
    {
        return [
            'name'         => fake()->unique()->company(),
            'url'          => fake()->url(),
            'created_at'   => now(),
            'user_id'      => User::factory(),
            'imgext'       => null,
            'view_count'   => 0,
            'rating_count' => 1,
            'rating_total' => 5,
            'inactive'     => false,
            'description'  => fake()->sentence(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['inactive' => true]);
    }

    public function inCategory(?string $name = null): static
    {
        return $this->afterCreating(function (Link $link) use ($name) {
            $link->categories()->attach(
                CategoryFactory::new()->create(
                    $name === null ? [] : ['name' => $name]
                )
            );
        });
    }
}
