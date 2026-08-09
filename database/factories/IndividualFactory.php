<?php

namespace Database\Factories;

use App\Models\Individual;
use App\Models\IndividualText;
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
     * Nearly every individual has an `individual_text` row even with nothing in
     * it, so the bio is a state rather than part of the definition - see
     * AdminStatisticsHelper::coverage(), which counts only non-empty profiles.
     */
    public function withBio(string $profile = 'Member of Dune.'): static
    {
        return $this->afterCreating(function (Individual $individual) use ($profile) {
            IndividualText::forceCreate([
                'ind_id'      => $individual->getKey(),
                'ind_profile' => $profile,
                'ind_imgext'  => null,
                'ind_email'   => null,
            ]);
        });
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
