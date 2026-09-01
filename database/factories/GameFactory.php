<?php

namespace Database\Factories;

use App\Models\Game;
use App\Models\Screenshot;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Game>
 */
class GameFactory extends Factory
{
    protected $model = Game::class;

    /**
     * `slug` is the only column with a unique index, and it is what the public
     * routes bind on, so it has to follow the name rather than be random.
     */
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->words(3, true));

        return [
            'name'                             => $name,
            'slug'                             => Str::slug($name),
            'game_series_id'                   => null,
            'port_id'                          => null,
            'game_progress_system_id'          => null,
            'number_players_on_same_machine'   => 1,
            'number_players_multiple_machines' => null,
            'multiplayer_type'                 => null,
            'multiplayer_hardware'             => null,
        ];
    }

    /**
     * Name the game explicitly, keeping the slug in step with it.
     */
    public function named(string $name): static
    {
        return $this->state(fn () => [
            'name' => $name,
            'slug' => Str::slug($name),
        ]);
    }

    /**
     * Attach a screenshot, which several listings use to decide whether a game
     * is worth showing.
     */
    public function withScreenshot(): static
    {
        return $this->afterCreating(function (Game $game) {
            $game->screenshots()->attach(Screenshot::factory()->create());
        });
    }

    public function withRelease(int $count = 1): static
    {
        return $this->afterCreating(function (Game $game) use ($count) {
            GameReleaseFactory::new()->count($count)->create(['game_id' => $game->getKey()]);
        });
    }
}
