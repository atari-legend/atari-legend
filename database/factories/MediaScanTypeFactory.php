<?php

namespace Database\Factories;

use App\Models\MediaScanType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MediaScanType>
 */
class MediaScanTypeFactory extends Factory
{
    protected $model = MediaScanType::class;

    /**
     * Reference data that the migrations create the table for but never
     * populate, so tests have to make their own.
     */
    public function definition(): array
    {
        return [
            'name' => MediaScanType::TYPE_OTHER,
        ];
    }

    public function named(string $name): static
    {
        return $this->state(fn () => ['name' => $name]);
    }
}
