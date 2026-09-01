<?php

namespace Tests\Feature\Admin\Tables;

use App\Livewire\Admin\ArticlesTable;
use App\Livewire\Admin\CommentsTable;
use App\Livewire\Admin\CrewsTable;
use App\Livewire\Admin\Games\GameCompaniesTable;
use App\Livewire\Admin\Games\GameIndividualsTable;
use App\Livewire\Admin\Games\GameSeriesTable;
use App\Livewire\Admin\Games\GameSubmissionsTable;
use App\Livewire\Admin\InterviewsTable;
use App\Livewire\Admin\LinkCategoriesTable;
use App\Livewire\Admin\LinksTable;
use App\Livewire\Admin\MagazineIssuesTable;
use App\Livewire\Admin\MagazinesTable;
use App\Livewire\Admin\NewsSubmissionsTable;
use App\Livewire\Admin\SoftwareTable;
use App\Livewire\Admin\SpotlightsTable;
use App\Livewire\Admin\UsersTable;
use App\Models\Article;
use App\Models\Crew;
use App\Models\Game;
use App\Models\GameSeries;
use App\Models\GameSubmitInfo;
use App\Models\Individual;
use App\Models\Interview;
use App\Models\Magazine;
use App\Models\MagazineIssue;
use App\Models\MenuSoftware;
use App\Models\MenuSoftwareContentType;
use App\Models\NewsSubmission;
use App\Models\PubDev;
use App\Models\Screenshot;
use App\Models\Spotlight;
use App\Models\User;
use App\Models\Website;
use App\Models\WebsiteCategory;
use Carbon\Carbon;
use Livewire\Livewire;
use Tests\Feature\Admin\AdminTestCase;

/**
 * The Livewire datatables behind the admin lists.
 *
 * Each one is asked the three questions that actually break: does it come back
 * in the order it says it sorts by, does its search match, and does each filter
 * it declares narrow the list. The tables covered by ChangelogTableTest and by
 * the controller tests rendering their index pages are not repeated here.
 */
class AdminTablesTest extends AdminTestCase
{
    // Users

    public function test_the_users_table_sorts_searches_and_filters(): void
    {
        User::factory()->create(['userid' => 'Alice']);
        User::factory()->create(['userid' => 'Bob']);

        Livewire::test(UsersTable::class)->assertSeeInOrder(['Alice', 'Bob']);

        Livewire::test(UsersTable::class)
            ->set('search', 'Ali')
            ->assertSee('Alice')
            ->assertDontSee('Bob');
    }

    /**
     * The keys below are the ones the package derives from each filter's label
     * - 'E-mail verified' becomes 'e-mail_verified' - not the array keys in the
     * table's filters() method. The rendered control binds to the derived key
     * too, so the admin panel is unaffected; it only matters here, where a test
     * setting the array key would quietly filter nothing and look like a filter
     * that matches everything.
     */
    public function test_the_users_table_filters_on_verified_and_admin(): void
    {
        User::factory()->create(['userid' => 'Alpha']);
        User::factory()->unverified()->create(['userid' => 'Beta']);
        User::factory()->admin()->create(['userid' => 'Boss']);

        Livewire::test(UsersTable::class)
            ->set('filterComponents.e-mail_verified', 'no')
            ->assertSee('Beta')
            ->assertDontSee('Alpha');

        Livewire::test(UsersTable::class)
            ->set('filterComponents.is_admin', 'yes')
            ->assertSee('Boss')
            ->assertDontSee('Beta');
    }

    /**
     * Join date and last visit are unix timestamps kept in varchar columns, so
     * they have to be sorted numerically rather than as text - '9' must not
     * come after '10'.
     */
    public function test_the_users_table_sorts_dates_numerically(): void
    {
        User::factory()->create(['userid' => 'Older', 'join_date' => '999999999']);
        User::factory()->create(['userid' => 'Newer', 'join_date' => '1000000000']);

        Livewire::test(UsersTable::class)
            ->call('sortBy', 'join_date')
            ->assertSeeInOrder(['Older', 'Newer']);
    }

    // Comments

    public function test_the_comments_table_lists_searches_and_filters(): void
    {
        $game = Game::factory()->named('Xenon')->create();
        $author = User::factory()->create(['userid' => 'Alice']);

        $this->actingAs($author)->post(route('games.comment', $game), ['comment' => 'Still holds up.']);
        $this->actingAs($this->admin);

        Livewire::test(CommentsTable::class)
            ->assertSee('Still holds up.')
            ->assertSee('Xenon');

        Livewire::test(CommentsTable::class)
            ->set('search', 'holds')
            ->assertSee('Still holds up.');

        Livewire::test(CommentsTable::class)
            ->set('filterComponents.type', 'games')
            ->assertSee('Still holds up.');

        Livewire::test(CommentsTable::class)
            ->set('filterComponents.author', strval($author->getKey()))
            ->assertSee('Still holds up.');
    }

