<?php

namespace Database\Seeders;

use App\Helpers\UserHelper;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * The fixture the Playwright suite asserts against.
 *
 * The ids and names below are mirrored in tests/e2e/support/fixture.js, which
 * is what the specs read. Change one, change the other.
 *
 * Rows are written with raw DB::table() inserts rather than the factories in
 * database/factories/, deliberately: several of these tables have no model at
 * all (screenshot_game, article_text, review_game, interview_text,
 * individual_text, game_user_comments, website_category_cross), the factories
 * are random where the specs need fixed names and slugs, and $fillable on the
 * legacy models is thin enough that Model::create() would silently drop
 * columns we depend on.
 */
class E2ESeeder extends Seeder
{
    public const USER_ADMIN_ID = 1;
    public const USER_STANDARD_ID = 2;

    public const GAME_ID = 1;
    public const RELEASE_ID = 1;
    public const SCREENSHOT_ID = 1;
    public const SPOTLIGHT_SCREENSHOT_ID = 2;
    public const RELEASE_SCAN_ID = 1;
    public const GAME_FACT_ID = 1;
    public const GAME_SUBMISSION_ID = 1;
    public const GAME_SERIES_ID = 1;
    public const COMPANY_ID = 1;
    public const INDIVIDUAL_ID = 1;
    public const CREW_ID = 1;

    public const ARTICLE_ID = 1;
    public const REVIEW_ID = 1;
    public const INTERVIEW_ID = 1;
    public const NEWS_ID = 1;
    public const COMMENT_ID = 1;

    public const MAGAZINE_ID = 1;
    public const MAGAZINE_ISSUE_ID = 1;

    public const MENU_SET_ID = 1;
    public const MENU_ID = 1;
    public const MENU_DISK_ID = 1;
    public const MENU_DISK_CONTENT_ID = 1;
    public const MENU_SOFTWARE_ID = 1;

    public const WEBSITE_ID = 1;
    public const WEBSITE_CATEGORY_ID = 1;

    /** Seeded by a migration, not by us. */
    public const MENU_CONDITION_INTACT_ID = 4;
    public const MENU_CONTENT_TYPE_GAME_ID = 1;

    public const GAME_NAME = 'Xenon 2 Megablast';
    public const GAME_SLUG = 'xenon-2-megablast';
    public const ARTICLE_TITLE = 'Playwright Test Article';
    public const INTERVIEW_INDIVIDUAL = 'Playwright Test Individual';
    public const MAGAZINE_NAME = 'Playwright Test Magazine';
    public const MENU_SET_NAME = 'Playwright Test Menu Set';
    public const MENU_SOFTWARE_NAME = 'Playwright Test Software';
    public const NEWS_HEADLINE = 'Welcome to Atari Legend';
    public const COMPANY_NAME = 'Playwright Test Company';
    public const CREW_NAME = 'Playwright Test Crew';
    public const SERIES_NAME = 'Playwright Test Series';
    public const WEBSITE_NAME = 'Playwright Test Website';
    public const WEBSITE_CATEGORY_NAME = 'Playwright Test Category';

    /**
     * An 8x8 opaque PNG, base64-encoded.
     *
     * Deliberately a constant rather than something GD draws: the routes that
     * read these files are the ones most likely to be broken by a GD build
     * missing WebP or FreeType, and a fixture that needs GD to produce its own
     * input cannot tell you that.
     */
    private const PIXEL_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAgAAAAICAYAAADED76LAAAAHElEQVQoz2P8'
        . '//8/AymAiYFEMKphVMOohuGiAQCP2gN/8FnhAgAAAABJRU5ErkJggg==';

