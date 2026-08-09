<?php

namespace Database\Factories;

use App\Models\Game;
use App\Models\Release;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Release>
 */
class ReleaseFactory extends Factory
{
    protected $model = Release::class;

    /**
     * A release with no publisher, no crews and no system detail: the emptiest
     * release the site will still render. States below add each part, so a test
     * only pays for the detail it asserts on.
     */
    public function definition(): array
    {
        return [
            'game_id'        => Game::factory(),
            'name'           => 'Original',
            'date'           => fake()->dateTimeBetween('1985-01-01', '1995-12-31')->format('Y-m-d'),
            'license'        => Release::LICENCE_COMMERCIAL,
            'type'           => null,
            'pub_dev_id'     => null,
            'hd_installable' => false,
            'status'         => null,
            'notes'          => null,
        ];
    }

    /**
     * Releases with no date sort and label differently - `getYearAttribute()`
     * returns '[no date]' rather than a year.
     */
    public function undated(): static
    {
        return $this->state(fn () => ['date' => null]);
    }

    public function publishedBy(?string $name = null): static
    {
        return $this->state(fn () => [
            'pub_dev_id' => PublisherDeveloperFactory::new()->state(
                $name === null ? [] : ['pub_dev_name' => $name]
            ),
        ]);
    }

    public function crackedBy(?string $name = null): static
    {
        return $this->afterCreating(function (Release $release) use ($name) {
            $release->crews()->attach(
                CrewFactory::new()->create($name === null ? [] : ['crew_name' => $name])
            );
        });
    }

    public function inLanguages(string ...$ids): static
    {
        return $this->afterCreating(function (Release $release) use ($ids) {
            foreach ($ids as $id) {
                $release->languages()->attach(LanguageFactory::new()->create(['id' => $id]));
            }
        });
    }

    public function inResolutions(string ...$names): static
    {
        return $this->afterCreating(function (Release $release) use ($names) {
            foreach ($names as $name) {
                $release->resolutions()->attach(ResolutionFactory::new()->create(['name' => $name]));
            }
        });
    }

    public function withTrainer(string $name = 'Infinite lives'): static
    {
        return $this->afterCreating(function (Release $release) use ($name) {
            $release->trainers()->attach(TrainerFactory::new()->create(['name' => $name]));
        });
    }
}
