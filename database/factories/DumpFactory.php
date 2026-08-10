<?php

namespace Database\Factories;

use App\Models\Dump;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Dump>
 */
class DumpFactory extends Factory
{
    protected $model = Dump::class;

    /**
     * `media_id` and `user_id` are both NOT NULL - a dump always belongs to a
     * piece of media and records who uploaded it.
     */
    public function definition(): array
    {
        return [
            'media_id'      => MediaFactory::new(),
            'user_id'       => User::factory(),
            'format'        => 'STX',
            'sha512'        => hash('sha512', fake()->uuid()),
            'date'          => now()->timestamp,
            'size'          => 819200,
            'info'          => null,
            'track_picture' => false,
        ];
    }

    public function inFormat(string $format): static
    {
        return $this->state(fn () => ['format' => $format]);
    }
}