    /**
     * Seed the application for End-to-End (E2E) Playwright tests.
     */
    public function run(): void
    {
        // This seeder creates an administrator whose password is public
        // knowledge, and it ships to the servers with the rest of the code.
        // Refuse to run anywhere it could matter.
        if (app()->isProduction()) {
            throw new RuntimeException('E2ESeeder must never run in production.');
        }

        $this->seedUsers();

        // The sample data below writes to row 1 of about twenty tables. On a
        // database restored from a dump that would overwrite real content, so
        // stop at the first sign of one. The users above are keyed on userid
        // and are safe either way.
        if (DB::table('game')->count() > 1) {
            $this->command?->warn('E2ESeeder: existing dataset detected, skipping sample data.');

            return;
        }

        $this->seedGames();
        $this->seedContent();
        $this->seedMagazines();
        $this->seedMenus();
        $this->seedLinks();
        $this->seedOthers();
    }

    private function seedUsers(): void
    {
        $salt = UserHelper::salt();
        $sha512Password = UserHelper::hashPassword('password', $salt);

        // user_id is the primary key and is not fillable, so these rely on
        // insertion order on a fresh database - admin first, hence
        // USER_ADMIN_ID = 1. Keep the order.
        foreach ([
            ['admin', 'admin@atarilegend.com', User::PERMISSION_ADMIN],
            ['testuser', 'test@example.com', User::PERMISSION_USER],
        ] as [$userid, $email, $permission]) {
            User::updateOrCreate(
                ['userid' => $userid],
                [
                    'email'             => $email,
                    'email_verified_at' => now(),
                    'password'          => Hash::make('password'),
                    'salt'              => $salt,
                    'sha512_password'   => $sha512Password,
                    'permission'        => $permission,
                    'inactive'          => User::ACTIVE,
                    'join_date'         => (string) now()->timestamp,
                    'last_visit'        => (string) now()->timestamp,
                    'remember_token'    => Str::random(10),
                    'karma'             => 0,
                    'show_email'        => false,
                ]
            );
        }
    }

    private function seedGames(): void
    {
        $this->insert('game', ['game_id' => self::GAME_ID], [
            'game_name' => self::GAME_NAME,
            'slug'      => self::GAME_SLUG,
        ]);

        // A release needs a date: the link on the game page is labelled with
        // the release year, and an empty label is not clickable.
        $this->insert('game_release', ['id' => self::RELEASE_ID], [
            'game_id' => self::GAME_ID,
            'date'    => '1989-01-01',
            'license' => 'Commercial',
        ]);

        $this->insert('screenshot_main', ['screenshot_id' => self::SCREENSHOT_ID], ['imgext' => 'png']);
        $this->insert('screenshot_game', [
            'game_id'       => self::GAME_ID,
            'screenshot_id' => self::SCREENSHOT_ID,
        ], []);
        $this->seedImage('images/game_screenshots/' . self::SCREENSHOT_ID . '.png');

        $this->insert('game_release_scan', ['id' => self::RELEASE_SCAN_ID], [
            'game_release_id' => self::RELEASE_ID,
            'type'            => 'Box front',
            'imgext'          => 'png',
        ]);
        $this->seedImage('images/game_release_scans/' . self::RELEASE_SCAN_ID . '.png');

        $this->insert('game_fact', ['game_fact_id' => self::GAME_FACT_ID], [
            'game_id'   => self::GAME_ID,
            'game_fact' => 'The Bitmap Brothers wrote this one.',
        ]);

        $this->insert('game_submitinfo', ['game_submitinfo_id' => self::GAME_SUBMISSION_ID], [
            'game_id'     => self::GAME_ID,
            'user_id'     => self::USER_STANDARD_ID,
            'timestamp'   => (string) now()->timestamp,
            'submit_text' => 'Playwright test submission.',
            'game_done'   => 'N',
        ]);

        $this->insert('game_series', ['id' => self::GAME_SERIES_ID], ['name' => self::SERIES_NAME]);
        $this->insert('pub_dev', ['pub_dev_id' => self::COMPANY_ID], ['pub_dev_name' => self::COMPANY_NAME]);
    }

