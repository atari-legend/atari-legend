<?php

namespace Tests\Feature\Admin;

use App\Http\Controllers\Admin\Games\GameConfigurationController;
use App\Models\Article;
use App\Models\Comment;
use App\Models\Crew;
use App\Models\Game;
use App\Models\GameFact;
use App\Models\GameSeries;
use App\Models\GameSubmitInfo;
use App\Models\Individual;
use App\Models\Interview;
use App\Models\Magazine;
use App\Models\MagazineIssue;
use App\Models\Media;
use App\Models\Menu;
use App\Models\MenuDisk;
use App\Models\MenuDiskCondition;
use App\Models\MenuDiskContent;
use App\Models\MenuSet;
use App\Models\MenuSoftware;
use App\Models\MenuSoftwareContentType;
use App\Models\News;
use App\Models\PublisherDeveloper;
use App\Models\Release;
use App\Models\Review;
use App\Models\Spotlight;
use App\Models\User;
use App\Models\Website;
use App\Models\WebsiteCategory;
use Illuminate\Support\Facades\Route;

/**
 * Every screen of the admin panel, rendered once.
 *
 * The write paths have tests of their own; the screens that show the forms
 * mostly do not, and those are the ones that answer 500 when a view variable
 * stops being passed or a relation is renamed - a break no store() test can
 * see. This walks the router rather than a hand-written list, so a section
 * added later is covered from the day its routes are registered.
 */
class AdminRenderTest extends AdminTestCase
{
    /**
     * Admin GET routes this test deliberately does not render, and why.
     *
     * Nothing belongs here that a fixture could satisfy.
     */
    private const SKIPPED = [
        'admin.games.music'               => 'MySQL MATCH ... AGAINST full-text search, which the SQLite test database has no equivalent for',
        'admin.games.game-music.index'    => 'MySQL MATCH ... AGAINST full-text search, which the SQLite test database has no equivalent for',
        'admin.games.configuration.index' => 'A bare redirect rather than a screen; asserted on its own below',
    ];

    /**
     * How many admin GET routes this is expected to reach, at a minimum.
     *
     * Without it the whole test would still pass if the router filter stopped
     * matching anything, which is the one failure an empty list of failures
     * cannot tell apart from success.
     */
    private const MINIMUM_ROUTES = 90;

    /**
     * One of everything the admin panel can be pointed at, keyed by the route
     * parameter that binds it.
     *
     * The graph is joined up - the release belongs to the game, the disk to the
     * menu, the issue to the magazine - because several screens read the parent
     * through the child and would fall over on an unrelated pair.
     *
     * @return array<string, mixed>
     */
    private function fixtures(): array
    {
        $game = Game::factory()->named('Xenon')->withScreenshot()->create();
        $release = Release::factory()->create(['game_id' => $game->getKey()]);
        Media::factory()->create(['release_id' => $release->getKey()]);

        $magazine = Magazine::factory()->create();
        $set = MenuSet::factory()->create();
        $menu = Menu::factory()->create(['menu_set_id' => $set->getKey()]);
        $disk = MenuDisk::factory()->create(['menu_id' => $menu->getKey()]);

        $content = new MenuDiskContent(['menu_disk_id' => $disk->getKey(), 'order' => 1]);
        $content->game_id = $game->getKey();
        $content->save();

        // forceCreate: neither model lists its foreign keys as fillable.
        $fact = GameFact::forceCreate([
            'game_id'   => $game->getKey(),
            'game_fact' => 'Written in a fortnight.',
        ]);

        $submission = GameSubmitInfo::forceCreate([
            'game_id'     => $game->getKey(),
            'user_id'     => User::factory()->create()->user_id,
            'timestamp'   => (string) now()->timestamp,
            'submit_text' => 'Please add this.',
            'game_done'   => GameSubmitInfo::SUBMISSION_NEW,
        ]);

        return [
            'article'      => Article::factory()->create(),
            'category'     => WebsiteCategory::factory()->create(),
            'comment'      => Comment::factory()->onGame($game)->create(),
            'company'      => PublisherDeveloper::factory()->create(),
            'condition'    => MenuDiskCondition::query()->firstOrFail(),
            'content'      => $content,
            'content_type' => MenuSoftwareContentType::query()->firstOrFail(),
            'crew'         => Crew::factory()->create(),
            'disk'         => $disk,
            'fact'         => $fact,
            'game'         => $game,
            'individual'   => Individual::factory()->create(),
            'interview'    => Interview::factory()->create(),
            'issue'        => MagazineIssue::factory()->create(['magazine_id' => $magazine->getKey()]),
            'link'         => Website::factory()->create(),
            'magazine'     => $magazine,
            'menu'         => $menu,
            'news'         => News::factory()->create(),
            'release'      => $release,
            'review'       => Review::factory()->forGame($game->getKey())->scored()->create(),
            'series'       => GameSeries::create(['name' => 'Xenon']),
            'set'          => $set,
            'software'     => MenuSoftware::factory()->create(),
            'spotlight'    => Spotlight::factory()->create(),
            'submission'   => $submission,
            'type'         => 'engine',
            'user'         => $this->admin,
        ];
    }

