<?php

namespace Tests\Feature\Public;

use App\Models\Changelog;
use App\Models\Comment;
use App\Models\Game;
use App\Models\Review;
use App\Models\ScreenshotReviewComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The review listing and detail pages, and the public submission form.
 *
 * A submitted review is not published: it waits for an editor. That boundary is
 * what most of these tests are about.
 */
class ReviewPagesTest extends TestCase
{
    use RefreshDatabase;

    private function review(string $gameName, ?User $author = null, string $date = '2026-01-01'): Review
    {
        return Review::factory()
            ->forGame(Game::factory()->named($gameName)->create()->getKey())
            ->scored()
            ->create([
                'user_id'     => ($author ?? User::factory()->create())->user_id,
                'review_date' => strtotime($date),
            ]);
    }

    public function test_the_index_lists_published_reviews_newest_first(): void
    {
        $this->review('Older', date: '2026-01-01');
        $this->review('Newer', date: '2026-06-01');

        $reviews = $this->get(route('reviews.index'))->assertOk()->viewData('reviews');

        $this->assertSame(
            ['Newer', 'Older'],
            $reviews->map(fn ($review) => $review->games->first()->game_name)->all()
        );
    }

    public function test_the_index_hides_unpublished_reviews(): void
    {
        Review::factory()
            ->unpublished()
            ->forGame(Game::factory()->named('Waiting')->create()->getKey())
            ->create();

        $this->assertCount(0, $this->get(route('reviews.index'))->assertOk()->viewData('reviews'));
    }

    public function test_the_index_can_be_filtered_by_author(): void
    {
        $alice = User::factory()->create(['userid' => 'Alice']);

        $this->review('By Alice', $alice);
        $this->review('By someone else');

        $reviews = $this->get(route('reviews.index', ['author' => $alice->user_id]))
            ->assertOk()
            ->viewData('reviews');

        $this->assertCount(1, $reviews);
        $this->assertSame('By Alice', $reviews->first()->games->first()->game_name);
    }

    /**
     * The author filter is populated from users who have actually written
     * something.
     */
    public function test_only_reviewers_are_offered_as_authors(): void
    {
        $author = User::factory()->create(['userid' => 'Alice']);
        User::factory()->create(['userid' => 'Bob']);

        $this->review('Xenon', $author);

        $authors = $this->get(route('reviews.index'))->assertOk()->viewData('authors');

        $this->assertSame(['Alice'], $authors->pluck('userid')->all());
    }

    public function test_the_index_paginates(): void
    {
        foreach (range(1, 7) as $i) {
            $this->review('Game ' . $i);
        }

        $reviews = $this->get(route('reviews.index'))->assertOk()->viewData('reviews');

        $this->assertCount(5, $reviews);
        $this->assertSame(7, $reviews->total());
    }

    public function test_a_review_page_shows_the_review(): void
    {
        $review = $this->review('Xenon');

        $this->get(route('reviews.show', $review))
            ->assertOk()
            ->assertSee('Xenon');
    }

    /**
     * The sidebar offers the author's other reviews, without repeating the one
     * being read.
     */
    public function test_a_review_page_links_the_authors_other_reviews(): void
    {
        $author = User::factory()->create();

        $review = $this->review('Xenon', $author);
        $this->review('Turrican', $author);
        $this->review('By someone else');

        $others = $this->get(route('reviews.show', $review))->assertOk()->viewData('otherReviews');

        $this->assertSame(
            ['Turrican'],
            $others->map(fn ($other) => $other->games->first()->game_name)->all()
        );
    }

    public function test_a_review_page_carries_structured_data(): void
    {
        $review = $this->review('Xenon');

        $jsonLd = $this->get(route('reviews.show', $review))->assertOk()->viewData('jsonLd')->json();

        $this->assertStringContainsString('"@type": "Article"', $jsonLd);
        $this->assertStringContainsString('Review of Xenon', $jsonLd);
    }