    public function test_the_comments_table_can_sort_by_date(): void
    {
        $game = Game::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('games.comment', $game), ['comment' => 'The only one.']);
        $this->actingAs($this->admin);

        Livewire::test(CommentsTable::class)
            ->call('sortBy', 'timestamp')
            ->assertSee('The only one.');
    }

    // Game submissions

    private function submission(string $gameName, int $done = GameSubmitInfo::SUBMISSION_NEW): GameSubmitInfo
    {
        $submission = new GameSubmitInfo();
        $submission->game_id = Game::factory()->named($gameName)->create()->getKey();
        $submission->user_id = User::factory()->create()->getKey();
        $submission->text = 'Something is wrong with ' . $gameName;
        $submission->timestamp = time();
        $submission->game_done = $done;
        $submission->save();

        return $submission;
    }

    /**
     * The keys are 'processed' and 'attachments' because GameSubmissionsTable
     * passes them to SelectFilter::make(). They used to be derived from the
     * filters' names - 'reviewed' and 'has_attachments' - which is what this
     * test was written against, and which is why the table's own
     * `public array $filters = ['processed' => 'no']` default never applied.
     */
    public function test_the_submissions_table_filters_on_reviewed_and_attachments(): void
    {
        $new = $this->submission('Xenon');
        $this->submission('Turrican', GameSubmitInfo::SUBMISSION_REVIEWED);

        Livewire::test(GameSubmissionsTable::class)
            ->set('filterComponents.processed', 'no')
            ->assertSee('Xenon')
            ->assertDontSee('Turrican');

        Livewire::test(GameSubmissionsTable::class)
            ->set('filterComponents.processed', 'yes')
            ->assertSee('Turrican')
            ->assertDontSee('Xenon');

        $new->screenshots()->save(Screenshot::factory()->create());

        Livewire::test(GameSubmissionsTable::class)
            ->set('filterComponents.attachments', 'yes')
            ->assertSee('Xenon')
            ->assertDontSee('Turrican');

        Livewire::test(GameSubmissionsTable::class)
            ->set('filterComponents.attachments', 'no')
            ->assertSee('Turrican')
            ->assertDontSee('Xenon');
    }

    public function test_the_submissions_table_sorts_dates_numerically(): void
    {
        $this->submission('Xenon');

        Livewire::test(GameSubmissionsTable::class)
            ->call('sortBy', 'timestamp')
            ->assertSee('Xenon');
    }

    // Menu software and crews

    public function test_the_software_table_sorts_searches_and_filters_by_type(): void
    {
        $game = MenuSoftwareContentType::where('name', 'Game')->sole();
        $demo = MenuSoftwareContentType::where('name', 'Demo')->sole();

        MenuSoftware::factory()->named('Xtracker')->create([
            'menu_software_content_type_id' => $demo->getKey(),
        ]);
        MenuSoftware::factory()->named('Arkanoid ripper')->create([
            'menu_software_content_type_id' => $game->getKey(),
        ]);

        Livewire::test(SoftwareTable::class)->assertSeeInOrder(['Arkanoid ripper', 'Xtracker']);

        Livewire::test(SoftwareTable::class)
            ->set('search', 'Xtra')
            ->assertSee('Xtracker')
            ->assertDontSee('Arkanoid ripper');

        Livewire::test(SoftwareTable::class)
            ->set('filterComponents.type', strval($demo->getKey()))
            ->assertSee('Xtracker')
            ->assertDontSee('Arkanoid ripper');
    }

    public function test_the_crews_table_sorts_and_searches(): void
    {
        Crew::factory()->create(['name' => 'Automation']);
        Crew::factory()->create(['name' => 'The Replicants']);

        Livewire::test(CrewsTable::class)->assertSeeInOrder(['Automation', 'The Replicants']);

        Livewire::test(CrewsTable::class)
            ->set('search', 'Replic')
            ->assertSee('The Replicants')
            ->assertDontSee('Automation');
    }

    // Individuals, companies and series

    public function test_the_individuals_table_sorts_and_searches(): void
    {
        Individual::factory()->create(['name' => 'Alice Coder']);
        Individual::factory()->create(['name' => 'Bob Musician']);

        Livewire::test(GameIndividualsTable::class)->assertSeeInOrder(['Alice Coder', 'Bob Musician']);

        Livewire::test(GameIndividualsTable::class)
            ->set('search', 'Musician')
            ->assertSee('Bob Musician')
            ->assertDontSee('Alice Coder');
    }

    public function test_the_companies_table_filters_on_having_a_logo(): void
    {
        PubDev::factory()->create(['name' => 'Ocean', 'imgext' => 'png']);
        PubDev::factory()->create(['name' => 'US Gold', 'imgext' => null]);

        Livewire::test(GameCompaniesTable::class)->assertSeeInOrder(['Ocean', 'US Gold']);

        Livewire::test(GameCompaniesTable::class)
            ->set('filterComponents.logo', 'true')
            ->assertSee('Ocean')
            ->assertDontSee('US Gold');

        Livewire::test(GameCompaniesTable::class)
            ->set('filterComponents.logo', 'false')
            ->assertSee('US Gold')
            ->assertDontSee('Ocean');
    }

    public function test_the_series_table_sorts_and_searches(): void
    {
        GameSeries::forceCreate(['name' => 'Bubble Bobble series']);
        GameSeries::forceCreate(['name' => 'Xenon series']);

        Livewire::test(GameSeriesTable::class)->assertSeeInOrder(['Bubble Bobble series', 'Xenon series']);

        Livewire::test(GameSeriesTable::class)
            ->set('search', 'Xenon')
            ->assertSee('Xenon series')
            ->assertDontSee('Bubble Bobble series');
    }

    // Links

    public function test_the_links_table_filters_by_category_and_status(): void
    {
        $emulation = WebsiteCategory::factory()->create(['name' => 'Emulation']);

        $hatari = Website::factory()->create(['name' => 'Hatari']);
        $hatari->categories()->attach($emulation);

        Website::factory()->inactive()->create(['name' => 'Dead link']);

        Livewire::test(LinksTable::class)->assertSeeInOrder(['Dead link', 'Hatari']);

        Livewire::test(LinksTable::class)
            ->set('filterComponents.category', strval($emulation->getKey()))
            ->assertSee('Hatari')
            ->assertDontSee('Dead link');

        Livewire::test(LinksTable::class)
            ->set('filterComponents.status', '1')
            ->assertSee('Dead link')
            ->assertDontSee('Hatari');

        Livewire::test(LinksTable::class)
            ->set('search', 'Hata')
            ->assertSee('Hatari')
            ->assertDontSee('Dead link');
    }

    public function test_the_link_categories_table_counts_its_links(): void
    {
        $category = WebsiteCategory::factory()->create(['name' => 'Emulation']);
        Website::factory()->create()->categories()->attach($category);

        WebsiteCategory::factory()->create(['name' => 'Archives']);

        Livewire::test(LinkCategoriesTable::class)->assertSeeInOrder(['Archives', 'Emulation']);

        $rows = Livewire::test(LinkCategoriesTable::class)->instance()->builder()->get();

        $this->assertSame(1, $rows->firstWhere('name', 'Emulation')->websites_count);
        $this->assertSame(0, $rows->firstWhere('name', 'Archives')->websites_count);
    }

    // Magazines

    /**
     * The search callback used to build a second closure and return it instead
     * of constraining the query, so the magazine search box did nothing at all.
     */
    public function test_the_magazines_table_sorts_and_searches(): void
    {
        Magazine::factory()->create(['name' => 'Atari ST User']);
        Magazine::factory()->create(['name' => 'ST Format']);

        Livewire::test(MagazinesTable::class)->assertSeeInOrder(['Atari ST User', 'ST Format']);

        Livewire::test(MagazinesTable::class)
            ->set('search', 'Format')
            ->assertSee('ST Format')
            ->assertDontSee('Atari ST User');
    }

    /**
     * The issues table is scoped to one magazine, so another magazine's issues
     * must not appear in it.
     */
    public function test_the_issues_table_is_scoped_to_its_magazine(): void
    {
        $magazine = Magazine::factory()->create(['name' => 'ST Format']);
        $other = Magazine::factory()->create(['name' => 'Atari ST User']);

        MagazineIssue::factory()->create(['magazine_id' => $magazine->getKey(), 'issue' => 7]);
        MagazineIssue::factory()->create(['magazine_id' => $other->getKey(), 'issue' => 99]);

        $rows = Livewire::test(MagazineIssuesTable::class, ['magazine' => $magazine->getKey()])
            ->instance()
            ->builder()
            ->get();

        $this->assertSame([7], $rows->pluck('issue')->all());
    }

    // News submissions and spotlights

    /**
     * The queue is meant to come back newest first. The Date column carried no
     * sortable() call, so configure()'s default sort on it never applied and
     * submissions arrived in insertion order.
     */
    public function test_the_news_submissions_table_lists_newest_first(): void
    {
        NewsSubmission::forceCreate([
            'headline' => 'Older submission',
            'text'     => 'Text',
            'user_id'       => User::factory()->create()->getKey(),
            'date'     => strtotime('2026-01-01'),
        ]);
        NewsSubmission::forceCreate([
            'headline' => 'Newer submission',
            'text'     => 'Text',
            'user_id'       => User::factory()->create()->getKey(),
            'date'     => strtotime('2026-06-01'),
        ]);

        Livewire::test(NewsSubmissionsTable::class)
            ->assertSeeInOrder(['Newer submission', 'Older submission']);

        Livewire::test(NewsSubmissionsTable::class)
            ->set('search', 'Older')
            ->assertSee('Older submission')
            ->assertDontSee('Newer submission');
    }

    public function test_the_spotlights_table_sorts_and_searches(): void
    {
        Spotlight::forceCreate(['text' => 'A new dump', 'link' => 'https://example.org']);
        Spotlight::forceCreate(['text' => 'Zero day release', 'link' => 'https://example.org']);

        Livewire::test(SpotlightsTable::class)->assertSeeInOrder(['A new dump', 'Zero day release']);

        Livewire::test(SpotlightsTable::class)
            ->set('search', 'Zero')
            ->assertSee('Zero day release')
            ->assertDontSee('A new dump');
    }

    /**
     * The only assertion in this file about a *rendered date*, and it is here
     * for a reason. articles.date is an integer timestamp with a
     * `datetime:timestamp` cast, and the column used to be read off a join,
     * where it arrived raw and was passed through Carbon::createFromTimestamp().
     * Handing that method a Carbon does not throw on Carbon 3 -- it stringifies
     * the date and sums the digits, rendering "Jan 1, 1970" in every row. So a
     * regression here is silent everywhere else: the page is still a 200, the
     * markup is still well formed, and only the date is wrong.
     */
    public function test_the_articles_table_renders_the_date(): void
    {
        Article::factory()->titled('Coding the blitter')->create([
            'date' => Carbon::parse('2018-01-21')->timestamp,
        ]);

        Livewire::test(ArticlesTable::class)
            ->assertSee('Coding the blitter')
            ->assertSee('Jan 21, 2018');
    }

    /**
     * The interviews table left joins individuals and selects
     * interviews.* alongside individuals.name. An interview with no
     * subject is the row that used to null out the primary key of every row
     * in a join like this one, by hydrating the model from the joined table's
     * id -- silently, with no exception and nothing in the log. So this
     * asserts the edit link, which is built from getKey(), rather than the
     * rendered name.
     *
     * The E2E fixture used to carry a subject-less interview for the same
     * reason. It cannot any more: before interviews.text was merged in, the
     * public list inner joined it and never saw such a row, and now it would
     * render one and fail on the missing name. This assertion is the better
     * home for it -- it names the id it expects, where the fixture only
     * proved the page was a 200.
     */
    public function test_the_interviews_table_keeps_its_own_key_when_the_subject_is_missing(): void
    {
        $interview = Interview::factory()->create(['individual_id' => null]);

        Livewire::test(InterviewsTable::class)
            ->assertSee(route('admin.interviews.interviews.edit', $interview->getKey()), escape: false);
    }

    /**
     * Each table links its first column at the row's edit screen. A broken
     * route here makes the whole list unusable, and it is easy to miss.
     */
    public function test_every_table_links_its_rows_to_an_edit_screen(): void
    {
        $crew = Crew::factory()->create(['name' => 'The Replicants']);
        $individual = Individual::factory()->create(['name' => 'Someone']);
        $company = PubDev::factory()->create(['name' => 'Ocean']);
        $link = Website::factory()->create(['name' => 'Hatari']);

        Livewire::test(CrewsTable::class)
            ->assertSee(route('admin.menus.crews.edit', $crew), escape: false);

        Livewire::test(GameIndividualsTable::class)
            ->assertSee(route('admin.games.individuals.edit', $individual), escape: false);

        Livewire::test(GameCompaniesTable::class)
            ->assertSee(route('admin.games.companies.edit', $company), escape: false);

        Livewire::test(LinksTable::class)
            ->assertSee(route('admin.links.links.edit', $link), escape: false);
    }
}
