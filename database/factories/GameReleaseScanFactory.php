<?php

namespace Database\Factories;

use App\Models\GameReleaseScan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GameReleaseScan>
 */
class GameReleaseScanFactory extends Factory
{
    protected $model = GameReleaseScan::class;

    /**
     * `type` is one of `GameReleaseScan::TYPES` rather than free text - the game
     * page picks the box scan out of the set by matching on it, so a scan with
     * an arbitrary type is invisible there.
     */
    public function definition(): array
    {
        return [
            'game_release_id' => GameReleaseFactory::new(),
            'type'            => GameReleaseScan::TYPE_BOX_FRONT,
            'imgext'          => 'jpg',
            'notes'           => null,
        ];
    }

    public function ofType(string $type): static
    {
        return $this->state(fn () => ['type' => $type]);
    }
}
