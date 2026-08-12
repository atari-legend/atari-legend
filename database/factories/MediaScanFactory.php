<?php

namespace Database\Factories;

use App\Models\MediaScan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MediaScan>
 */
class MediaScanFactory extends Factory
{
    protected $model = MediaScan::class;

    /**
     * A scan of one side of one piece of media - a disk label, a cartridge
     * sticker. `media_scan_type_id` is nullable in the schema but the admin
     * always sets one, so the factory does too.
     */
    public function definition(): array
    {
        return [
            'media_id'           => MediaFactory::new(),
            'media_scan_type_id' => MediaScanTypeFactory::new(),
            'imgext'             => 'jpg',
        ];
    }
}
