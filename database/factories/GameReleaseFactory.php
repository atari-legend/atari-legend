<?php

namespace Database\Factories;

use App\Models\Game;
use App\Models\GameRelease;
use App\Models\GameReleaseAka;
use App\Models\GameReleaseMemoryEnhanced;
use App\Models\GameReleaseSystemEnhanced;
use App\Models\GameReleaseTosVersionIncompatibility;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GameRelease>
 */
class GameReleaseFactory extends Factory
{
    protected $model = GameRelease::class;

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
            'license'        => GameRelease::LICENCE_COMMERCIAL,
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
            'pub_dev_id' => PubDevFactory::new()->state(
                $name === null ? [] : ['pub_dev_name' => $name]
            ),
        ]);
    }

    public function crackedBy(?string $name = null): static
    {
        return $this->afterCreating(function (GameRelease $release) use ($name) {
            $release->crews()->attach(
                CrewFactory::new()->create($name === null ? [] : ['crew_name' => $name])
            );
        });
    }

    /**
     * The description prints the language `name`, so it has to follow the code
     * rather than stay at whatever the factory picked at random.
     */
    public function inLanguages(string ...$ids): static
    {
        return $this->afterCreating(function (GameRelease $release) use ($ids) {
            foreach ($ids as $id) {
                $release->languages()->attach(
                    LanguageFactory::new()->create(['id' => $id, 'name' => $id])
                );
            }
        });
    }

    public function inResolutions(string ...$names): static
    {
        return $this->afterCreating(function (GameRelease $release) use ($names) {
            foreach ($names as $name) {
                $release->resolutions()->attach(ResolutionFactory::new()->create(['name' => $name]));
            }
        });
    }

    public function withTrainer(string $name = 'Infinite lives'): static
    {
        return $this->afterCreating(function (GameRelease $release) use ($name) {
            $release->trainers()->attach(TrainerOptionFactory::new()->create(['name' => $name]));
        });
    }

    public function releasedIn(string ...$names): static
    {
        return $this->afterCreating(function (GameRelease $release) use ($names) {
            foreach ($names as $name) {
                $release->locations()->attach(LocationFactory::new()->create(['name' => $name]));
            }
        });
    }

    public function distributedBy(string ...$names): static
    {
        return $this->afterCreating(function (GameRelease $release) use ($names) {
            foreach ($names as $name) {
                $release->distributors()->attach(
                    PubDevFactory::new()->create(['pub_dev_name' => $name])
                );
            }
        });
    }

    public function alsoKnownAs(string $name, ?string $languageId = null): static
    {
        return $this->afterCreating(function (GameRelease $release) use ($name, $languageId) {
            GameReleaseAka::create([
                'game_release_id' => $release->getKey(),
                'name'            => $name,
                'language_id'     => $languageId === null
                    ? null
                    : LanguageFactory::new()->create(['id' => $languageId, 'name' => $languageId])->id,
            ]);
        });
    }

    public function hdInstallable(): static
    {
        return $this->state(fn () => ['hd_installable' => true]);
    }

    public function requiringMemory(string ...$names): static
    {
        return $this->afterCreating(function (GameRelease $release) use ($names) {
            foreach ($names as $name) {
                $release->memoryMinimums()->attach(MemoryFactory::new()->create(['name' => $name]));
            }
        });
    }

    public function incompatibleWithMemory(string ...$names): static
    {
        return $this->afterCreating(function (GameRelease $release) use ($names) {
            foreach ($names as $name) {
                $release->memoryIncompatibles()->attach(MemoryFactory::new()->create(['name' => $name]));
            }
        });
    }

    public function enhancedForSystem(string $system, ?string $enhancement = null): static
    {
        return $this->afterCreating(function (GameRelease $release) use ($system, $enhancement) {
            GameReleaseSystemEnhanced::create([
                'game_release_id' => $release->getKey(),
                'system_id'       => SystemFactory::new()->create(['name' => $system])->id,
                'enhancement_id'  => $enhancement === null
                    ? null
                    : EnhancementFactory::new()->create(['name' => $enhancement])->id,
            ]);
        });
    }

    public function enhancedForMemory(string $memory, ?string $enhancement = null): static
    {
        return $this->afterCreating(function (GameRelease $release) use ($memory, $enhancement) {
            GameReleaseMemoryEnhanced::create([
                'game_release_id' => $release->getKey(),
                'memory_id'       => MemoryFactory::new()->create(['name' => $memory])->id,
                'enhancement_id'  => $enhancement === null
                    ? null
                    : EnhancementFactory::new()->create(['name' => $enhancement])->id,
            ]);
        });
    }

    public function incompatibleWithSystems(string ...$names): static
    {
        return $this->afterCreating(function (GameRelease $release) use ($names) {
            foreach ($names as $name) {
                $release->systemIncompatibles()->attach(SystemFactory::new()->create(['name' => $name]));
            }
        });
    }

    public function incompatibleWithEmulators(string ...$names): static
    {
        return $this->afterCreating(function (GameRelease $release) use ($names) {
            foreach ($names as $name) {
                $release->emulatorIncompatibles()->attach(EmulatorFactory::new()->create(['name' => $name]));
            }
        });
    }

    public function incompatibleWithTos(string $version, ?string $languageId = null): static
    {
        return $this->afterCreating(function (GameRelease $release) use ($version, $languageId) {
            GameReleaseTosVersionIncompatibility::create([
                'game_release_id' => $release->getKey(),
                'tos_id'          => TosFactory::new()->create(['name' => $version])->id,
                'language_id'     => $languageId === null
                    ? null
                    : LanguageFactory::new()->create(['id' => $languageId, 'name' => $languageId])->id,
            ]);
        });
    }

    /**
     * The notes live on the pivot and are appended to the protection name in
     * the release description, so they are part of what a test asserts on.
     */
    public function copyProtectedBy(string $name, ?string $notes = null): static
    {
        return $this->afterCreating(function (GameRelease $release) use ($name, $notes) {
            $release->copyProtections()->attach(
                CopyProtectionFactory::new()->create(['name' => $name]),
                ['notes' => $notes]
            );
        });
    }

    public function diskProtectedBy(string $name, ?string $notes = null): static
    {
        return $this->afterCreating(function (GameRelease $release) use ($name, $notes) {
            $release->diskProtections()->attach(
                DiskProtectionFactory::new()->create(['name' => $name]),
                ['notes' => $notes]
            );
        });
    }

    /**
     * Disk protection 1 is the catch-all 'Yes', which the description renders
     * as 'an unknown scheme' rather than by name.
     */
    public function diskProtectedByUnknownScheme(): static
    {
        return $this->afterCreating(function (GameRelease $release) {
            $release->diskProtections()->attach(
                DiskProtectionFactory::new()->unknownScheme()->create(),
                ['notes' => null]
            );
        });
    }
}
