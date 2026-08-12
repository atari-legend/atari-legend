<?php

namespace Database\Factories;

use App\Models\MenuDiskDump;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MenuDiskDump>
 */
class MenuDiskDumpFactory extends Factory
{
    protected $model = MenuDiskDump::class;

    /**
     * The dump row only describes the file; the file itself lives at
     * `zips/menus/{id}.zip` on the public disk. Tests that follow a download
     * have to put it there themselves, because the id is not known until the
     * row is created.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'format'  => 'STX',
            'sha512'  => hash('sha512', fake()->uuid()),
            'size'    => 819200,
        ];
    }

    public function inFormat(string $format): static
    {
        return $this->state(fn () => ['format' => $format]);
    }
}
