<?php

namespace App\Helpers;

use App\Models\Changelog;
use App\Models\GameVote;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Aggregates for the admin statistics dashboard.
 *
 * All queries here must run on both MySQL and the SQLite in-memory database used
 * by the test suite, so date functions such as FROM_UNIXTIME() and DATE_FORMAT()
 * are never used. Categorical breakdowns are grouped in SQL; time series pluck
 * the raw date column in a single query and are bucketed in PHP.
 */
class AdminStatisticsHelper
{
    /**
     * Legacy values found in change_log.action, mapped onto the current constants.
     */
    const ACTION_ALIASES = [
        'Add'         => Changelog::INSERT,
        'Edit'        => Changelog::UPDATE,
        'Delete shot' => Changelog::DELETE,
    ];

    /**
     * The lowest and highest year we accept from user-entered year columns.
     */
    const YEAR_MIN = 1980;
    const YEAR_MAX = 2030;

    /**
     * The shared `draft` flag on reviews, interviews and articles.
     */
    const PUBLISHED = 0;
    const DRAFT = 1;

    /**
     * Headline figures, shown as stat tiles at the top of the page.
     *
     * @return array Map of label => count
     */
    public static function headlines()
    {
        return [
            'Games'       => DB::table('game')->count(),
            'Releases'    => DB::table('game_release')->count(),
            'Screenshots' => DB::table('screenshots')->count(),
            'Individuals' => DB::table('individuals')->count(),
            'Companies'   => DB::table('pub_dev')->count(),
            'Users'       => DB::table('users')->count(),
        ];
    }

    /**
     * Row counts across the whole database, grouped by domain.
     *
     * @return array Map of group name => (map of label => count)
     */
    public static function counts()
    {
        return [
            'Games & releases' => [
                'Games'              => DB::table('game')->count(),
                'Releases'           => DB::table('game_release')->count(),
                'Alternative titles' => DB::table('game_aka')->count(),
                'Game facts'         => DB::table('game_fact')->count(),
                'Series'             => DB::table('game_series')->count(),
                'Videos'             => DB::table('game_videos')->count(),
                'Similar game links' => DB::table('game_similar')->count(),
                'Game submissions'   => DB::table('game_submitinfo')->count(),
            ],
            'Media' => [
                'Game screenshots'      => DB::table('screenshot_game')->count(),
                'Game fact screenshots' => DB::table('screenshot_game_fact')->count(),
                'Review screenshots'    => DB::table('screenshot_review')->count(),
                'Interview screenshots' => DB::table('screenshot_interview')->count(),
                'Article screenshots'   => DB::table('screenshot_article')->count(),
                'Release scans'         => DB::table('game_release_scan')->count(),
                'Media'                 => DB::table('media')->count(),
                'Media scans'           => DB::table('media_scan')->count(),
                'Dumps'                 => DB::table('dump')->count(),
            ],
            'Menus' => [
                'Menu sets'       => DB::table('menu_sets')->count(),
                'Menus'           => DB::table('menus')->count(),
                'Menu disks'      => DB::table('menu_disks')->count(),
                'Disk contents'   => DB::table('menu_disk_contents')->count(),
                'Disk dumps'      => DB::table('menu_disk_dumps')->count(),
                'Screenshots'     => DB::table('menu_disk_screenshots')->count(),
                'Software titles' => DB::table('menu_software')->count(),
            ],
            'Music' => [
                'SNDH files'         => DB::table('sndhs')->count(),
                'SNDH archives'      => DB::table('sndh_archives')->count(),
                'Games with music'   => DB::table('game_sndh')->distinct('game_id')->count(),
                'Game / music links' => DB::table('game_sndh')->count(),
            ],
            'Content' => [
                'Reviews'                => DB::table('reviews')->where('draft', self::PUBLISHED)->count(),
                'Reviews (draft)'        => DB::table('reviews')->where('draft', self::DRAFT)->count(),
                'Interviews'             => DB::table('interviews')->where('draft', self::PUBLISHED)->count(),
                'Interviews (draft)'     => DB::table('interviews')->where('draft', self::DRAFT)->count(),
                'Articles'               => DB::table('articles')->where('draft', self::PUBLISHED)->count(),
                'Articles (draft)'       => DB::table('articles')->where('draft', self::DRAFT)->count(),
                'News items'             => DB::table('news')->count(),
                'Did you know?'          => DB::table('trivia')->count(),
                'Quotes'                 => DB::table('trivia_quotes')->count(),
                'Spotlights'             => DB::table('spotlight')->count(),
                'Magazines'              => DB::table('magazines')->count(),
                'Magazine issues'        => DB::table('magazine_issues')->count(),
                'Magazine index entries' => DB::table('magazine_indices')->count(),
                'Links'                  => DB::table('website')->count(),
                'Links pending review'   => DB::table('website_validate')->count(),
            ],
            'People & companies' => [
                'Individuals'            => DB::table('individuals')->count(),
                'Individuals with bio'   => self::countWithText('individuals', 'ind_profile', 'id'),
                'Nicknames'              => DB::table('individual_nicks')->count(),
                'Crews'                  => DB::table('crew')->count(),
                'Sub-crews'              => DB::table('sub_crew')->count(),
                'Crew members'           => DB::table('crew_individual')->count(),
                'Companies'              => DB::table('pub_dev')->count(),
                'Companies with profile' => self::countWithText('pub_dev', 'pub_dev_profile', 'id'),
            ],
            'Community' => [
                'Registered users'  => DB::table('users')->count(),
                'Active users'      => DB::table('users')->where('inactive', User::ACTIVE)->count(),
                'Inactive users'    => DB::table('users')->where('inactive', User::INACTIVE)->count(),
                'Verified users'    => DB::table('users')->whereNotNull('email_verified_at')->where('inactive', User::ACTIVE)->count(),
                'Administrators'    => DB::table('users')->where('permission', User::PERMISSION_ADMIN)->count(),
                'Comments'          => DB::table('comments')->count(),
                'Votes'             => DB::table('game_votes')->count(),
                'News submissions'  => DB::table('news_submission')->count(),
            ],
        ];
    }

