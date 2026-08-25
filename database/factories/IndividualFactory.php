<?php

namespace Database\Factories;

use App\Models\Individual;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Individual>
 */
class IndividualFactory extends Factory
{
    protected $model = Individual::class;

    public function definition(): array
    {
        return [
            'ind_name' => fake()->unique()->name(),
        ];
    }

    /**
     * Most individuals have no bio at all, so it is a state rather than part
     * of the definition - see AdminStatisticsHelper::coverage(), which counts
     * only non-empty profiles.
     */
    public function withBio(string $profile = 'Member of Dune.'): static
    {
        return $this->state(fn () => ['ind_profile' => $profile]);
    }

    public function nicknamed(string ...$nicks): static
    {
        return $this->afterCreating(function (Individual $individual) use ($nicks) {
            foreach ($nicks as $nick) {
                $individual->nicknames()->attach(
                    Individual::factory()->create(['ind_name' => $nick])
                );
            }
        });
    }
}
