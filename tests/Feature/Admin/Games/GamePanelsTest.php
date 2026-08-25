<?php

namespace Tests\Feature\Admin\Games;

use App\Models\Changelog;
use App\Models\Game;
use App\Models\GameFact;
use App\Models\GameVideo;
use App\Models\Individual;
use App\Models\PublisherDeveloper;
use App\Models\Screenshot;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Admin\AdminTestCase;

/**
 * The panels hanging off a game in the admin: credits, screenshots, facts,
 * videos and similar games, plus the individual and company editors those
 * credits point at.
 *
 * These are small controllers with one job each, so the tests are mostly about
 * the pivot rows - which carry a role, and are deleted by role rather than
 * wholesale.
 */
class GamePanelsTest extends AdminTestCase
{
    private function individualRole(string $name = 'Coder'): int
    {
        return DB::table('individual_role')->insertGetId(['name' => $name]);
    }

    private function developerRole(string $name = 'Developer'): int
    {
        return DB::table('developer_role')->insertGetId(['name' => $name]);
    }

    // Individuals

    public function test_an_individual_can_be_created_with_a_bio(): void
    {
        $this->post(route('admin.games.individuals.store'), [
            'name'    => 'Jochen Hippel',
            'profile' => 'Musician.',
            'email'   => 'jochen@example.org',
        ])->assertRedirect(route('admin.games.individuals.edit', Individual::sole()));

        $individual = Individual::sole();

        $this->assertSame('Jochen Hippel', $individual->ind_name);
        $this->assertSame('Musician.', $individual->ind_profile);
        $this->assertSame('jochen@example.org', $individual->ind_email);
    }

    /**
     * A blank bio is stored as NULL, not as an empty string - the coverage
     * report counts individuals "with a bio" and an empty string would inflate
     * it.
     */
    public function test_a_blank_bio_is_stored_as_null(): void
    {
        $this->post(route('admin.games.individuals.store'), [
            'name'    => 'Anonymous',
            'profile' => '',
            'email'   => '',
        ])->assertRedirect();

        $individual = Individual::sole();

        $this->assertNull($individual->ind_profile);
        $this->assertNull($individual->ind_email);
    }

    public function test_an_individual_needs_a_name_and_a_valid_email(): void
    {
        $this->post(route('admin.games.individuals.store'), ['name' => ''])
            ->assertSessionHasErrors('name');

        $this->post(route('admin.games.individuals.store'), [
            'name'  => 'Someone',
            'email' => 'not-an-email',
        ])->assertSessionHasErrors('email');

        $this->assertSame(0, Individual::query()->count());
    }

    public function test_an_avatar_can_be_uploaded_and_removed(): void
    {
        Storage::fake('public');

        $this->post(route('admin.games.individuals.store'), [
            'name'   => 'Jochen Hippel',
            'avatar' => UploadedFile::fake()->image('face.png'),
        ])->assertRedirect();

        $individual = Individual::sole();

        $this->assertSame('png', $individual->ind_imgext);
        Storage::disk('public')->assertExists('images/individual_screenshots/' . $individual->getKey() . '.png');

        $this->delete(route('admin.games.individuals.avatar.destroy', $individual))->assertRedirect();

        $this->assertNull($individual->fresh()->ind_imgext);
    }

    /**
     * A nickname is typed as free text, and becomes an individual in its own
     * right linked back to the person - the self-referential relation in the
     * schema.
     */
    public function test_nicknames_can_be_added_and_removed(): void
    {
        $individual = Individual::factory()->create(['ind_name' => 'Jochen Hippel']);

        $this->post(route('admin.games.individuals.nickname.store', $individual), [
            'nickname' => 'Mad Max',
        ])->assertRedirect(route('admin.games.individuals.edit', $individual));

        $this->assertSame(['Mad Max'], $individual->fresh()->nicknames->pluck('ind_name')->all());

        $nickname = $individual->fresh()->nicknames->first();

        // The nickname is a row of its own, so it is listed among individuals
        $this->assertSame(2, Individual::query()->count());

        $this->delete(route('admin.games.individuals.nickname.destroy', [$individual, $nickname]))
            ->assertRedirect(route('admin.games.individuals.edit', $individual));

        $this->assertCount(0, $individual->fresh()->nicknames);
    }

    // Companies

    public function test_a_company_can_be_created(): void
    {
        $this->post(route('admin.games.companies.store'), [
            'name'    => 'Ocean',
            'profile' => 'A Manchester publisher.',
        ])->assertRedirect(route('admin.games.companies.edit', PublisherDeveloper::sole()));

        $company = PublisherDeveloper::sole();

        $this->assertSame('Ocean', $company->pub_dev_name);
        $this->assertSame('A Manchester publisher.', $company->text->pub_dev_profile);
        $this->assertChangelog(Changelog::INSERT, 'Company', 'Ocean');
    }

