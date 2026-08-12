<?php

namespace Database\Factories;

use App\Models\MagazineIndexType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MagazineIndexType>
 */
class MagazineIndexTypeFactory extends Factory
{
    protected $model = MagazineIndexType::class;

    /**
     * Reference data - "Review", "Preview", "Tips" and so on - that the
     * migrations create the table for but never populate.
     */
    public function definition(): array
    {
        return [
            'name' => 'Review',
        ];
    }

    public function named(string $name): static
    {
        return $this->state(fn () => ['name' => $name]);
    }
}