    /**
     * How complete the database is, expressed as "n of total" for each metric.
     *
     * @return array Map of group name => list of ['label', 'count', 'total', 'percent']
     */
    public static function coverage()
    {
        $games = DB::table('game')->count();
        $releases = DB::table('game_release')->count();
        $individuals = DB::table('individuals')->count();
        $companies = DB::table('pub_dev')->count();
        $menuDisks = DB::table('menu_disks')->count();
        $sndhs = DB::table('sndhs')->count();

        return [
            'Games' => [
                self::coverageRow('With a release', DB::table('game_release')->distinct('game_id')->count(), $games),
                self::coverageRow('With screenshots', DB::table('screenshot_game')->distinct('game_id')->count(), $games),
                self::coverageRow('With a genre', DB::table('game_genre_cross')->distinct('game_id')->count(), $games),
                self::coverageRow('With a developer', DB::table('game_developer')->distinct('game_id')->count(), $games),
                self::coverageRow('With a publisher', DB::table('game_release')->whereNotNull('pub_dev_id')->distinct('game_id')->count(), $games),
                self::coverageRow('With creators', DB::table('game_individual')->distinct('game_id')->count(), $games),
                self::coverageRow('With music', DB::table('game_sndh')->distinct('game_id')->count(), $games),
                self::coverageRow('With a review', DB::table('review_game')->distinct('game_id')->count(), $games),
                self::coverageRow('With a magazine index entry', DB::table('magazine_indices')->whereNotNull('game_id')->distinct('game_id')->count(), $games),
                self::coverageRow('With an alternative title', DB::table('game_aka')->distinct('game_id')->count(), $games),
                self::coverageRow('With a video', DB::table('game_videos')->distinct('game_id')->count(), $games),
                self::coverageRow('With a cross-reference', DB::table('game_vs')->distinct('atari_id')->count(), $games),
            ],
            'Releases' => [
                self::coverageRow('With a date', DB::table('game_release')->whereNotNull('date')->count(), $releases),
                self::coverageRow('With a publisher', DB::table('game_release')->whereNotNull('pub_dev_id')->count(), $releases),
                self::coverageRow('With a licence', DB::table('game_release')->whereNotNull('license')->count(), $releases),
                self::coverageRow('With scans', DB::table('game_release_scan')->distinct('game_release_id')->count(), $releases),
                self::coverageRow('With a language', DB::table('game_release_language')->distinct('game_release_id')->count(), $releases),
                self::coverageRow('With a location', DB::table('game_release_location')->distinct('game_release_id')->count(), $releases),
                self::coverageRow('With media', DB::table('media')->distinct('game_release_id')->count(), $releases),
            ],
            'Other' => [
                self::coverageRow('Individuals with a bio', self::countWithText('individuals', 'ind_profile', 'id'), $individuals),
                self::coverageRow('Companies with a profile', self::countWithText('pub_dev', 'pub_dev_profile', 'id'), $companies),
                self::coverageRow('Menu disks with a dump', DB::table('menu_disks')->whereNotNull('menu_disk_dump_id')->count(), $menuDisks),
                self::coverageRow('Menu disks with a screenshot', DB::table('menu_disk_screenshots')->distinct('menu_disk_id')->count(), $menuDisks),
                self::coverageRow('SNDH files linked to a game', DB::table('game_sndh')->distinct('sndh_id')->count(), $sndhs),
                self::coverageRow('SNDH files with a year', DB::table('sndhs')->whereBetween('year', [self::YEAR_MIN, self::YEAR_MAX])->count(), $sndhs),
            ],
        ];
    }

