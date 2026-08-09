<?php

namespace Database\Factories;

use App\Http\Controllers\MenuSetController;
use App\Models\MenuDisk;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MenuDisk>
 */
class MenuDiskFactory extends Factory
{
    protected $model = MenuDisk::class;

    /**
     * Disks default to intact. The menu set listing counts anything else as
     * missing, so `damaged()` is the state that makes those counts move.
     *
     * The conditions themselves are reference data shipped by the migrations,
     * which is why this refers to one by id rather than creating one.
     */
    public function definition(): array
    {
        return [
            'menu_id'                  => MenuFactory::new(),
            'part'                     => 'A',
            'scrolltext'               => null,
            'donated_by_individual_id' => null,
            'menu_disk_condition_id'   => MenuSetController::INTACT_CONDITION_ID,
            'menu_disk_dump_id'        => null,
            'notes'                    => null,
        ];
    }

    public function damaged(): static
    {
        return $this->state(fn () => ['menu_disk_condition_id' => 1]);
    }

    public function withScrolltext(string $text): static
    {
        return $this->state(fn () => ['scrolltext' => $text]);
    }
}
