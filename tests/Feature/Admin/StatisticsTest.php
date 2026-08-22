<?php

namespace Tests\Feature\Admin;

use App\Helpers\AdminStatisticsHelper;
use App\Models\Changelog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StatisticsTest extends TestCase
{
    use RefreshDatabase;

    private function entry(string $action, string $section, string $date): Changelog
    {
        return Changelog::create([
            'action'           => $action,
            'section'          => $section,
            'section_id'       => 1,
            'section_name'     => 'Xenon',
            'sub_section'      => '',
            'sub_section_id'   => 0,
            'sub_section_name' => '',
            'user_id'          => 1,
            'timestamp'        => Carbon::parse($date . ' 12:00:00')->timestamp,
        ]);
    }

    private function game(int $id, string $name): void
    {
        DB::table('game')->insert([
            'game_id'   => $id,
            'game_name' => $name,
            'slug'      => strtolower($name),
        ]);
    }

    /**
     * The legacy Add / Edit / Delete shot values fold into the three current ones.
     */
    public function test_legacy_changelog_actions_are_folded(): void
    {
        $this->entry('Add', 'Games', Carbon::now()->format('Y-m-d'));
        $this->entry('Edit', 'Games', Carbon::now()->format('Y-m-d'));
        $this->entry('Delete shot', 'Games', Carbon::now()->format('Y-m-d'));
        $this->entry(Changelog::INSERT, 'Games', Carbon::now()->format('Y-m-d'));

        $datasets = collect(AdminStatisticsHelper::changesByMonth()['datasets'])
            ->mapWithKeys(fn ($dataset) => [$dataset['label'] => array_sum($dataset['data'])]);

        $this->assertSame([
            Changelog::INSERT => 2,
            Changelog::UPDATE => 1,
            Changelog::DELETE => 1,
        ], $datasets->all());
    }

    /**
     * Sections are counted as they are stored. The "Menu Softwre" typo used to
     * be folded on read; it is corrected in the data instead, by
     * 2026_08_10_120000_fix_menu_software_changelog_section.
     */
    public function test_changes_are_counted_by_section(): void
    {
        $this->entry(Changelog::INSERT, 'Menu Software', '2025-01-01');
        $this->entry(Changelog::INSERT, 'Menu Software', '2025-01-01');
        $this->entry(Changelog::INSERT, 'Games', '2025-01-01');

        $sections = AdminStatisticsHelper::changesBySection();
        $counts = array_combine($sections['labels'], $sections['data']);

        $this->assertSame(['Menu Software' => 2, 'Games' => 1], $counts);
    }

    /**
     * Busiest first, and no more than asked for.
     */
    public function test_only_the_busiest_sections_are_charted(): void
    {
        $this->entry(Changelog::INSERT, 'Games', '2025-01-01');
        $this->entry(Changelog::INSERT, 'Games', '2025-01-02');
        $this->entry(Changelog::INSERT, 'Reviews', '2025-01-03');
        $this->entry(Changelog::INSERT, 'Menus', '2025-01-04');

        $sections = AdminStatisticsHelper::changesBySection(limit: 2);

        $this->assertCount(2, $sections['labels']);
        $this->assertSame('Games', $sections['labels'][0]);
        $this->assertSame(2, $sections['data'][0]);
    }

    /**
     * Years with no changes at all still get a bar, so the axis stays continuous.
     */
    public function test_changes_by_year_has_no_gaps(): void
    {
        $this->entry(Changelog::INSERT, 'Games', '2020-05-01');
        $this->entry(Changelog::INSERT, 'Games', '2022-05-01');

        $this->assertSame(
            ['labels' => ['2020', '2021', '2022'], 'data' => [1, 0, 1]],
            AdminStatisticsHelper::changesByYear()
        );
    }

    public function test_coverage_reports_counts_and_percentages(): void
    {
        $this->game(1, 'Xenon');
        $this->game(2, 'Bubble Bobble');
        $this->game(3, 'Rick Dangerous');
        $this->game(4, 'Turrican');

        DB::table('screenshot_main')->insert(['id' => 1, 'imgext' => 'png']);
        DB::table('screenshot_game')->insert(['game_id' => 1, 'screenshot_id' => 1]);

        $games = collect(AdminStatisticsHelper::coverage()['Games'])
            ->firstWhere('label', 'With screenshots');

        $this->assertSame(1, $games['count']);
        $this->assertSame(4, $games['total']);
        $this->assertSame(25.0, $games['percent']);
    }

    /**
     * A game with two screenshots is still only one game with screenshots.
     */
    public function test_coverage_counts_each_game_once(): void
    {
        $this->game(1, 'Xenon');

        DB::table('screenshot_main')->insert([
            ['id' => 1, 'imgext' => 'png'],
            ['id' => 2, 'imgext' => 'png'],
        ]);
        DB::table('screenshot_game')->insert([
            ['game_id' => 1, 'screenshot_id' => 1],
            ['game_id' => 1, 'screenshot_id' => 2],
        ]);

        $games = collect(AdminStatisticsHelper::coverage()['Games'])
            ->firstWhere('label', 'With screenshots');

        $this->assertSame(1, $games['count']);
    }

    /**
     * Nearly every individual has an individual_text row, so the row itself must
     * not be taken to mean a bio was written.
     */
    public function test_coverage_ignores_individuals_without_a_bio(): void
    {
        DB::table('individuals')->insert([
            ['id' => 1, 'ind_name' => 'Alice'],
            ['id' => 2, 'ind_name' => 'Bob'],
            ['id' => 3, 'ind_name' => 'Carol'],
            ['id' => 4, 'ind_name' => 'Dave'],
        ]);

        DB::table('individual_text')->insert([
            ['ind_id' => 1, 'ind_profile' => 'Member in Dune', 'ind_email' => null],
            ['ind_id' => 2, 'ind_profile' => null, 'ind_email' => 'bob@example.org'],
            ['ind_id' => 3, 'ind_profile' => null, 'ind_email' => null],
        ]);

        $bios = collect(AdminStatisticsHelper::coverage()['Other'])
            ->firstWhere('label', 'Individuals with a bio');

        $this->assertSame(1, $bios['count']);
        $this->assertSame(4, $bios['total']);
    }

    /**
     * Undated releases are excluded from the chart but reported alongside it.
     */
    public function test_releases_by_year_reports_undated_releases(): void
    {
        $this->game(1, 'Xenon');

        DB::table('game_release')->insert([
            ['game_id' => 1, 'name' => 'Original', 'date' => '1988-01-01'],
            ['game_id' => 1, 'name' => 'Re-release', 'date' => '1990-01-01'],
            ['game_id' => 1, 'name' => 'Unknown', 'date' => null],
        ]);

        $releases = AdminStatisticsHelper::releasesByYear();

        $this->assertSame(['1988', '1989', '1990'], $releases['labels']);
        $this->assertSame([1, 0, 1], $releases['data']);
        $this->assertSame(1, $releases['undated']);
    }

    /**
     * The SNDH archive holds years such as 0 and 2107, which must not reach the chart.
     */
    public function test_out_of_range_sndh_years_are_ignored(): void
    {
        // Migrations ship the real SNDH archive, so start from a known state
        DB::table('game_sndh')->delete();
        DB::table('sndhs')->delete();
        DB::table('sndh_archives')->delete();

        DB::table('sndh_archives')->insert(['id' => 'main', 'download_url' => 'https://example.org']);

        foreach ([0, 1990, 1991, 2107] as $index => $year) {
            DB::table('sndhs')->insert([
                'id'               => 'tune-' . $index,
                'title'            => 'Tune ' . $index,
                'year'             => $year,
                'sndh_archive_id'  => 'main',
            ]);
        }

        $this->assertSame(
            ['labels' => ['1990', '1991'], 'data' => [1, 1]],
            AdminStatisticsHelper::sndhByYear()
        );
    }

    /**
     * Every score has a bar, including the ones nobody picked.
     */
    public function test_vote_distribution_covers_every_score(): void
    {
        $this->game(1, 'Xenon');
        $user = User::factory()->create();

        DB::table('game_votes')->insert([
            'game_id' => 1, 'user_id' => $user->getKey(), 'score' => 4,
        ]);

        $this->assertSame(
            ['labels' => ['Terrible', 'Bad', 'Average', 'Good', 'Awesome'], 'data' => [0, 0, 0, 0, 1]],
            AdminStatisticsHelper::voteDistribution()
        );
    }

    public function test_admin_page_loads(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.others.statistics.index'))
            ->assertOk()
            ->assertSee('Coverage')
            ->assertSee('All counts');
    }

    /**
     * The home page and the statistics page must not drift apart: both take
     * their tiles from AdminStatisticsHelper::headlines().
     */
    public function test_home_and_statistics_share_their_headline_figures(): void
    {
        $this->game(1, 'Xenon');
        $this->game(2, 'Turrican');

        $admin = User::factory()->admin()->create();

        $headlines = AdminStatisticsHelper::headlines();
        $this->assertSame(2, $headlines['Games']);

        $this->actingAs($admin)
            ->get(route('admin.others.statistics.index'))
            ->assertViewHas('headlines', $headlines);

        $this->actingAs($admin)
            ->get(route('admin.home.index'))
            ->assertViewHas('stats', $headlines);
    }

    public function test_non_admins_are_turned_away(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.others.statistics.index'))
            ->assertRedirect('/');
    }
}