    public function test_company_names_are_unique(): void
    {
        PublisherDeveloper::factory()->create(['pub_dev_name' => 'Ocean']);

        $this->post(route('admin.games.companies.store'), ['name' => 'Ocean'])
            ->assertSessionHasErrors('name');

        $this->assertSame(1, PublisherDeveloper::query()->count());
    }

    /**
     * Renaming a company must not trip the uniqueness rule on its own name.
     */
    public function test_a_company_may_keep_its_own_name(): void
    {
        $company = PublisherDeveloper::factory()->create(['pub_dev_name' => 'Ocean']);

        $this->put(route('admin.games.companies.update', $company), ['name' => 'Ocean'])
            ->assertRedirect();

        $this->assertSame('Ocean', $company->fresh()->pub_dev_name);
    }

    // Credits

    public function test_an_individual_can_be_credited_and_uncredited(): void
    {
        $game = Game::factory()->named('Xenon')->create();
        $individual = Individual::factory()->create(['ind_name' => 'Jochen Hippel']);
        $role = $this->individualRole('Musician');

        $this->post(route('admin.games.game-credits.store', $game), [
            'individual' => $individual->getKey(),
            'role'       => $role,
        ])->assertRedirect(route('admin.games.game-credits.index', $game));

        $this->assertSame(['Jochen Hippel'], $game->fresh()->individuals->pluck('ind_name')->all());
        $this->assertChangelog(Changelog::INSERT, 'Games', 'Xenon');

        $this->delete(route('admin.games.game-credits.destroy', [$game, $individual]), ['role' => $role])
            ->assertRedirect(route('admin.games.game-credits.index', $game));

        $this->assertCount(0, $game->fresh()->individuals);
    }

    /**
     * Someone credited in two roles loses only the role being removed.
     */
    public function test_removing_one_credit_leaves_the_other_role(): void
    {
        $game = Game::factory()->create();
        $individual = Individual::factory()->create();

        $coder = $this->individualRole('Coder');
        $musician = $this->individualRole('Musician');

        $game->individuals()->attach($individual, ['individual_role_id' => $coder]);
        $game->individuals()->attach($individual, ['individual_role_id' => $musician]);

        $this->delete(route('admin.games.game-credits.destroy', [$game, $individual]), ['role' => $coder]);

        $remaining = DB::table('game_individual')->where('game_id', $game->getKey())->get();

        $this->assertCount(1, $remaining);
        $this->assertSame($musician, $remaining->first()->individual_role_id);
    }

    public function test_crediting_an_unknown_individual_changes_nothing(): void
    {
        $game = Game::factory()->create();

        $this->post(route('admin.games.game-credits.store', $game), [
            'individual' => 9999,
            'role'       => $this->individualRole(),
        ])->assertRedirect();

        $this->assertCount(0, $game->fresh()->individuals);
        $this->assertNoChangelog();
    }

    public function test_a_developer_can_be_credited_and_uncredited(): void
    {
        $game = Game::factory()->named('Xenon')->create();
        $developer = PublisherDeveloper::factory()->create(['pub_dev_name' => 'The Bitmap Brothers']);
        $role = $this->developerRole();

        $this->post(route('admin.games.game-developers.store', $game), [
            'developer' => $developer->getKey(),
            'role'      => $role,
        ])->assertRedirect(route('admin.games.game-credits.index', $game));

        $this->assertSame(
            ['The Bitmap Brothers'],
            $game->fresh()->developers->pluck('pub_dev_name')->all()
        );

        $this->delete(
            route('admin.games.game-developers.destroy', [$game, $developer]),
            ['role' => $role]
        )->assertRedirect();

        $this->assertCount(0, $game->fresh()->developers);
    }

    // Screenshots

    public function test_screenshots_can_be_uploaded_and_removed(): void
    {
        Storage::fake('public');

        $game = Game::factory()->named('Xenon')->create();

        $this->post(route('admin.games.game-screenshots.store', $game), [
            'screenshot' => [
                UploadedFile::fake()->image('one.png'),
                UploadedFile::fake()->image('two.png'),
            ],
        ])->assertRedirect(route('admin.games.game-screenshots.index', $game));

        $this->assertSame(2, $game->fresh()->screenshots()->count());

        $screenshot = $game->fresh()->screenshots->first();
        Storage::disk('public')->assertExists($screenshot->getPath('game'));

        $this->delete(route('admin.games.game-screenshots.destroy', [$game, $screenshot]))
            ->assertRedirect(route('admin.games.game-screenshots.index', $game));

        $this->assertSame(1, $game->fresh()->screenshots()->count());
        Storage::disk('public')->assertMissing($screenshot->getPath('game'));
    }