    /**
     * Database changes per month, split by action.
     *
     * @param  int  $months  Number of months to look back, including the current one
     * @return array ['labels' => string[], 'datasets' => [['label' => string, 'data' => int[]]]]
     */
    public static function changesByMonth($months = 24)
    {
        $from = Carbon::now()->startOfMonth()->subMonths($months - 1);

        $labels = [];
        $buckets = [];
        for ($month = $from->copy(); $month->lessThanOrEqualTo(Carbon::now()); $month->addMonth()) {
            $labels[] = $month->format('Y-m');
            $buckets[$month->format('Y-m')] = 0;
        }

        $actions = [Changelog::INSERT, Changelog::UPDATE, Changelog::DELETE];
        $series = array_fill_keys($actions, $buckets);

        $changes = DB::table('change_log')
            ->select('timestamp', 'action')
            ->where('timestamp', '>=', $from->getTimestamp())
            ->get();

        foreach ($changes as $change) {
            $action = self::ACTION_ALIASES[$change->action] ?? $change->action;
            $bucket = date('Y-m', (int) $change->timestamp);

            if (isset($series[$action][$bucket])) {
                $series[$action][$bucket]++;
            }
        }

        $datasets = [];
        foreach ($series as $action => $counts) {
            $datasets[] = ['label' => $action, 'data' => array_values($counts)];
        }

        return ['labels' => $labels, 'datasets' => $datasets];
    }

    /**
     * Database changes per year, over the whole history of the changelog.
     *
     * @return array ['labels' => string[], 'data' => int[]]
     */
    public static function changesByYear()
    {
        return self::bucketByYear(DB::table('change_log')->pluck('timestamp'));
    }

    /**
     * The most edited sections of the site.
     *
     * @param  int  $limit
     * @return array ['labels' => string[], 'data' => int[]]
     */
    public static function changesBySection($limit = 15)
    {
        $sections = DB::table('change_log')
            ->select('section', DB::raw('count(*) as total'))
            ->groupBy('section')
            ->orderByDesc('total')
            ->limit($limit)
            ->pluck('total', 'section')
            ->all();

        return self::toChartData($sections);
    }

    /**
     * The users who made the most changes.
     *
     * @param  int  $limit
     * @return array ['labels' => string[], 'data' => int[]]
     */
    public static function topContributors($limit = 15)
    {
        $rows = DB::table('change_log')
            ->join('users', 'users.id', '=', 'change_log.user_id')
            ->select('users.userid', DB::raw('count(*) as total'))
            ->groupBy('users.id', 'users.userid')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();

        return self::toChartData($rows->pluck('total', 'userid')->all());
    }

    /**
     * Releases per year, plus how many have no date at all.
     *
     * @return array ['labels' => string[], 'data' => int[], 'undated' => int]
     */
    public static function releasesByYear()
    {
        $dates = DB::table('game_release')->whereNotNull('date')->pluck('date');

        return self::bucketByYear($dates, false) + [
            'undated' => DB::table('game_release')->whereNull('date')->count(),
        ];
    }

    /**
     * How many games are assigned to each genre.
     *
     * @return array ['labels' => string[], 'data' => int[]]
     */
    public static function gamesByGenre()
    {
        $rows = DB::table('game_genre_cross')
            ->join('game_genre', 'game_genre.id', '=', 'game_genre_cross.game_genre_id')
            ->select('game_genre.name', DB::raw('count(*) as total'))
            ->groupBy('game_genre.id', 'game_genre.name')
            ->orderByDesc('total')
            ->get();

        return self::toChartData($rows->pluck('total', 'name')->all());
    }

