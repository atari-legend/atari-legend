<?php

namespace Tests\Feature\Admin\Interviews;

use App\Models\Changelog;
use App\Models\Individual;
use App\Models\Interview;
use App\Models\InterviewText;
use App\Models\Screenshot;
use App\Models\ScreenshotInterview;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Admin\AdminTestCase;

/**
 * The admin Interviews section.
 *
 * Like articles, an interview is two rows - the interview and its text. Unlike
 * articles, the subject cannot be changed after creation: update() does not
 * touch ind_id.
 */
class InterviewsControllerTest extends AdminTestCase
{
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'author'   => $this->admin->getKey(),
            'date'     => '2026-03-14',
            'text'     => 'The interview itself.',
            'intro'    => 'A short introduction.',
            'chapters' => null,
            'draft'    => null,
        ], $overrides);
    }

    public function test_index_lists_the_interviews(): void
    {
        $individual = Individual::factory()->create(['ind_name' => 'Jochen Hippel']);
        Interview::factory()->create(['ind_id' => $individual->getKey()]);

        $this->get(route('admin.interviews.interviews.index'))
            ->assertOk()
            ->assertSee('Jochen Hippel');
    }

    public function test_create_and_edit_forms_load(): void
    {
        $individual = Individual::factory()->create(['ind_name' => 'Jochen Hippel']);
        $interview = Interview::factory()->create(['ind_id' => $individual->getKey()]);

        $this->get(route('admin.interviews.interviews.create'))->assertOk();

        $this->get(route('admin.interviews.interviews.edit', $interview))
            ->assertOk()
            ->assertSee('Jochen Hippel');
    }

    public function test_store_creates_the_interview_and_its_text(): void
    {
        $individual = Individual::factory()->create(['ind_name' => 'Jochen Hippel']);

        $this->post(route('admin.interviews.interviews.store'), $this->payload([
            'individual' => $individual->getKey(),
        ]))->assertRedirect(route('admin.interviews.interviews.index'));

        $interview = Interview::sole();
        $text = $interview->texts->first();

        $this->assertSame($individual->getKey(), $interview->ind_id);
        $this->assertSame('The interview itself.', $text->interview_text);
        $this->assertSame('A short introduction.', $text->interview_intro);
        $this->assertSame(
            Carbon::parse('2026-03-14')->timestamp,
            $text->getRawOriginal('interview_date')
        );

        $this->assertChangelog(Changelog::INSERT, 'Interviews', 'Jochen Hippel');
    }

    /**
     * The green Save button posts stay=true and comes back to the edit screen;
     * Save & Close returns to the list.
     */
    public function test_save_and_stay_returns_to_the_edit_screen(): void
    {
        $individual = Individual::factory()->create();

        $this->post(route('admin.interviews.interviews.store'), $this->payload([
            'individual' => $individual->getKey(),
            'stay'       => 'true',
        ]))->assertRedirect(route('admin.interviews.interviews.edit', Interview::sole()));
    }

    public function test_store_requires_a_known_subject(): void
    {
        $this->post(route('admin.interviews.interviews.store'), $this->payload(['individual' => 9999]))
            ->assertSessionHasErrors('individual');

        $this->post(route('admin.interviews.interviews.store'), $this->payload())
            ->assertSessionHasErrors('individual');

        $this->assertSame(0, Interview::query()->count());
        $this->assertNoChangelog();
    }

    public function test_store_requires_the_text_and_the_date(): void
    {
        $individual = Individual::factory()->create();

        $this->post(route('admin.interviews.interviews.store'), [
            'individual' => $individual->getKey(),
        ])->assertSessionHasErrors(['author', 'date', 'text']);

        $this->assertSame(0, Interview::query()->count());
    }

    public function test_update_rewrites_the_text(): void
    {
        $interview = Interview::factory()->create();

        $this->put(route('admin.interviews.interviews.update', $interview), $this->payload([
            'text'     => 'Rewritten.',
            'chapters' => '[hotspotUrl=#1]The early days[/hotspotUrl]',
        ]))->assertRedirect(route('admin.interviews.interviews.index'));

        $text = $interview->fresh()->texts->first();

        $this->assertSame('Rewritten.', $text->interview_text);
        $this->assertSame('[hotspotUrl=#1]The early days[/hotspotUrl]', $text->interview_chapters);
        $this->assertChangelog(Changelog::UPDATE, 'Interviews', $interview->individual->ind_name);
    }

    /**
     * An interview whose text row is missing gets one on the next save, rather
     * than failing.
     */
    public function test_update_creates_the_text_row_if_it_is_missing(): void
    {
        $interview = Interview::factory()->create();
        $interview->texts()->delete();

        $this->put(route('admin.interviews.interviews.update', $interview->fresh()), $this->payload([
            'text' => 'Recovered.',
        ]))->assertRedirect();

        $this->assertSame('Recovered.', $interview->fresh()->texts->first()->interview_text);
    }

    public function test_update_can_toggle_the_draft_flag(): void
    {
        $interview = Interview::factory()->draft()->create();

        $this->put(route('admin.interviews.interviews.update', $interview), $this->payload());

        $this->assertFalse((bool) $interview->fresh()->draft);
    }

    public function test_destroy_removes_the_interview(): void
    {
        $individual = Individual::factory()->create(['ind_name' => 'Jochen Hippel']);
        $interview = Interview::factory()->create(['ind_id' => $individual->getKey()]);

        $this->delete(route('admin.interviews.interviews.destroy', $interview))
            ->assertRedirect(route('admin.interviews.interviews.index'));

        $this->assertSame(0, Interview::query()->count());
        $this->assertSame(0, InterviewText::query()->count());
        $this->assertChangelog(Changelog::DELETE, 'Interviews', 'Jochen Hippel');
    }

    public function test_images_can_be_uploaded_and_deleted(): void
    {
        Storage::fake('public');

        $interview = Interview::factory()->create();

        $this->post(route('admin.interviews.interviews.image.store', $interview), [
            'image' => [UploadedFile::fake()->image('shot.png')],
        ])->assertRedirect(route('admin.interviews.interviews.edit', $interview));

        $screenshot = Screenshot::sole();
        Storage::disk('public')->assertExists($screenshot->getPath('interview'));

        $this->delete(route('admin.interviews.interviews.image.destroy', [$interview, $screenshot]))
            ->assertRedirect(route('admin.interviews.interviews.edit', $interview));

        $this->assertSame(0, Screenshot::query()->count());
        Storage::disk('public')->assertMissing($screenshot->getPath('interview'));
    }

    public function test_a_caption_can_be_added_and_removed(): void
    {
        $interview = Interview::factory()->create();
        $screenshot = Screenshot::factory()->create();
        $interview->screenshots()->attach($screenshot);

        $pivot = ScreenshotInterview::sole();

        $this->put(route('admin.interviews.interviews.image.update', $interview), [
            'description-' . $pivot->getKey() => 'At the keyboard',
        ])->assertRedirect(route('admin.interviews.interviews.edit', $interview));

        $this->assertSame('At the keyboard', $pivot->fresh()->comment->comment_text);

        $this->put(route('admin.interviews.interviews.image.update', $interview), [
            'description-' . $pivot->getKey() => '',
        ]);

        $this->assertNull($pivot->fresh()->comment);
    }

    public function test_non_admins_are_turned_away(): void
    {
        $interview = Interview::factory()->create();

        $this->assertNonAdminIsTurnedAway(route('admin.interviews.interviews.index'));
        $this->assertNonAdminIsTurnedAway(route('admin.interviews.interviews.edit', $interview));

        $this->assertSame(1, Interview::query()->count());
    }
}
