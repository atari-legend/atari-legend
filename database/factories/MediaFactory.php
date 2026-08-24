<?php

namespace Database\Factories;

use App\Models\Media;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Media>
 */
class MediaFactory extends Factory
{
    protected $model = Media::class;

    public function definition(): array
    {
        return [
            'game_release_id' => GameReleaseFactory::new(),
            'media_type_id'   => MediaTypeFactory::new(),
            'label'           => 'Disk 1',
        ];
    }
}