    private function seedContent(): void
    {
        $this->insert('article_main', ['article_id' => self::ARTICLE_ID], ['user_id' => self::USER_ADMIN_ID]);
        $this->insert('article_text', ['article_id' => self::ARTICLE_ID], [
            'article_title' => self::ARTICLE_TITLE,
            'article_intro' => 'Playwright test article intro.',
            'article_text'  => 'Playwright test article content.',
            'article_date'  => now()->timestamp,
        ]);

        $this->insert('review_main', ['review_id' => self::REVIEW_ID], [
            'user_id'     => self::USER_ADMIN_ID,
            'review_text' => 'Great game!',
            'review_date' => now()->timestamp,
        ]);
        $this->insert('review_game', [
            'review_id' => self::REVIEW_ID,
            'game_id'   => self::GAME_ID,
        ], []);

        // The "Who is it?" card on the home page only picks an interview whose
        // individual has a picture, and the card then reads the interview's
        // text, so seed the whole chain.
        $this->insert('individuals', ['ind_id' => self::INDIVIDUAL_ID], [
            'ind_name' => self::INTERVIEW_INDIVIDUAL,
        ]);
        $this->insert('individual_text', ['ind_id' => self::INDIVIDUAL_ID], ['ind_imgext' => 'png']);
        $this->seedImage('images/individual_screenshots/' . self::INDIVIDUAL_ID . '.png');

        $this->insert('interview_main', ['interview_id' => self::INTERVIEW_ID], [
            'user_id' => self::USER_ADMIN_ID,
            'ind_id'  => self::INDIVIDUAL_ID,
        ]);
        $this->insert('interview_text', ['interview_id' => self::INTERVIEW_ID], [
            'interview_intro' => 'Playwright test interview intro.',
            'interview_text'  => 'Playwright test interview content.',
            'interview_date'  => now()->timestamp,
        ]);

        // Interview 2 deliberately has neither an individual nor a text row.
        // The admin table joins those, and a row with no match is what used to
        // null out its primary key - keep a row of that shape so the
        // regression stays covered.
        $this->insert('interview_main', ['interview_id' => 2], ['user_id' => self::USER_ADMIN_ID]);

        $this->insert('news', ['news_id' => self::NEWS_ID], [
            'news_headline' => self::NEWS_HEADLINE,
            'news_text'     => 'Playwright test news post.',
            'user_id'       => self::USER_ADMIN_ID,
            'news_date'     => now()->timestamp,
        ]);

        // The pivot is not optional: Comment::getTypeAttribute() throws
        // 'Unknown comment type' without one, and the admin comment form
        // builds a route name out of it.
        $this->insert('comments', ['comments_id' => self::COMMENT_ID], [
            'comment'   => 'Playwright test comment.',
            'timestamp' => (string) now()->timestamp,
            'user_id'   => self::USER_STANDARD_ID,
        ]);
        $this->insert('game_user_comments', [
            'game_id'    => self::GAME_ID,
            'comment_id' => self::COMMENT_ID,
        ], []);
    }

