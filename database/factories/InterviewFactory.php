<?php

namespace Database\Factories;

use App\Models\Interview;
use App\Models\InterviewText;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Interview>
 */
class InterviewFactory extends Factory
{
    protected $model = Interview::class;

    /**
     * The body lives in `interview_text`, and every view reaches for
     * `texts->first()`, so the definition always creates one.
     */
    public function definition(): array
    {
        return [
            'user_id'        => User::factory(),
            'individual_id'  => IndividualFactory::new(),
            'draft'          => false,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Interview $interview) {
            if ($interview->texts()->exists()) {
                return;
            }

            InterviewText::create([
                'interview_id'       => $interview->getKey(),
                'interview_text'     => fake()->paragraph(),
                'interview_date'     => now()->timestamp,
                'interview_intro'    => fake()->sentence(),
                'interview_chapters' => null,
            ]);
        });
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
        return $this->afterCreating(function (Interview $interview) use ($chapters, $text) {
            $interview->texts()->first()->update([
                'interview_chapters' => $chapters,
                'interview_text'     => $text,
            ]);
        });
    }
}
