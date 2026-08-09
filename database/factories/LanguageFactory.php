<?php

namespace Database\Factories;

use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Language>
 */
class LanguageFactory extends Factory
{
    protected $model = Language::class;

    /**
     * `language.id` is the ISO code, not a generated integer, so callers
     * normally pass it: `LanguageFactory::new()->create(['id' => 'fr'])`.
     */
    public function definition(): array
    {
        $code = fake()->unique()->languageCode();

        return [
            'id'   => $code,
            'name' => $code,
        ];
    }
}
