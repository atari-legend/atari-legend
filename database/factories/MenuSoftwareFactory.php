<?php

namespace Database\Factories;

use App\Models\MenuSoftware;
use App\Models\MenuSoftwareContentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MenuSoftware>
 */
class MenuSoftwareFactory extends Factory
{
    protected $model = MenuSoftware::class;

    /**
     * Content types are reference data the migrations ship, so reuse one rather
     * than adding an eighth.
     */
    public function definition(): array
    {
        return [
            'name'                          => fake()->unique()->words(2, true),
            'demozoo_id'                    => null,
            'menu_software_content_type_id' => MenuSoftwareContentType::query()->value('id')
                ?? MenuSoftwareContentType::create(['name' => 'Demo'])->getKey(),
        ];
    }

    public function named(string $name): static
    {
        return $this->state(fn () => ['name' => $name]);
    }
}