    /**
     * The form is only reachable from a game, so arriving without one is a bad
     * request rather than an empty form.
     */
    public function test_the_submission_form_needs_a_game(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('reviews.edit'))
            ->assertStatus(400);
    }

    public function test_the_submission_form_loads_for_a_game(): void
    {
        $game = Game::factory()->named('Xenon')->create();

        $this->actingAs(User::factory()->create())
            ->get(route('reviews.edit', ['game' => $game->getKey()]))
            ->assertOk()
            ->assertSee('Xenon');
    }

    public function test_a_guest_cannot_reach_the_submission_form(): void
    {
        $this->get(route('reviews.edit'))->assertRedirect(route('login'));
    }

    /**
     * A submitted review lands unpublished, with its scores, and the visitor is
     * sent back to the game.
     */
    public function test_a_submitted_review_waits_for_an_editor(): void
    {
        $game = Game::factory()->named('Xenon')->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('reviews.submit'), [
                'game'     => $game->getKey(),
                'text'     => 'A fine shoot-em-up.',
                'graphics' => 5,
                'sound'    => 4,
                'gameplay' => 3,
                'overall'  => 4,
            ])
            ->assertRedirect(route('games.show', $game))
            ->assertSessionHas('alert-title', 'Review submitted');

        $review = Review::sole();

        $this->assertSame(Review::REVIEW_UNPUBLISHED, $review->review_edit);
        $this->assertSame('A fine shoot-em-up.', $review->review_text);
        $this->assertSame($user->user_id, $review->user_id);
        $this->assertSame(5, $review->score->review_graphics);
        $this->assertSame(1, Changelog::where('sub_section', 'Submission')->count());
    }

    public function test_missing_scores_default_to_zero(): void
    {
        $game = Game::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('reviews.submit'), ['game' => $game->getKey(), 'text' => 'Short.'])
            ->assertRedirect();

        $score = Review::sole()->score;

        $this->assertSame(0, $score->review_graphics);
        $this->assertSame(0, $score->review_overall);
    }

    /**
     * Screenshot comments are matched to the game's screenshots by position,
     * in screenshot_id order, and only the ones actually filled in are kept.
     */
    public function test_screenshot_comments_are_matched_by_position(): void
    {
        $game = Game::factory()->withScreenshot()->withScreenshot()->create();
        $screenshots = $game->screenshots->sortBy('screenshot_id')->values();

        $this->actingAs(User::factory()->create())
            ->post(route('reviews.submit'), [
                'game'       => $game->getKey(),
                'text'       => 'With captions.',
                'screenshot' => [null, 'The second screen'],
            ])
            ->assertRedirect();

        $this->assertSame(1, ScreenshotReviewComment::query()->count());
        $this->assertSame('The second screen', ScreenshotReviewComment::sole()->comment_text);

        $this->assertSame(
            $screenshots[1]->screenshot_id,
            (int) DB::table('screenshot_review')->value('screenshot_id')
        );
    }

    public function test_a_guest_cannot_submit_a_review(): void
    {
        $game = Game::factory()->create();

        $this->post(route('reviews.submit'), ['game' => $game->getKey(), 'text' => 'Spam'])
            ->assertRedirect(route('login'));

        $this->assertSame(0, Review::query()->count());
    }

    public function test_a_signed_in_visitor_can_comment_on_a_review(): void
    {
        $review = $this->review('Xenon');

        $this->actingAs(User::factory()->create())
            ->post(route('review.comment', $review), ['comment' => 'Good write-up.'])
            ->assertRedirect();

        $this->assertSame('Good write-up.', Comment::sole()->comment);
        $this->assertSame(1, $review->comments()->count());
        $this->assertSame(
            1,
            Changelog::where('section', 'Reviews')->where('sub_section', 'Comment')->count()
        );
    }

    public function test_a_guest_cannot_comment_on_a_review(): void
    {
        $review = $this->review('Xenon');

        $this->post(route('review.comment', $review), ['comment' => 'Spam'])
            ->assertRedirect(route('login'));

        $this->assertSame(0, Comment::query()->count());
    }
}
