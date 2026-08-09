<?php

namespace Database\Factories;

use App\Models\Screenshot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Screenshot>
 */
class ScreenshotFactory extends Factory
{
    protected $model = Screenshot::class;

    /**
     * `imgext` is the whole row: the filename is derived from the id and the
     * extension by `Helper::filename()`, so nothing else is stored.
     */
    public function definition(): array
    {
        return [
            'imgext' => 'png',
        ];
    }
}