    /**
     * Query-string arguments a handful of screens need on top of their route
     * parameters.
     *
     * These are the "create" forms reached from a parent page, which read the
     * parent - or, for disk contents, the kind of thing being added - out of
     * the query string instead of the path.
     */
    private function extraParametersFor(string $name, array $fixtures): array
    {
        return match ($name) {
            'admin.menus.menus.create'         => ['set' => $fixtures['set']->getKey()],
            'admin.menus.disks.create'         => ['menu' => $fixtures['menu']->getKey()],
            'admin.menus.disks.content.create' => ['type' => 'game'],
            default                            => [],
        };
    }

    public function test_every_admin_screen_renders(): void
    {
        $fixtures = $this->fixtures();

        $failures = [];
        $rendered = 0;

        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();

            if ($name === null || ! str_starts_with($name, 'admin.') || ! in_array('GET', $route->methods(), true)) {
                continue;
            }

            if (array_key_exists($name, self::SKIPPED)) {
                continue;
            }

            $missing = array_diff($route->parameterNames(), array_keys($fixtures));

            if ($missing !== []) {
                $failures[] = $name . ' -> no fixture for {' . implode('}, {', $missing) . '}';
                continue;
            }

            $parameters = array_merge(
                array_intersect_key($fixtures, array_flip($route->parameterNames())),
                $this->extraParametersFor($name, $fixtures)
            );

            $response = $this->get(route($name, $parameters));
            $rendered++;

            if ($response->getStatusCode() !== 200) {
                $failures[] = sprintf(
                    '%s -> %d %s',
                    $name,
                    $response->getStatusCode(),
                    $response->exception?->getMessage() ?? ''
                );
            }
        }

        sort($failures);

        $this->assertSame([], $failures, 'Admin screens that did not render');

        $this->assertGreaterThanOrEqual(
            self::MINIMUM_ROUTES,
            $rendered,
            'Far fewer admin screens were reached than expected - has the route filter stopped matching?'
        );
    }

    /**
     * The configuration screens all come out of one controller driven by the
     * type in the URL, so the list of types is what decides how much of it is
     * ever rendered.
     */
    public function test_every_game_configuration_type_renders(): void
    {
        $failures = [];

        foreach (array_keys(GameConfigurationController::CONFIG_TYPES_TABLES) as $type) {
            $response = $this->get(route('admin.games.configuration.show', $type));

            if ($response->getStatusCode() !== 200) {
                $failures[] = $type . ' -> ' . $response->getStatusCode()
                    . ' ' . ($response->exception?->getMessage() ?? '');
            }
        }

        $this->assertSame([], $failures, 'Configuration types that did not render');
    }

    public function test_the_configuration_landing_page_redirects_to_the_first_type(): void
    {
        $this->get(route('admin.games.configuration.index'))
            ->assertRedirect(route('admin.games.configuration.show', 'engine'));
    }

    /**
     * The form for adding a content to a menu disk pulls in a different partial
     * per kind of content, chosen by a query string the loop above can only
     * pass one value for.
     */
    public function test_every_kind_of_menu_disk_content_can_be_added(): void
    {
        $disk = MenuDisk::factory()->create();

        foreach (['game', 'release', 'software'] as $type) {
            $this->get(route('admin.menus.disks.content.create', ['disk' => $disk, 'type' => $type]))
                ->assertOk();
        }
    }

    /**
     * The middleware is declared once around the whole of routes/admin.php, so
     * a sample of screens from either end of it is enough to show it is on.
     */
    public function test_the_admin_panel_is_closed_to_non_admins(): void
    {
        $this->assertNonAdminIsTurnedAway(route('admin.home.index'));
        $this->assertNonAdminIsTurnedAway(route('admin.games.games.index'));
        $this->assertNonAdminIsTurnedAway(route('admin.users.users.index'));
        $this->assertNonAdminIsTurnedAway(route('admin.menus.sets.index'));
        $this->assertNonAdminIsTurnedAway(route('admin.ajax.games'));
    }

    /**
     * A signed-out visitor gets the same treatment as a signed-in one without
     * the permission: the admin panel never announces itself.
     */
    public function test_the_admin_panel_is_closed_to_guests(): void
    {
        auth()->logout();

        $this->get(route('admin.home.index'))->assertRedirect('/');
    }
}
