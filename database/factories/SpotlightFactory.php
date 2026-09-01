<?php

namespace Database\Factories;

use App\Models\Spotlight;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Spotlight>
 */
class SpotlightFactory extends Factory
{
    protected $model = Spotlight::class;

    /**
     * The image is keyed on `screenshot_id`, not on the spotlight's own id, so
     * a spotlight without a screenshot has no image at all - which is a state
     * the home page has to survive, hence `withoutScreenshot()`.
     */
    public function definition(): array
    {
        return [
            'screenshot_id' => ScreenshotFactory::new(),
            'text'          => fake()->sentence(),
            'link'          => 'https://example.org/',
        ];
    }

    public function withoutScreenshot(): static
    {
        return $this->state(fn () => ['screenshot_id' => null]);
    }
}
