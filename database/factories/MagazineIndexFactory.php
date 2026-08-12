<?php

namespace Database\Factories;

use App\Models\MagazineIndex;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MagazineIndex>
 */
class MagazineIndexFactory extends Factory
{
    protected $model = MagazineIndex::class;

    /**
     * One entry in an issue's index. The thing the entry is about is one of
     * `game_id`, `menu_software_id` or `individual_id` - all three are
     * nullable, and an entry that points at none of them is just a title, which
     * is a shape the index editor allows.
     */
    public function definition(): array
    {
        return [
            'magazine_issue_id'      => MagazineIssueFactory::new(),
            'magazine_index_type_id' => MagazineIndexTypeFactory::new(),
            'game_id'                => null,
            'menu_software_id'       => null,
            'individual_id'          => null,
            'title'                  => fake()->sentence(3),
            'score'                  => null,
            'page'                   => fake()->numberBetween(1, 96),
        ];
    }

    public function forGame(): static
    {
        return $this->state(fn () => ['game_id' => GameFactory::new(), 'score' => '85%']);
    }

    public function forSoftware(): static
    {
        return $this->state(fn () => ['menu_software_id' => MenuSoftwareFactory::new()]);
    }

    public function forIndividual(): static
    {
        return $this->state(fn () => ['individual_id' => IndividualFactory::new()]);
    }
}