    private function seedMagazines(): void
    {
        $this->insert('magazines', ['id' => self::MAGAZINE_ID], ['name' => self::MAGAZINE_NAME]);
        $this->insert('magazine_issues', ['id' => self::MAGAZINE_ISSUE_ID], [
            'magazine_id' => self::MAGAZINE_ID,
            'issue'       => 1,
            'published'   => '1990-01-01',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    private function seedMenus(): void
    {
        // The menu set index inner-joins sets to menus to disks, so a set on
        // its own would not appear at all.
        //
        // These tables are Eloquent-managed, so their timestamps are never
        // null in practice. Set them explicitly: a raw insert would leave them
        // null, and the "Latest menus" card formats updated_at unguarded.
        $now = now();

        $this->insert('menu_sets', ['id' => self::MENU_SET_ID], [
            'name'       => self::MENU_SET_NAME,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->insert('menus', ['id' => self::MENU_ID], [
            'menu_set_id' => self::MENU_SET_ID,
            'number'      => 1,
            'date'        => '1990-01-01',
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);
        $this->insert('menu_disks', ['id' => self::MENU_DISK_ID], [
            'menu_id'                => self::MENU_ID,
            'menu_disk_condition_id' => self::MENU_CONDITION_INTACT_ID,
            'created_at'             => $now,
            'updated_at'             => $now,
        ]);

        $this->insert('menu_software', ['id' => self::MENU_SOFTWARE_ID], [
            'name'                          => self::MENU_SOFTWARE_NAME,
            'menu_software_content_type_id' => self::MENU_CONTENT_TYPE_GAME_ID,
            'created_at'                    => $now,
            'updated_at'                    => $now,
        ]);
        $this->insert('menu_disk_contents', ['id' => self::MENU_DISK_CONTENT_ID], [
            'menu_disk_id' => self::MENU_DISK_ID,
            'order'        => 1,
            'game_id'      => self::GAME_ID,
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);

        $this->insert('crew', ['crew_id' => self::CREW_ID], ['crew_name' => self::CREW_NAME]);
    }

    private function seedLinks(): void
    {
        $this->insert('website_category', ['website_category_id' => self::WEBSITE_CATEGORY_ID], [
            'website_category_name' => self::WEBSITE_CATEGORY_NAME,
        ]);
        $this->insert('website', ['website_id' => self::WEBSITE_ID], [
            'website_name'   => self::WEBSITE_NAME,
            'website_url'    => 'https://example.com/',
            'website_date'   => now()->timestamp,
            'user_id'        => self::USER_ADMIN_ID,
            'website_imgext' => 'png',
            'inactive'       => 0,
            'description'    => 'Playwright test link.',
        ]);
        $this->insert('website_category_cross', [
            'website_id'          => self::WEBSITE_ID,
            'website_category_id' => self::WEBSITE_CATEGORY_ID,
        ], []);
        $this->seedImage('images/website_images/' . self::WEBSITE_ID . '.png');
    }

    private function seedOthers(): void
    {
        $this->insert('screenshot_main', ['screenshot_id' => self::SPOTLIGHT_SCREENSHOT_ID], ['imgext' => 'png']);
        $this->insert('spotlight', ['spotlight_id' => 1], [
            'screenshot_id' => self::SPOTLIGHT_SCREENSHOT_ID,
            'spotlight'     => 'Playwright test spotlight.',
            'link'          => 'https://example.com/',
        ]);
        $this->seedImage('images/spotlight_screens/' . self::SPOTLIGHT_SCREENSHOT_ID . '.png');
    }

    /**
     * Write one fixture row, keyed on its primary key.
     *
     * updateOrInsert rather than "insert if the table is empty": the latter
     * means anyone who ran an older version of this seeder and then pulls a
     * newer one gets half a fixture, because the guard for a table that
     * already has a row never fires again.
     */
    private function insert(string $table, array $key, array $values): void
    {
        DB::table($table)->updateOrInsert($key, $values);
    }

    /**
     * Put a real image where a resource route expects to find one.
     *
     * Those controllers read the file off disk, so a database row on its own
     * makes them 404 or 500.
     *
     * Guarded on existence because the database freshness check in run() does
     * not protect the filesystem: on a developer machine
     * images/game_screenshots/1.png may well be a real screenshot.
     *
     * Nothing cleans these up, on purpose. storage/app/public is gitignored in
     * full and CI runners are ephemeral, so the only thing a teardown could
     * achieve is deleting files a developer wanted to keep.
     */
    private function seedImage(string $path): void
    {
        if (Storage::disk('public')->exists($path)) {
            return;
        }

        Storage::disk('public')->put($path, base64_decode(self::PIXEL_PNG));
    }
}