    public function test_a_screenshot_upload_must_be_an_image(): void
    {
        $game = Game::factory()->create();

        $this->post(route('admin.games.game-screenshots.store', $game), [
            'screenshot' => [UploadedFile::fake()->create('notes.txt', 10)],
        ])->assertSessionHasErrors('screenshot.0');

        $this->post(route('admin.games.game-screenshots.store', $game), [])
            ->assertSessionHasErrors('screenshot');

        $this->assertSame(0, Screenshot::query()->count());
    }

    // Facts

    public function test_a_fact_can_be_added_edited_and_removed(): void
    {
        $game = Game::factory()->named('Xenon')->create();

        $this->post(route('admin.games.game-facts.store', $game), ['content' => 'Composed in a week.'])
            ->assertRedirect(route('admin.games.game-facts.index', $game));

        $fact = GameFact::sole();
        $this->assertSame('Composed in a week.', $fact->game_fact);

        $this->put(route('admin.games.game-facts.update', [$game, $fact]), ['content' => 'Composed in a day.'])
            ->assertRedirect();

        $this->assertSame('Composed in a day.', $fact->fresh()->game_fact);

        $this->delete(route('admin.games.game-facts.destroy', [$game, $fact]))->assertRedirect();

        $this->assertSame(0, GameFact::query()->count());
    }

    public function test_a_fact_needs_content(): void
    {
        $game = Game::factory()->create();

        $this->post(route('admin.games.game-facts.store', $game), [])
            ->assertSessionHasErrors('content');

        $this->assertSame(0, GameFact::query()->count());
    }

    // Similar games

    public function test_a_similar_game_can_be_linked_and_unlinked(): void
    {
        $game = Game::factory()->named('Xenon')->create();
        $similar = Game::factory()->named('Xenon 2')->create();

        $this->post(route('admin.games.game-similar.store', $game), ['similar' => $similar->getKey()])
            ->assertRedirect(route('admin.games.game-similar.index', $game));

        $this->assertSame(['Xenon 2'], $game->fresh()->similarGames->pluck('game_name')->all());

        $this->delete(route('admin.games.game-similar.destroy', [$game, $similar]))->assertRedirect();

        $this->assertCount(0, $game->fresh()->similarGames);
    }

    /**
     * Linking the same game twice must not create a second row.
     */
    public function test_a_similar_game_is_only_linked_once(): void
    {
        $game = Game::factory()->create();
        $similar = Game::factory()->create();

        $this->post(route('admin.games.game-similar.store', $game), ['similar' => $similar->getKey()]);
        $this->post(route('admin.games.game-similar.store', $game), ['similar' => $similar->getKey()]);

        $this->assertCount(1, $game->fresh()->similarGames);
    }

    // Videos

    /**
     * Adding a video calls out to YouTube for the title, so only the rejection
     * path is exercised here - the part that does not leave the process.
     */
    public function test_a_video_must_be_a_youtube_url(): void
    {
        $game = Game::factory()->create();

        $this->post(route('admin.games.game-videos.store', $game), [
            'video' => 'https://example.org/not-youtube',
        ])->assertSessionHasErrors('video');

        $this->assertSame(0, GameVideo::query()->count());
    }

    public function test_a_video_can_be_removed(): void
    {
        $game = Game::factory()->named('Xenon')->create();

        $video = GameVideo::create([
            'title'      => 'Longplay',
            'author'     => 'Someone',
            'youtube_id' => 'dQw4w9WgXcQ',
            'game_id'    => $game->getKey(),
        ]);

        $this->delete(route('admin.games.game-videos.destroy', [$game, $video]))
            ->assertRedirect(route('admin.games.game-videos.index', $game));

        $this->assertSame(0, GameVideo::query()->count());
        $this->assertChangelog(Changelog::DELETE, 'Games', 'Xenon');
    }

    public function test_the_panels_are_closed_to_non_admins(): void
    {
        $game = Game::factory()->create();

        $this->assertNonAdminIsTurnedAway(route('admin.games.game-credits.index', $game));
        $this->assertNonAdminIsTurnedAway(route('admin.games.game-screenshots.index', $game));
        $this->assertNonAdminIsTurnedAway(route('admin.games.individuals.index'));
        $this->assertNonAdminIsTurnedAway(route('admin.games.companies.index'));
    }
}
