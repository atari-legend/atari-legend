<?php

namespace Database\Factories;

use App\Models\Sndh;
use App\Models\SndhArchive;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sndh>
 */
class SndhFactory extends Factory
{
    protected $model = Sndh::class;

    /**
     * The key is the tune's path inside the SNDH archive, not a generated
     * integer.
     *
     * The migrations ship the real archive, so reuse whichever one is there
     * rather than adding another.
     */
    public function definition(): array
    {
        return [
            'id'              => 'Musicians/' . fake()->unique()->lastName() . '/tune.sndh',
            'sndh_archive_id' => SndhArchive::query()->value('id')
                ?? SndhArchive::create(['id' => 'main', 'download_url' => 'https://example.org'])->getKey(),
            'title'           => fake()->words(2, true),
            'composer'        => fake()->name(),
            'ripper'          => null,
            'subtunes'        => 1,
            'default_subtune' => 1,
            'year'            => fake()->numberBetween(1985, 1995),
        ];
    }

    /**
     * A tune with several subtunes is listed once per subtune on a game page.
     */
    public function withSubtunes(int $count): static
    {
        return $this->state(fn () => ['subtunes' => $count]);
    }
}
