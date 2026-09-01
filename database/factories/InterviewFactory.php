<?php

namespace Database\Factories;

use App\Models\Interview;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Interview>
 */
class InterviewFactory extends Factory
{
    protected $model = Interview::class;

    public function definition(): array
    {
        return [
            'user_id'       => User::factory(),
            'individual_id' => IndividualFactory::new(),
            'draft'         => false,
            'text'          => fake()->paragraph(),
            'date'          => now()->timestamp,
            'intro'         => fake()->sentence(),
            'chapters'      => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['draft' => true]);
    }

    /**
     * Chapter links and their anchors are BBCode, and the pairing between them
     * is what the interview page depends on - see the [hotspot] tags in
     * CLAUDE.md.
     */
    public function withChapters(string $chapters, string $text): static
    {
        return $this->state(fn () => [
            'chapters' => $chapters,
            'text'     => $text,
        ]);
    }
}
