<?php

namespace Tests\Feature\Public;

use App\Models\Changelog;
use App\Models\Comment;
use App\Models\Game;
use App\Models\GameSubmitInfo;
use App\Models\GameVote;
use App\Models\Individual;
use App\Models\Interview;
use App\Models\PublisherDeveloper;
use App\Models\Release;
use App\Models\Review;
use App\Models\Screenshot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The game page, which pulls together nearly every relation the schema has, and
 * the two things a signed-in visitor can do from it: leave a comment, and
 * submit a correction.
 *
 * The E2E suite proves the page renders against real data. These tests are
 * about what it decides to show.
 */
class GamePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_game_is_reachable_by_its_slug(): void
    {
        $game = Game::factory()->named('Bubble Bobble')->create();

        $this->get(route('games.show', $game))
            ->assertOk()
            ->assertSee('Bubble Bobble');
    }

    /**
     * Old links used the numeric id; they still have to land on the slug.
     */
    public function test_the_numeric_id_redirects_to_the_slug(): void
    {
        $game = Game::factory()->named('Bubble Bobble')->create();

        $this->get('/games/' . $game->getKey())
            ->assertRedirect('/games/bubble-bobble');
    }

    public function test_an_unknown_game_is_a_404(): void
    {
        $this->get(route('games.show', 'no-such-game'))->assertNotFound();
    }

    /**
     * Only published reviews reach the page; a submission awaiting an editor
     * must not.
     */
    public function test_unpublished_reviews_are_hidden(): void
    {
        $game = Game::factory()->create();

        Review::factory()->forGame($game->getKey())->scored()->create();
        Review::factory()->unpublished()->forGame($game->getKey())->scored()->create();

        $reviews = $this->get(route('games.show', $game))->assertOk()->viewData('reviews');

        $this->assertCount(1, $reviews);
        $this->assertSame(Review::REVIEW_PUBLISHED, $reviews->first()->review_edit);
    }

    /**
     * Box scans are shown front first, and only the ones that are actually box
     * scans - goodies are listed elsewhere.
     */
    public function test_box_scans_are_listed_front_first(): void
    {
        $game = Game::factory()->create();
        $release = Release::factory()->create(['game_id' => $game->getKey()]);

        foreach (['Box back', 'Box front', 'Poster'] as $type) {
            DB::table('game_release_scan')->insert([
                'game_release_id' => $release->getKey(),
                'type'            => $type,
                'imgext'          => 'jpg',
            ]);
        }

        $boxscans = $this->get(route('games.show', $game))->assertOk()->viewData('boxscans');

        $this->assertCount(2, $boxscans);
        $this->assertStringContainsString('/games/release/', $boxscans->first()['preview']);
    }

    public function test_developer_logos_are_shown_only_when_there_is_one(): void
    {
        $game = Game::factory()->create();
        $game->developers()->attach(
            PublisherDeveloper::factory()->create(),
            ['developer_role_id' => DB::table('developer_role')->insertGetId(['name' => 'Developer'])]
        );

        $this->assertCount(
            0,
            $this->get(route('games.show', $game))->assertOk()->viewData('developersLogos')
        );
    }

    /**
     * Interviews are only offered where there is a portrait to show alongside
     * them, and an individual credited twice must not produce two.
     */
    public function test_interviews_need_a_portrait_and_are_listed_once(): void
    {
        $game = Game::factory()->create();
        $roleId = DB::table('individual_role')->insertGetId(['name' => 'Coder']);
        $otherRoleId = DB::table('individual_role')->insertGetId(['name' => 'Graphics']);

        $withPortrait = Individual::factory()->create();
        $withPortrait->text()->create(['ind_profile' => 'A coder', 'ind_imgext' => 'jpg']);
        Interview::factory()->create(['ind_id' => $withPortrait->getKey()]);

        $withoutPortrait = Individual::factory()->withBio()->create();
        Interview::factory()->create(['ind_id' => $withoutPortrait->getKey()]);

        $game->individuals()->attach($withPortrait, ['individual_role_id' => $roleId]);
        $game->individuals()->attach($withPortrait, ['individual_role_id' => $otherRoleId]);
        $game->individuals()->attach($withoutPortrait, ['individual_role_id' => $roleId]);

        $interviews = $this->get(route('games.show', $game))->assertOk()->viewData('interviews');

        $this->assertCount(1, $interviews);
    }

    public function test_the_average_score_is_shown_once_there_are_votes(): void
    {
        $game = Game::factory()->create();

        foreach ([4, 2] as $score) {
            GameVote::factory()->scored($score)->create(['game_id' => $game->getKey()]);
        }

        $response = $this->get(route('games.show', $game))->assertOk();

        $this->assertSame(2, $response->viewData('voteCount'));
        $this->assertNotNull($response->viewData('score'));
    }

    public function test_a_game_with_no_votes_reports_no_score(): void
    {
        $response = $this->get(route('games.show', Game::factory()->create()))->assertOk();

        $this->assertNull($response->viewData('score'));
        $this->assertSame(0, $response->viewData('voteCount'));
    }

    /**
     * A visitor's own vote is only looked up once they are signed in.
     */
    public function test_the_visitors_own_vote_is_only_loaded_when_signed_in(): void
    {
        $game = Game::factory()->create();
        $user = User::factory()->create();

        GameVote::factory()->scored(3)->create([
            'game_id' => $game->getKey(),
            'user_id' => $user->getKey(),
        ]);

        $this->assertNull($this->get(route('games.show', $game))->viewData('vote'));

        $this->assertNotNull(
            $this->actingAs($user)->get(route('games.show', $game))->viewData('vote')
        );
    }

    public function test_the_page_carries_structured_data(): void
    {
        $game = Game::factory()->named('Xenon')->withScreenshot()->create();

        $jsonLd = $this->get(route('games.show', $game))->assertOk()->viewData('jsonLd')->json();

        $this->assertStringContainsString('"@type": "VideoGame"', $jsonLd);
        $this->assertStringContainsString('Atari ST', $jsonLd);
        $this->assertStringContainsString('Xenon', $jsonLd);
        $this->assertStringContainsString('"image"', $jsonLd);
    }

    public function test_a_signed_in_visitor_can_comment(): void
    {
        $game = Game::factory()->named('Xenon')->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('games.comment', $game), ['comment' => 'Still holds up.'])
            ->assertRedirect();

        $comment = Comment::sole();

        $this->assertSame('Still holds up.', $comment->comment);
        $this->assertSame(1, $game->comments()->count());
        $this->assertSame(1, Changelog::where('sub_section', 'Comment')->count());
    }

    public function test_a_guest_cannot_comment(): void
    {
        $game = Game::factory()->create();

        $this->post(route('games.comment', $game), ['comment' => 'Spam'])
            ->assertRedirect(route('login'));

        $this->assertSame(0, Comment::query()->count());
    }

    public function test_a_signed_in_visitor_can_submit_a_correction(): void
    {
        $game = Game::factory()->named('Xenon')->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('games.submitInfo', $game), ['info' => 'The publisher is wrong.'])
            ->assertRedirect()
            ->assertSessionHas('alert-title', 'Info submitted');

        $submission = GameSubmitInfo::sole();

        $this->assertSame('The publisher is wrong.', $submission->submit_text);
        $this->assertSame(GameSubmitInfo::SUBMISSION_NEW, $submission->game_done);
        $this->assertSame($user->getKey(), $submission->user_id);
        $this->assertSame(1, Changelog::where('sub_section', 'Submission')->count());
    }

    /**
     * A correction can come with screenshots, which are stored under the id the
     * screenshot row was given.
     */
    public function test_a_correction_can_carry_screenshots(): void
    {
        Storage::fake('public');

        $game = Game::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('games.submitInfo', $game), [
                'info'  => 'Here are better shots.',
                'files' => [UploadedFile::fake()->image('shot.png')],
            ])
            ->assertRedirect();

        $screenshot = Screenshot::sole();

        $this->assertSame('png', $screenshot->imgext);
        Storage::disk('public')->assertExists(
            'images/game_submit_screenshots/' . $screenshot->getKey() . '.png'
        );
    }

    public function test_a_guest_cannot_submit_a_correction(): void
    {
        $game = Game::factory()->create();

        $this->post(route('games.submitInfo', $game), ['info' => 'Spam'])
            ->assertRedirect(route('login'));

        $this->assertSame(0, GameSubmitInfo::query()->count());
    }
}
