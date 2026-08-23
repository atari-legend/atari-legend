<?php

namespace Tests\Feature\Admin\Games;

use App\Models\Changelog;
use App\Models\Comment;
use App\Models\Game;
use App\Models\GameSubmitInfo;
use App\Models\Screenshot;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Admin\AdminTestCase;

/**
 * Game submissions: the corrections visitors send in about a game, which an
 * administrator reads and then either marks as dealt with, turns into a public
 * comment on the game, or throws away.
 *
 * The table has no factory, so the fixtures are built with the query builder -
 * `game_submitinfo` predates the models and has no timestamps and a string
 * `timestamp` column holding a Unix time.
 */
class GameSubmissionTest extends AdminTestCase
{
    private User $visitor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->visitor = User::factory()->create(['userid' => 'Contributor']);
    }

    private function submission(
        Game $game,
        string $text = 'The musician is Jochen Hippel.',
        string $done = GameSubmitInfo::SUBMISSION_NEW
    ): GameSubmitInfo {
        $id = DB::table('game_submitinfo')->insertGetId([
            'game_id'     => $game->getKey(),
            'user_id'     => $this->visitor->getKey(),
            'timestamp'   => (string) mktime(12, 0, 0, 6, 1, 2020),
            'submit_text' => $text,
            'game_done'   => $done,
        ]);

        return GameSubmitInfo::findOrFail($id);
    }

    private function screenshot(GameSubmitInfo $submission): Screenshot
    {
        $screenshot = Screenshot::factory()->create();
        $submission->screenshots()->attach($screenshot);

        Storage::disk('public')->put($screenshot->getPath('game_submission'), 'not really a png');

        return $screenshot;
    }

    public function test_the_listing_renders(): void
    {
        $this->submission(Game::factory()->named('Xenon')->create());

        $this->get(route('admin.games.submissions.index'))
            ->assertOk()
            ->assertSee('Game submissions')
            ->assertSee('Xenon');
    }

    public function test_a_submission_is_shown_with_its_text_and_author(): void
    {
        $submission = $this->submission(
            Game::factory()->named('Xenon')->create(),
            'The musician is Jochen Hippel.'
        );

        $this->get(route('admin.games.submissions.show', $submission))
            ->assertOk()
            ->assertSee('Xenon')
            ->assertSee('Contributor')
            ->assertSee('The musician is Jochen Hippel.');
    }

    public function test_a_submission_can_be_marked_as_reviewed(): void
    {
        $submission = $this->submission(Game::factory()->named('Xenon')->create());

        $this->put(route('admin.games.submissions.update', $submission), ['action' => 'review'])
            ->assertRedirect(route('admin.games.submissions.index'));

        $this->assertSame(GameSubmitInfo::SUBMISSION_REVIEWED, $submission->fresh()->game_done);
        $this->assertChangelog(Changelog::UPDATE, 'Games', 'Xenon');
    }

    public function test_a_reviewed_submission_can_be_put_back_in_the_queue(): void
    {
        $submission = $this->submission(
            Game::factory()->named('Xenon')->create(),
            'The musician is Jochen Hippel.',
            GameSubmitInfo::SUBMISSION_REVIEWED
        );

        $this->put(route('admin.games.submissions.update', $submission), ['action' => 'unreview'])
            ->assertRedirect(route('admin.games.submissions.index'));

        $this->assertSame(GameSubmitInfo::SUBMISSION_NEW, $submission->fresh()->game_done);
        $this->assertChangelog(Changelog::UPDATE, 'Games', 'Xenon');
    }

    /**
     * "Convert to comment" is the way a submission worth keeping is published:
     * the text becomes a comment on the game, attributed to whoever sent it in
     * and dated when they sent it, and the submission itself goes away.
     */
    public function test_a_submission_can_be_converted_into_a_comment(): void
    {
        $game = Game::factory()->named('Xenon')->create();
        $submission = $this->submission($game, 'Best soundtrack on the ST.');

        $this->put(route('admin.games.submissions.update', $submission), ['action' => 'comment'])
            ->assertRedirect(route('admin.games.submissions.index'));

        $comment = Comment::sole();

        $this->assertSame('Best soundtrack on the ST.', $comment->comment);
        $this->assertSame($this->visitor->getKey(), $comment->user_id);
        $this->assertSame($submission->timestamp, (string) $comment->timestamp);
        $this->assertSame([$game->getKey()], $comment->games->pluck('id')->all());

        $this->assertSame(0, GameSubmitInfo::query()->count());

        $this->assertChangelog(Changelog::INSERT, 'Games', 'Xenon');
        $this->assertChangelog(Changelog::DELETE, 'Games', 'Xenon');
    }

    /**
     * An action the form does not offer leaves everything as it was - the
     * switch has no default branch, so this is the only way to tell.
     */
    public function test_an_unknown_action_changes_nothing(): void
    {
        $submission = $this->submission(Game::factory()->create());

        $this->put(route('admin.games.submissions.update', $submission), ['action' => 'nonsense'])
            ->assertRedirect(route('admin.games.submissions.index'));

        $this->assertSame(GameSubmitInfo::SUBMISSION_NEW, $submission->fresh()->game_done);
        $this->assertSame(0, Comment::query()->count());
        $this->assertNoChangelog();
    }

    public function test_a_submission_can_be_deleted_with_its_attachments(): void
    {
        Storage::fake('public');

        $submission = $this->submission(Game::factory()->named('Xenon')->create());
        $screenshot = $this->screenshot($submission);

        $this->delete(route('admin.games.submissions.destroy', $submission))
            ->assertRedirect(route('admin.games.submissions.index'));

        $this->assertSame(0, GameSubmitInfo::query()->count());
        $this->assertSame(0, DB::table('screenshot_game_submitinfo')->count());
        Storage::disk('public')->assertMissing($screenshot->getPath('game_submission'));

        $this->assertChangelog(Changelog::DELETE, 'Games', 'Xenon');
    }

    public function test_a_single_attachment_can_be_removed(): void
    {
        Storage::fake('public');

        $submission = $this->submission(Game::factory()->named('Xenon')->create());
        $removed = $this->screenshot($submission);
        $kept = $this->screenshot($submission);

        $this->delete(route('admin.games.submissions.screenshots.destroy', [$submission, $removed]))
            ->assertRedirect(route('admin.games.submissions.show', $submission));

        $this->assertSame([$kept->getKey()], $submission->fresh()->screenshots->modelKeys());

        Storage::disk('public')->assertMissing($removed->getPath('game_submission'));
        Storage::disk('public')->assertExists($kept->getPath('game_submission'));

        $this->assertChangelog(Changelog::DELETE, 'Games', 'Xenon');
        $this->assertSame('Screenshot', Changelog::sole()->sub_section_name);
    }

    /**
     * The screenshot id comes straight from the URL, so a screenshot attached
     * to another submission must not be deleted through this one.
     */
    public function test_an_attachment_of_another_submission_is_left_alone(): void
    {
        Storage::fake('public');

        $submission = $this->submission(Game::factory()->named('Xenon')->create());
        $other = $this->submission(Game::factory()->named('Turrican')->create());
        $screenshot = $this->screenshot($other);

        $this->delete(route('admin.games.submissions.screenshots.destroy', [$submission, $screenshot]))
            ->assertRedirect(route('admin.games.submissions.show', $submission));

        $this->assertSame([$screenshot->getKey()], $other->fresh()->screenshots->modelKeys());
        Storage::disk('public')->assertExists($screenshot->getPath('game_submission'));

        $this->assertNoChangelog();
    }

    public function test_submissions_are_closed_to_non_admins(): void
    {
        $submission = $this->submission(Game::factory()->create());

        $this->assertNonAdminIsTurnedAway(route('admin.games.submissions.index'));
        $this->assertNonAdminIsTurnedAway(route('admin.games.submissions.show', $submission));
        $this->assertNonAdminIsTurnedAway(route('admin.games.submissions.destroy', $submission), 'delete');

        $this->assertSame(1, GameSubmitInfo::query()->count());
    }
}