    /**
     * The companies that published the most releases.
     *
     * @param  int  $limit
     * @return array ['labels' => string[], 'data' => int[]]
     */
    public static function topPublishers($limit = 15)
    {
        $rows = DB::table('game_release')
            ->join('pub_dev', 'pub_dev.id', '=', 'game_release.pub_dev_id')
            ->select('pub_dev.pub_dev_name', DB::raw('count(*) as total'))
            ->groupBy('pub_dev.id', 'pub_dev.pub_dev_name')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();

        return self::toChartData($rows->pluck('total', 'pub_dev_name')->all());
    }

    /**
     * The companies credited as developer on the most games.
     *
     * @param  int  $limit
     * @return array ['labels' => string[], 'data' => int[]]
     */
    public static function topDevelopers($limit = 15)
    {
        $rows = DB::table('game_developer')
            ->join('pub_dev', 'pub_dev.id', '=', 'game_developer.pub_dev_id')
            ->select('pub_dev.pub_dev_name', DB::raw('count(distinct game_developer.game_id) as total'))
            ->groupBy('pub_dev.id', 'pub_dev.pub_dev_name')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();

        return self::toChartData($rows->pluck('total', 'pub_dev_name')->all());
    }

    /**
     * Commercial versus non-commercial releases.
     *
     * @return array ['labels' => string[], 'data' => int[]]
     */
    public static function releasesByLicence()
    {
        return self::toChartData(self::groupByColumn('game_release', 'license'));
    }

    /**
     * The breakdown of release types.
     *
     * @return array ['labels' => string[], 'data' => int[]]
     */
    public static function releasesByType()
    {
        return self::toChartData(self::groupByColumn('game_release', 'type'));
    }

    /**
     * The breakdown of dump file formats.
     *
     * @return array ['labels' => string[], 'data' => int[]]
     */
    public static function dumpsByFormat()
    {
        return self::toChartData(self::groupByColumn('dump', 'format'));
    }

    /**
     * Menu disks per year, based on the date of the menu they belong to.
     *
     * @return array ['labels' => string[], 'data' => int[]]
     */
    public static function menuDisksByYear()
    {
        $dates = DB::table('menu_disks')
            ->join('menus', 'menus.id', '=', 'menu_disks.menu_id')
            ->whereNotNull('menus.date')
            ->pluck('menus.date');

        return self::bucketByYear($dates, false);
    }

    /**
     * SNDH tunes per year, ignoring the out-of-range years in the archive.
     *
     * @return array ['labels' => string[], 'data' => int[]]
     */
    public static function sndhByYear()
    {
        $rows = DB::table('sndhs')
            ->select('year', DB::raw('count(*) as total'))
            ->whereBetween('year', [self::YEAR_MIN, self::YEAR_MAX])
            ->groupBy('year')
            ->orderBy('year')
            ->get();

        return self::fillYears($rows->pluck('total', 'year')->all());
    }

    /**
     * Published content per year, one series per content type.
     *
     * @return array ['labels' => string[], 'datasets' => [['label' => string, 'data' => int[]]]]
     */
    public static function contentByYear()
    {
        $sources = [
            'News'       => DB::table('news')->pluck('news_date'),
            'Reviews'    => DB::table('reviews')->pluck('review_date'),
            'Interviews' => DB::table('interviews')->pluck('interview_date'),
            'Articles'   => DB::table('articles')->pluck('article_date'),
        ];

        $years = [];
        $counted = [];
        foreach ($sources as $label => $timestamps) {
            $counted[$label] = self::countByYear($timestamps);
            $years = array_merge($years, array_keys($counted[$label]));
        }

        $labels = self::yearRange($years);

        $datasets = [];
        foreach ($counted as $label => $counts) {
            $datasets[] = [
                'label' => $label,
                'data'  => array_map(fn ($year) => $counts[$year] ?? 0, $labels),
            ];
        }

        return ['labels' => array_map('strval', $labels), 'datasets' => $datasets];
    }

    /**
     * User sign-ups per year.
     *
     * @return array ['labels' => string[], 'data' => int[]]
     */
    public static function userSignupsByYear()
    {
        return self::bucketByYear(DB::table('users')->pluck('join_date'));
    }

    /**
     * How users rated the games they voted on.
     *
     * @return array ['labels' => string[], 'data' => int[]]
     */
    public static function voteDistribution()
    {
        $rows = DB::table('game_votes')
            ->select('score', DB::raw('count(*) as total'))
            ->groupBy('score')
            ->pluck('total', 'score');

        $data = [];
        foreach (GameVote::LABELS as $score => $label) {
            $data[$label] = $rows[$score] ?? 0;
        }

        return self::toChartData($data);
    }

