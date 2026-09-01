<?php

namespace Tests\Feature\Admin\Reviews;

use App\Models\Changelog;
use App\Models\Game;
use App\Models\Review;
use App\Models\Screenshot;
use App\Models\ScreenshotReviewComment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Admin\AdminTestCase;

/**
 * The admin Reviews section.
 *
 * A review carries four scores in a separate row, and screenshot captions keyed
 * by the game's screenshot ids. The other thing worth pinning is the
 * submission flag: it decides both whether the review is published and which
 * list the editor lands back on.
 */
class ReviewsControllerTest extends AdminTestCase
{
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'author'   => $this->admin->getKey(),
            'date'     => '2026-03-14',
            'text'     => 'A fine shoot-em-up.',
            'graphics' => 5,
            'sound'    => 4,
            'gameplay' => 3,
            'overall'  => 4,
        ], $overrides);
    }

    private function review(string $gameName = 'Xenon'): Review
    {
        return Review::factory()
            ->forGame(Game::factory()->named($gameName)->create()->getKey())
            ->scored()
            ->create();
    }

    public function test_index_lists_the_reviews(): void
    {
        $this->review('Xenon');

        $this->get(route('admin.reviews.reviews.index'))
            ->assertOk()
            ->assertSee('Xenon');
    }

    public function test_create_and_edit_forms_load(): void
    {
        $review = $this->review('Xenon');

        $this->get(route('admin.reviews.reviews.create'))->assertOk();

        $this->get(route('admin.reviews.reviews.edit', $review))
            ->assertOk()
            ->assertSee('Xenon');
    }

    public function test_store_creates_the_review_and_its_scores(): void
    {
        $game = Game::factory()->named('Xenon')->create();

        $this->post(route('admin.reviews.reviews.store'), $this->payload([
            'game' => $game->getKey(),
        ]))->assertRedirect(route('admin.reviews.reviews.index'));

        $review = Review::sole();

        $this->assertSame('A fine shoot-em-up.', $review->text);
        $this->assertSame($this->admin->getKey(), $review->user_id);
        $this->assertSame('Xenon', $review->games->first()->name);
        $this->assertSame(Review::REVIEW_PUBLISHED, $review->edit);

        $this->assertSame(5, $review->graphics);
        $this->assertSame(4, $review->sound);
        $this->assertSame(3, $review->gameplay);
        $this->assertSame(4, $review->overall);

        $this->assertSame(
            Carbon::parse('2026-03-14')->timestamp,
            $review->getRawOriginal('date')
        );

        $this->assertChangelog(Changelog::INSERT, 'Reviews', 'Xenon');
    }

    /**
     * Saving as a submission leaves the review unpublished, and sends the
     * editor back to the submissions list rather than the reviews list.
     */
    public function test_a_review_saved_as_a_submission_stays_unpublished(): void
    {
        $game = Game::factory()->create();

        $this->post(route('admin.reviews.reviews.store'), $this->payload([
            'game'       => $game->getKey(),
            'submission' => '1',
        ]))->assertRedirect(route('admin.reviews.reviews.index'));

        $this->assertSame(Review::REVIEW_UNPUBLISHED, Review::sole()->edit);
    }

    public function test_store_requires_a_known_game(): void
    {
        $this->post(route('admin.reviews.reviews.store'), $this->payload(['game' => 9999]))
            ->assertSessionHasErrors('game');

        $this->assertSame(0, Review::query()->count());
        $this->assertNoChangelog();
    }

    public function test_store_requires_every_score(): void
    {
        $game = Game::factory()->create();

        $this->post(route('admin.reviews.reviews.store'), [
            'game' => $game->getKey(),
        ])->assertSessionHasErrors(['author', 'date', 'text', 'graphics', 'sound', 'gameplay', 'overall']);

        $this->assertSame(0, Review::query()->count());
    }

    public function test_scores_outside_the_scale_are_rejected(): void
    {
        $game = Game::factory()->create();

        $this->post(route('admin.reviews.reviews.store'), $this->payload([
            'game'     => $game->getKey(),
            'graphics' => 11,
            'sound'    => -1,
        ]))->assertSessionHasErrors(['graphics', 'sound']);

        $this->assertSame(0, Review::query()->count());
    }

    public function test_update_rewrites_the_review_and_its_scores(): void
    {
        $review = $this->review('Xenon');

        $this->put(route('admin.reviews.reviews.update', $review), $this->payload([
            'text'     => 'Revisited: it holds up.',
            'graphics' => 2,
        ]))->assertRedirect(route('admin.reviews.reviews.index'));

        $review->refresh();

        $this->assertSame('Revisited: it holds up.', $review->text);
        $this->assertSame(2, $review->graphics);
        $this->assertChangelog(Changelog::UPDATE, 'Reviews', 'Xenon');
    }

    /**
     * An unscored review is a supported state - the columns are nullable and
     * the public page guards them - and the next save fills it in.
     */
    public function test_update_fills_in_scores_that_were_never_set(): void
    {
        $review = Review::factory()
            ->forGame(Game::factory()->named('Xenon')->create()->getKey())
            ->create();

        $this->assertNull($review->graphics);

        $this->put(route('admin.reviews.reviews.update', $review->fresh()), $this->payload());

        $this->assertSame(5, $review->fresh()->graphics);
    }

    public function test_save_and_stay_returns_to_the_edit_screen(): void
    {
        $review = $this->review();

        $this->put(route('admin.reviews.reviews.update', $review), $this->payload(['stay' => 'true']))
            ->assertRedirect(route('admin.reviews.reviews.edit', $review));
    }

    /**
     * Saving a submission sends the editor back to the submissions queue, not
     * to the published list.
     */
    public function test_saving_a_submission_returns_to_the_submissions_list(): void
    {
        $review = $this->review();

        $this->put(route('admin.reviews.reviews.update', $review), $this->payload(['submission' => '1']))
            ->assertRedirect(route('admin.reviews.submissions.index'));
    }

    /**
     * Captions are posted as screenshot_comment_{screenshot id}, and the pivot
     * row is created on demand.
     */
    public function test_a_screenshot_caption_can_be_added_changed_and_removed(): void
    {
        $game = Game::factory()->named('Xenon')->withScreenshot()->create();
        $review = Review::factory()->forGame($game->getKey())->scored()->create();
        $screenshot = $game->screenshots->first();

        $this->put(route('admin.reviews.reviews.update', $review), $this->payload([
            'screenshot_comment_' . $screenshot->getKey() => 'The first level',
        ]))->assertRedirect();

        $this->assertSame('The first level', ScreenshotReviewComment::sole()->text);

        $this->put(route('admin.reviews.reviews.update', $review), $this->payload([
            'screenshot_comment_' . $screenshot->getKey() => 'A better caption',
        ]));

        $this->assertSame('A better caption', ScreenshotReviewComment::sole()->text);

        // A null value removes the pivot, and the caption with it
        $this->put(route('admin.reviews.reviews.update', $review), $this->payload([
            'screenshot_comment_' . $screenshot->getKey() => null,
        ]));

        $this->assertSame(0, DB::table('screenshot_review')->count());
    }

    public function test_destroy_removes_the_review(): void
    {
        $review = $this->review('Xenon');

        $this->delete(route('admin.reviews.reviews.destroy', $review))
            ->assertRedirect(route('admin.reviews.reviews.index'));

        $this->assertSame(0, Review::query()->count());
        $this->assertChangelog(Changelog::DELETE, 'Reviews', 'Xenon');
    }

    public function test_the_author_can_be_reassigned(): void
    {
        $review = $this->review();
        $author = User::factory()->create();

        $this->put(route('admin.reviews.reviews.update', $review), $this->payload([
            'author' => $author->getKey(),
        ]));

        $this->assertSame($author->getKey(), $review->fresh()->user_id);
    }

    public function test_non_admins_are_turned_away(): void
    {
        $review = $this->review();

        $this->assertNonAdminIsTurnedAway(route('admin.reviews.reviews.index'));
        $this->assertNonAdminIsTurnedAway(route('admin.reviews.reviews.edit', $review));

        $this->assertSame(1, Review::query()->count());
    }
}
