<?php

namespace Database\Factories;

use App\Models\DiskProtection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DiskProtection>
 */
class DiskProtectionFactory extends Factory
{
    protected $model = DiskProtection::class;

    /**
     * Row 1 is reserved: ReleaseDescriptionHelper treats disk protection id 1
     * as 'an unknown scheme' regardless of its name. Left to autoincrement, the
     * first protection a test creates would land on id 1 and be described as
     * unknown, which is not what the test asked for. So named protections start
     * at 2, and `unknownScheme()` is the only way to get id 1.
     */
    public function definition(): array
    {
        return [
            'id'   => max(DiskProtection::query()->max('id') ?? 1, 1) + 1,
            'name' => fake()->randomElement(['Rob Northen Copylock', 'Macrodos', 'Speedlock']),
        ];
    }

    /**
     * Row 1 is the catch-all 'Yes', meaning the media is protected but nobody
     * has identified the scheme. ReleaseDescriptionHelper special-cases it by
     * id, so tests that exercise that branch need this exact row.
     */
    public function unknownScheme(): static
    {
        return $this->state(fn () => ['id' => 1, 'name' => 'Yes']);
    }
}