    /**
     * User comments per year.
     *
     * @return array ['labels' => string[], 'data' => int[]]
     */
    public static function commentsByYear()
    {
        return self::bucketByYear(DB::table('comments')->pluck('timestamp'));
    }

    /**
     * Build a single coverage row.
     *
     * @param  string  $label
     * @param  int  $count
     * @param  int  $total
     * @return array
     */
    private static function coverageRow($label, $count, $total)
    {
        return [
            'label'   => $label,
            'count'   => $count,
            'total'   => $total,
            'percent' => $total > 0 ? round($count / $total * 100, 1) : 0,
        ];
    }

    /**
     * Count the owners whose text column actually holds something.
     *
     * A row exists for every individual and company, so the row itself says
     * nothing about whether a bio was ever written - only a non-NULL profile
     * does. Blank values are NULL rather than an empty string, thanks to the
     * 2026_08_09_100000_normalise_blank_profiles migration and the admin
     * controllers that write these columns.
     *
     * @param  string  $table
     * @param  string  $column
     * @param  string  $owner  Foreign key identifying who the text belongs to
     * @return int
     */
    private static function countWithText($table, $column, $owner)
    {
        return DB::table($table)
            ->whereNotNull($column)
            ->distinct($owner)
            ->count();
    }

    /**
     * Count rows per distinct value of a column, keeping NULLs as "Unknown".
     *
     * @param  string  $table
     * @param  string  $column
     * @return array Map of value => count, ordered by count descending
     */
    private static function groupByColumn($table, $column)
    {
        $rows = DB::table($table)
            ->select($column, DB::raw('count(*) as total'))
            ->groupBy($column)
            ->orderByDesc('total')
            ->get();

        // Legacy rows use both NULL and the empty string to mean "not set"
        $counts = [];
        foreach ($rows as $row) {
            $value = $row->{$column};
            $key = ($value === null || $value === '') ? 'Unknown' : $value;
            $counts[$key] = ($counts[$key] ?? 0) + $row->total;
        }

        arsort($counts);

        return $counts;
    }

    /**
     * Turn a list of dates into a per-year series with no gaps.
     *
     * @param  \Illuminate\Support\Collection  $dates  Unix timestamps, or date strings when $epoch is false
     * @param  bool  $epoch
     * @return array ['labels' => string[], 'data' => int[]]
     */
    private static function bucketByYear($dates, $epoch = true)
    {
        return self::fillYears(self::countByYear($dates, $epoch));
    }

    /**
     * Turn a year => count map into a series covering every year in between, so
     * charts do not silently skip years with no data.
     *
     * @param  array  $counts
     * @return array ['labels' => string[], 'data' => int[]]
     */
    private static function fillYears(array $counts)
    {
        $labels = self::yearRange(array_keys($counts));

        return [
            'labels' => array_map('strval', $labels),
            'data'   => array_map(fn ($year) => (int) ($counts[$year] ?? 0), $labels),
        ];
    }

    /**
     * Count how many of the given dates fall in each year.
     *
     * @param  \Illuminate\Support\Collection  $dates
     * @param  bool  $epoch  Whether the values are unix timestamps rather than date strings
     * @return array Map of year => count
     */
    private static function countByYear($dates, $epoch = true)
    {
        $counts = [];

        foreach ($dates as $date) {
            if ($date === null || $date === '') {
                continue;
            }

            // date() rather than Carbon here: this runs over every row of
            // change_log, where building a Carbon instance per row costs ~700ms.
            $year = $epoch
                ? (int) date('Y', (int) $date)
                : (int) Carbon::parse($date)->year;

            if ($year < self::YEAR_MIN || $year > self::YEAR_MAX) {
                continue;
            }

            $counts[$year] = ($counts[$year] ?? 0) + 1;
        }

        ksort($counts);

        return $counts;
    }

    /**
     * Every year between the smallest and largest of the given years, so charts
     * do not silently skip years with no data.
     *
     * @param  array  $years
     * @return array
     */
    private static function yearRange(array $years)
    {
        if (empty($years)) {
            return [];
        }

        return range(min($years), max($years));
    }

    /**
     * Split a label => count map into the two parallel arrays a chart needs.
     *
     * @param  array  $counts
     * @return array ['labels' => string[], 'data' => int[]]
     */
    private static function toChartData(array $counts)
    {
        return [
            'labels' => array_map('strval', array_keys($counts)),
            'data'   => array_map('intval', array_values($counts)),
        ];
    }
}
