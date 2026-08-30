<?php

namespace Tests\Feature\Admin\Games;

use App\Http\Controllers\Admin\Games\GameConfigurationController;
use App\Models\Changelog;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Admin\AdminTestCase;

/**
 * The editor for the reference lists a game or a release picks its values from
 * - engines, genres, resolutions, roles and so on.
 *
 * One controller serves all of them: the `{type}` segment is looked up in a
 * table map, so the tests are mostly about that indirection holding for every
 * type, and about each type being labelled correctly in the changelog.
 */
class GameConfigurationTest extends AdminTestCase
{
    /** Every type the editor claims to support, as the route segment. */
    public static function types(): array
    {
        return collect(array_keys(GameConfigurationController::CONFIG_TYPES_TABLES))
            ->mapWithKeys(fn ($type) => [$type => [$type]])
            ->all();
    }

    private function table(string $type): string
    {
        return GameConfigurationController::CONFIG_TYPES_TABLES[$type];
    }

    public function test_the_index_opens_on_the_engines(): void
    {
        $this->get(route('admin.games.configuration.index'))
            ->assertRedirect(route('admin.games.configuration.show', 'engine'));
    }

    /**
     * Every type has to map to a real table with a `name` column, which is the
     * one thing the map cannot express - a typo there only shows up here.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('types')]
    public function test_a_type_lists_its_entries(string $type): void
    {
        DB::table($this->table($type))->insert(['name' => 'An existing entry']);

        $this->get(route('admin.games.configuration.show', $type))
            ->assertOk()
            ->assertSee(GameConfigurationController::CONFIG_TYPES_CHANGELOG[$type])
            ->assertSee('An existing entry');
    }

    public function test_an_entry_can_be_added(): void
    {
        $this->post(route('admin.games.configuration.store', 'genre'), ['name' => 'Shoot-em-up'])
            ->assertRedirect(route('admin.games.configuration.show', 'genre'));

        $this->assertSame(
            ['Shoot-em-up'],
            DB::table('game_genres')->pluck('name')->all()
        );

        $id = (int) DB::table('game_genres')->where('name', 'Shoot-em-up')->value('id');
        $this->assertChangelog(Changelog::INSERT, 'Games Config', 'Shoot-em-up');
        $this->assertSame('Genre', Changelog::sole()->sub_section);
        $this->assertSame($id, (int) Changelog::sole()->section_id);
        $this->assertSame($id, (int) Changelog::sole()->sub_section_id);
    }

    public function test_an_entry_needs_a_name(): void
    {
        $this->post(route('admin.games.configuration.store', 'genre'), ['name' => ''])
            ->assertSessionHasErrors('name');

        $this->assertSame(0, DB::table('game_genres')->count());
        $this->assertNoChangelog();
    }

    /**
     * Only three types carry a description; sending one for any other type has
     * to be dropped rather than written, since the column is not there.
     */
    public function test_a_description_is_stored_only_where_there_is_a_column(): void
    {
        $this->post(route('admin.games.configuration.store', 'engine'), [
            'name'        => 'GFA Basic',
            'description' => 'An interpreted language.',
        ])->assertRedirect();

        $this->assertSame('An interpreted language.', DB::table('engines')->sole()->description);

        $this->post(route('admin.games.configuration.store', 'genre'), [
            'name'        => 'Shoot-em-up',
            'description' => 'Ignored, game_genre has no description column.',
        ])->assertRedirect();

        $this->assertSame('Shoot-em-up', DB::table('game_genres')->sole()->name);
    }

    public function test_an_entry_can_be_renamed(): void
    {
        $id = DB::table('resolutions')->insertGetId(['name' => 'Lo']);

        $this->put(route('admin.games.configuration.update', ['type' => 'resolution', 'id' => $id]), [
            'name' => 'Low',
        ])->assertRedirect(route('admin.games.configuration.show', 'resolution'));

        $this->assertSame('Low', DB::table('resolutions')->where('id', $id)->value('name'));

        $this->assertChangelog(Changelog::UPDATE, 'Games Config', 'Low');
        $this->assertSame('Resolution', Changelog::sole()->sub_section);
    }

    public function test_a_description_can_be_edited(): void
    {
        $id = DB::table('engines')->insertGetId(['name' => 'STOS', 'description' => 'Basic.']);

        $this->put(route('admin.games.configuration.update', ['type' => 'engine', 'id' => $id]), [
            'name'        => 'STOS',
            'description' => 'The game creator.',
        ])->assertRedirect();

        $this->assertSame('The game creator.', DB::table('engines')->where('id', $id)->value('description'));
    }

    public function test_an_entry_can_be_deleted(): void
    {
        $id = DB::table('media_scan_type')->insertGetId(['name' => 'Label']);

        $this->delete(route('admin.games.configuration.destroy', ['type' => 'media-scan-type', 'id' => $id]))
            ->assertRedirect(route('admin.games.configuration.show', 'media-scan-type'));

        $this->assertSame(0, DB::table('media_scan_type')->count());

        // The name is read before the row goes, so the log still says what went
        $this->assertChangelog(Changelog::DELETE, 'Games Config', 'Label');
        $this->assertSame('Media Scan Type', Changelog::sole()->sub_section);
    }

    public function test_the_configuration_is_closed_to_non_admins(): void
    {
        $this->assertNonAdminIsTurnedAway(route('admin.games.configuration.show', 'engine'));
        $this->assertNonAdminIsTurnedAway(route('admin.games.configuration.index'));
    }
}
