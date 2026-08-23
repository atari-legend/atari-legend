<?php

namespace Database\Factories;

use App\Models\ReleaseScan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ReleaseScan>
 */
class ReleaseScanFactory extends Factory
{
    protected $model = ReleaseScan::class;

    /**
     * `type` is one of `ReleaseScan::TYPES` rather than free text - the game
     * page picks the box scan out of the set by matching on it, so a scan with
     * an arbitrary type is invisible there.
     */
    public function definition(): array
    {
        return [
            'game_release_id' => GameReleaseFactory::new(),
            'type'            => ReleaseScan::TYPE_BOX_FRONT,
            'imgext'          => 'jpg',
            'notes'           => null,
        ];
    }

    public function ofType(string $type): static
    {
        return $this->state(fn () => ['type' => $type]);
    }
}
