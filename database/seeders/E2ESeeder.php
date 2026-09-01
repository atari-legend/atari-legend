<?php

namespace Database\Seeders;

use App\Helpers\UserHelper;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
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
 * all (screenshot_game, review_game, game_user_comments,
 * website_category_cross), the factories are random where the specs need fixed
 * names and slugs, and $fillable on the legacy models is thin enough that
 * Model::create() would silently drop columns we depend on.
 */
class E2ESeeder extends Seeder
{
    public const USER_ADMIN_ID = 101;
    public const USER_STANDARD_ID = 102;
    public const USER_UNVERIFIED_ID = 103;

    // The two accounts tests/e2e/public-write/ signs in as. They exist so that
    // project never has to write through USER_STANDARD_ID, which the read
    // specs assert on: it owns the seeded comment on the seeded game, and
    // admin-write/content.spec.js picks it in an autocomplete.
    //
    // Two rather than one because public-write/account.spec.js changes the
    // password, and an account whose password is in flux cannot be the one
    // every other spec in the project signs in with.
    public const USER_CONTRIBUTOR_ID = 104;
    public const USER_ACCOUNT_ID = 105;

    // The account tests/e2e/admin-write/users.spec.js edits, deactivates and
    // gives an avatar to. It exists because a user is the one row in this
    // application no form can create - registration is behind an hCaptcha and
    // the admin has no create screen - so that spec cannot make its own parent
    // the way every other write spec does. A sixth account it may rewrite
    // freely is the next best thing: no read spec asserts on it, and it signs
    // in nowhere.
    public const USER_MODERATED_ID = 106;

    public const GAME_ID = 201;
    public const GAME_AKA_ID = 202;
    public const RELEASE_ID = 301;
    public const SCREENSHOT_ID = 401;
    public const SPOTLIGHT_ID = 4001;
    public const TRIVIA_ID = 4101;
    public const SPOTLIGHT_SCREENSHOT_ID = 402;
    public const INTERVIEW_SCREENSHOT_ID = 403;
    public const ARTICLE_SCREENSHOT_ID = 404;
    public const RELEASE_SCAN_ID = 501;
    public const GAME_FACT_ID = 601;
    public const GAME_SUBMISSION_ID = 701;
    public const GAME_SERIES_ID = 801;
    public const COMPANY_ID = 901;
    public const INDIVIDUAL_ID = 1001;
    public const CREW_ID = 1101;

    // A tune of our own, even though a migration ships the whole SNDH
    // catalogue: a spec asserting on one of those would be asserting on
    // somebody else's data, which changes with the next archive. The key of an
    // SNDH is the tune's path inside the archive, not a number, and the
    // archive it belongs to is a foreign key - hence one the migration wrote.
    public const SNDH_ID = 'Playwright/Test Tune.sndh';
    public const SNDH_TITLE = 'Playwright Test Tune';
    public const SNDH_COMPOSER = 'Playwright Test Composer';
    public const SNDH_ARCHIVE_ID = 'sndh45lf';

    public const PORT_ID = 1201;
    public const PORT_NAME = 'Playwright Test Port';
    public const PROGRESS_SYSTEM_ID = 1301;
    public const PROGRESS_SYSTEM_NAME = 'Playwright Test Progress';
    public const GENRE_ID = 1401;
    public const GENRE_NAME = 'Playwright Test Genre';
    public const PROGRAMMING_LANGUAGE_ID = 1501;
    public const PROGRAMMING_LANGUAGE_NAME = 'Playwright Test Assembly';
    public const ENGINE_ID = 1601;
    public const ENGINE_NAME = 'Playwright Test Engine';
    public const CONTROL_ID = 1701;
    public const CONTROL_NAME = 'Playwright Test Joystick';
    public const SOUND_HARDWARE_ID = 1801;
    public const SOUND_HARDWARE_NAME = 'Playwright Test YM2149';
    public const COPY_PROTECTION_ID = 1901;
    public const COPY_PROTECTION_NAME = 'Playwright Test Copy Protection';

    public const ARTICLE_ID = 2001;
    public const ARTICLE_TYPE_ID = 2002;
    public const REVIEW_ID = 2101;
    public const INTERVIEW_ID = 2201;
    public const NEWS_ID = 2301;
    public const COMMENT_ID = 2401;

    public const MAGAZINE_ID = 2501;
    public const MAGAZINE_ISSUE_ID = 2502;
    // One index row per shape magazines/card_issues.blade.php can render: a
    // game, a menu software, an individual, and one that links to nothing and
    // is only its title.
    //
    // The text row is last but sits on the earliest page, so that the order
    // these are stored in is not the order anything displays them in. The
    // public view always sorts, and the editor sorts only when asked - neither
    // could be told from a broken one if the two orders agreed.
    public const MAGAZINE_INDEX_GAME_ID = 2601;
    public const MAGAZINE_INDEX_SOFTWARE_ID = 2602;
    public const MAGAZINE_INDEX_INDIVIDUAL_ID = 2603;
    public const MAGAZINE_INDEX_TEXT_ID = 2604;

    public const MENU_SET_ID = 2701;
    public const MENU_ID = 2801;
    public const MENU_DISK_ID = 2901;
    public const MENU_DISK_CONTENT_ID = 3101;
    public const MENU_SOFTWARE_ID = 3001;

    public const WEBSITE_ID = 3201;
    public const WEBSITE_CATEGORY_ID = 3301;

    /** Seeded by a migration, not by us. */
    public const MENU_CONDITION_INTACT_ID = 4;
    public const MENU_CONTENT_TYPE_GAME_ID = 1;
    // Index types ship with the magazines migration. Names rather than ids:
    // theirs come from the order that migration inserts them in, which is not
    // something a fixture should depend on.
    public const MAGAZINE_INDEX_TYPE_TEXT = 'Column';
    public const MAGAZINE_INDEX_TYPE_GAME = 'Review';
    public const MAGAZINE_INDEX_TYPE_SOFTWARE = 'Tutorial';
    public const MAGAZINE_INDEX_TYPE_INDIVIDUAL = 'Interview';

    public const GAME_NAME = 'Xenon 2 Megablast';
    public const GAME_SLUG = 'xenon-2-megablast';
    // Shorter than the game name and matching the same term, so the two
    // autocompletes that merge games with their AKAs have something to rank.
    public const GAME_AKA_NAME = 'Xenon II';
    public const ARTICLE_TITLE = 'Playwright Test Article';
    public const ARTICLE_TYPE_NAME = 'Playwright Test Article Type';
    public const INTERVIEW_INDIVIDUAL = 'Playwright Test Individual';
    public const MAGAZINE_NAME = 'Playwright Test Magazine';
    public const MAGAZINE_INDEX_TEXT_TITLE = 'Playwright Test Editorial';
    public const MAGAZINE_INDEX_GAME_SCORE = '92%';
    public const MAGAZINE_INDEX_SOFTWARE_TITLE = 'Playwright Test Cover Disk';
    public const MAGAZINE_INDEX_INDIVIDUAL_TITLE = 'Playwright Test Coder Profile';
    public const MENU_SET_NAME = 'Playwright Test Menu Set';
    public const MENU_SOFTWARE_NAME = 'Playwright Test Software';
    public const NEWS_HEADLINE = 'Welcome to Atari Legend';

    // /news pages at six items, so a second page needs a seventh row. These are
    // the six extras, headlined by their number, and only the last of them can
    // be asserted on page two.
    public const NEWS_FILLER_COUNT = 6;
    public const NEWS_FILLER_HEADLINE = 'Playwright Test Filler News';

    // The caption on the seeded interview screenshot, and the chapter the
    // interview's [hotspotUrl] links to. Both are BBCode features with nothing
    // else in the fixture that renders them.
    public const INTERVIEW_SCREENSHOT_CAPTION = 'Playwright Test Interview Screenshot';
    public const INTERVIEW_CHAPTER = 'Playwright Test Chapter';
    public const ARTICLE_SCREENSHOT_CAPTION = 'Playwright Test Article Screenshot';
    public const TRIVIA_TEXT = 'Playwright test trivia.';
    public const MAGAZINE_ARCHIVE_URL = 'https://archive.org/details/playwright-test-issue';
    public const COMPANY_NAME = 'Playwright Test Company';
    public const CREW_NAME = 'Playwright Test Crew';
    public const SERIES_NAME = 'Playwright Test Series';
    public const WEBSITE_NAME = 'Playwright Test Website';
    public const WEBSITE_CATEGORY_NAME = 'Playwright Test Category';

    /**
     * An 8x8 opaque RGBA PNG, base64-encoded.
     *
     * Deliberately a constant rather than something GD draws: the routes that
     * read these files are the ones most likely to be broken by a GD build
     * missing WebP or FreeType, and a fixture that needs GD to produce its own
     * input cannot tell you that.
     *
     * It has to survive imagecreatefromstring(), not just look like a PNG -
     * several routes re-encode it through Intervention. A header alone is not
     * enough: `file` and getimagesizefromstring() both read one happily while
     * GD rejects the image.
     */
    private const PIXEL_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAgAAAAICAYAAADED76LAAAAEklEQVR42mMwTpv5Hx9m'
        . 'GBkKAANgjEFZddhTAAAAAElFTkSuQmCC';

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
        if (DB::table('games')->count() > 1) {
            $this->command?->warn('E2ESeeder: existing dataset detected, skipping sample data.');

            return;
        }

        $this->seedGames();
        $this->seedReferenceData();
        $this->seedContent();
        $this->seedGameLinks();
        $this->seedMenus();
        // After the menus: a magazine index row can link to a menu software,
        // and the foreign key wants it to exist first.
        $this->seedMagazines();
        $this->seedLinks();
        $this->seedOthers();
    }

    private function seedUsers(): void
    {
        $salt = UserHelper::salt();
        $sha512Password = UserHelper::hashPassword('password', $salt);

        // user_id is auto-increment and not fillable, so the ids come from the
        // counter, not from the constants above. Start the counter at
        // USER_ADMIN_ID so that insertion order lands on them: without this the
        // users are 1..6 while everything pointing at them uses 101..106, and
        // the first such insert fails on the foreign key.
        if (DB::table('users')->count() === 0 && DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE users AUTO_INCREMENT = ' . self::USER_ADMIN_ID);
        }

        // user_id is the primary key and is not fillable, so these rely on
        // insertion order on a fresh database - admin first, then the counter
        // set above walks USER_ADMIN_ID upwards. Keep the order.
        //
        // The third user has never confirmed its address. Every route in the
        // app sits behind the `verified` middleware, so it is the only way to
        // reach the verification notice - and the only account that proves the
        // middleware still turns people away.
        //
        // The fourth and fifth belong to tests/e2e/public-write/ and nothing
        // else. 'accounttester' is the one whose own profile and password get
        // rewritten, which is why it is not 'contributor': every other spec in
        // that project signs in as 'contributor' while it does.
        //
        // The sixth is the one tests/e2e/admin-write/users.spec.js edits - see
        // USER_MODERATED_ID above.
        foreach ([
            ['admin', 'admin@atarilegend.com', User::PERMISSION_ADMIN, true],
            ['testuser', 'test@example.com', User::PERMISSION_USER, true],
            ['unverified', 'unverified@example.com', User::PERMISSION_USER, false],
            ['contributor', 'contributor@example.com', User::PERMISSION_USER, true],
            ['accounttester', 'accounttester@example.com', User::PERMISSION_USER, true],
            ['moderated', 'moderated@example.com', User::PERMISSION_USER, true],
        ] as [$userid, $email, $permission, $verified]) {
            User::updateOrCreate(
                ['userid' => $userid],
                [
                    'email'             => $email,
                    'email_verified_at' => $verified ? now() : null,
                    'salt'              => $salt,
                    'sha512_password'   => $sha512Password,
                    'permission'        => $permission,
                    'inactive'          => User::ACTIVE,
                    'join_date'         => (string) now()->timestamp,
                    'last_visit'        => (string) now()->timestamp,
                    'remember_token'    => Str::random(10),
                    'karma'             => 0,
                ]
            );
        }
    }

    private function seedGames(): void
    {
        $this->insert('games', ['id' => self::GAME_ID], [
            'name' => self::GAME_NAME,
            'slug'      => self::GAME_SLUG,
        ]);

        // A release needs a date: the link on the game page is labelled with
        // the release year, and an empty label is not clickable.
        $this->insert('game_releases', ['id' => self::RELEASE_ID], [
            'game_id' => self::GAME_ID,
            'date'    => '1989-01-01',
            'license' => 'Commercial',
        ]);

        $this->insert('screenshots', ['id' => self::SCREENSHOT_ID], ['imgext' => 'png']);
        $this->insert('screenshot_game', [
            'game_id'       => self::GAME_ID,
            'screenshot_id' => self::SCREENSHOT_ID,
        ], []);
        $this->seedImage('images/game_screenshots/' . self::SCREENSHOT_ID . '.png');

        $this->insert('game_release_scans', ['id' => self::RELEASE_SCAN_ID], [
            'game_release_id' => self::RELEASE_ID,
            'type'            => 'Box front',
            'imgext'          => 'png',
        ]);
        $this->seedImage('images/game_release_scans/' . self::RELEASE_SCAN_ID . '.png');

        $this->insert('game_facts', ['id' => self::GAME_FACT_ID], [
            'game_id'   => self::GAME_ID,
            'fact' => 'The Bitmap Brothers wrote this one.',
        ]);

        $this->insert('game_submit_infos', ['id' => self::GAME_SUBMISSION_ID], [
            'game_id'     => self::GAME_ID,
            'user_id'     => self::USER_STANDARD_ID,
            'timestamp'   => (string) now()->timestamp,
            'text' => 'Playwright test submission.',
            'game_done'   => 'N',
        ]);

        $this->insert('game_akas', ['id' => self::GAME_AKA_ID], [
            'game_id'  => self::GAME_ID,
            'name' => self::GAME_AKA_NAME,
        ]);

        $this->insert('game_series', ['id' => self::GAME_SERIES_ID], ['name' => self::SERIES_NAME]);
        $this->insert('pub_devs', ['id' => self::COMPANY_ID], ['name' => self::COMPANY_NAME]);

        $this->insert('sndhs', ['id' => self::SNDH_ID], [
            'sndh_archive_id' => self::SNDH_ARCHIVE_ID,
            'title'           => self::SNDH_TITLE,
            'composer'        => self::SNDH_COMPOSER,
            'subtunes'        => 1,
            'default_subtune' => 1,
        ]);
    }

    /**
     * Hang the reference rows off the game.
     *
     * Its own step, after seedReferenceData() and seedContent(): the company,
     * genre, engine and individual all have to exist first, and pub_dev_id on
     * a release is a foreign key.
     *
     * These links are what make the game search assertable through its
     * autocompletes - each field searches on one of them - and they are also
     * what makes the individuals autocomplete decorate a name with the games
     * that person worked on. The roles are left null: the role tables are
     * RESTRICT foreign keys with nothing seeded in them, and nothing here
     * asserts on a role.
     */
    private function seedGameLinks(): void
    {
        DB::table('game_releases')
            ->where('id', self::RELEASE_ID)
            ->update(['pub_dev_id' => self::COMPANY_ID]);

        $this->insert('game_developer', [
            'game_id'    => self::GAME_ID,
            'pub_dev_id' => self::COMPANY_ID,
        ], []);

        $this->insert('game_genre_cross', [
            'game_id'       => self::GAME_ID,
            'game_genre_id' => self::GENRE_ID,
        ], []);

        $this->insert('game_engine', [
            'game_id'   => self::GAME_ID,
            'engine_id' => self::ENGINE_ID,
        ], []);

        $this->insert('game_individual', [
            'game_id'       => self::GAME_ID,
            'individual_id' => self::INDIVIDUAL_ID,
        ], []);
    }

    private function seedContent(): void
    {
        // The type is the badge on /articles. article_type ships empty and the
        // article's column was null, so the badge rendered as nothing at all -
        // indistinguishable from a badge that had stopped working.
        $this->insert('article_types', ['id' => self::ARTICLE_TYPE_ID], [
            'name' => self::ARTICLE_TYPE_NAME,
        ]);
        $this->insert('articles', ['id' => self::ARTICLE_ID], [
            'user_id'         => self::USER_ADMIN_ID,
            'article_type_id' => self::ARTICLE_TYPE_ID,
            'article_title'   => self::ARTICLE_TITLE,
            'article_intro'   => 'Playwright test article intro.',
            'article_text'    => 'Playwright test article content.',
            'article_date'    => now()->timestamp,
        ]);

        // Same chain as the interview screenshot below, one table along.
        $this->insert('screenshots', ['id' => self::ARTICLE_SCREENSHOT_ID], ['imgext' => 'png']);
        $this->insert('screenshot_article', ['id' => 1], [
            'article_id'    => self::ARTICLE_ID,
            'screenshot_id' => self::ARTICLE_SCREENSHOT_ID,
        ]);
        $this->insert('screenshot_article_comments', ['id' => 1], [
            'screenshot_article_id' => 1,
            'text'                  => self::ARTICLE_SCREENSHOT_CAPTION,
        ]);
        $this->seedImage('images/article_screenshots/' . self::ARTICLE_SCREENSHOT_ID . '.png');

        $this->insert('reviews', ['id' => self::REVIEW_ID], [
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
        $this->insert('individuals', ['id' => self::INDIVIDUAL_ID], [
            'name'   => self::INTERVIEW_INDIVIDUAL,
            'imgext' => 'png',
        ]);
        $this->seedImage('images/individual_screenshots/' . self::INDIVIDUAL_ID . '.png');

        // The chapters and the text carry the two halves of the interview
        // BBCode: [hotspotUrl=#1] in the chapter list is the link, [hotspot=1]
        // in the text is what it jumps to. Nothing else in the fixture renders
        // either, so without this pair Helper::bbCode() could stop emitting the
        // anchor entirely and every interview test would still pass.
        $this->insert('interviews', ['id' => self::INTERVIEW_ID], [
            'user_id'            => self::USER_ADMIN_ID,
            'individual_id'      => self::INDIVIDUAL_ID,
            'interview_intro'    => 'Playwright test interview intro.',
            'interview_text'     => '[hotspot=1]' . self::INTERVIEW_CHAPTER . '[/hotspot] '
                . 'Playwright test interview content.',
            'interview_chapters' => '[hotspotUrl=#1]' . self::INTERVIEW_CHAPTER . '[/hotspotUrl]',
            'interview_date'     => now()->timestamp,
        ]);

        // A screenshot on the interview, and the caption row that goes with it.
        // interviews/card_interview.blade.php reads
        // $screenshot->pivot->comment->text without guarding it, so a
        // screenshot seeded without its comment would 500 the public page
        // rather than render an empty caption.
        $this->insert('screenshots', ['id' => self::INTERVIEW_SCREENSHOT_ID], ['imgext' => 'png']);
        $this->insert('screenshot_interview', ['id' => 1], [
            'interview_id'  => self::INTERVIEW_ID,
            'screenshot_id' => self::INTERVIEW_SCREENSHOT_ID,
        ]);
        $this->insert('screenshot_interview_comments', ['id' => 1], [
            'screenshot_interview_id' => 1,
            'text'                    => self::INTERVIEW_SCREENSHOT_CAPTION,
        ]);
        $this->seedImage('images/interview_screenshots/' . self::INTERVIEW_SCREENSHOT_ID . '.png');

        $this->insert('news', ['id' => self::NEWS_ID], [
            'news_headline' => self::NEWS_HEADLINE,
            'news_text'     => 'Playwright test news post.',
            'user_id'       => self::USER_ADMIN_ID,
            'news_date'     => now()->timestamp,
        ]);

        // Enough news to paginate. /news shows six at a time and orders by
        // date, so these are dated backwards from the headline above - which
        // keeps NEWS_HEADLINE on page one and puts the oldest filler alone on
        // page two.
        for ($number = 1; $number <= self::NEWS_FILLER_COUNT; $number++) {
            $this->insert('news', ['id' => self::NEWS_ID + $number], [
                'news_headline' => self::NEWS_FILLER_HEADLINE . ' ' . $number,
                'news_text'     => 'Playwright test filler news post.',
                'user_id'       => self::USER_ADMIN_ID,
                'news_date'     => now()->subDays($number)->timestamp,
            ]);
        }

        // The pivot is not optional: Comment::getTypeAttribute() throws
        // 'Unknown comment type' without one, and the admin comment form
        // builds a route name out of it.
        $this->insert('comments', ['id' => self::COMMENT_ID], [
            'text'      => 'Playwright test comment.',
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
        // The cover and the archive.org URL are both on the issue: imgext is
        // what makes MagazineIssue::getCoverAttribute() build a path instead of
        // falling back to no-cover.svg, and getReadUrlAttribute() rewrites
        // /details/ to /stream/ - a rewrite no test could see while the column
        // was null.
        $this->insert('magazine_issues', ['id' => self::MAGAZINE_ISSUE_ID], [
            'magazine_id'    => self::MAGAZINE_ID,
            'issue'          => 1,
            'imgext'         => 'png',
            'archiveorg_url' => self::MAGAZINE_ARCHIVE_URL,
            'published'      => '1990-01-01',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
        $this->seedImage('images/magazine_scans/' . self::MAGAZINE_ISSUE_ID . '.png');

        // An index covering all four row shapes, so that the public view and
        // the Livewire editor both have every branch on screen. A row links to
        // a game, a menu software, an individual or to nothing at all; the type
        // is an orthogonal label, and the title is optional for the rows that
        // link somewhere - card_issues.blade.php falls back to the linked
        // record's name.
        foreach ([
            [self::MAGAZINE_INDEX_GAME_ID, 12, self::MAGAZINE_INDEX_TYPE_GAME, [
                'game_id' => self::GAME_ID,
                'score'   => self::MAGAZINE_INDEX_GAME_SCORE,
            ]],
            [self::MAGAZINE_INDEX_SOFTWARE_ID, 30, self::MAGAZINE_INDEX_TYPE_SOFTWARE, [
                'menu_software_id' => self::MENU_SOFTWARE_ID,
                'title'            => self::MAGAZINE_INDEX_SOFTWARE_TITLE,
            ]],
            [self::MAGAZINE_INDEX_INDIVIDUAL_ID, 44, self::MAGAZINE_INDEX_TYPE_INDIVIDUAL, [
                'individual_id' => self::INDIVIDUAL_ID,
                'title'         => self::MAGAZINE_INDEX_INDIVIDUAL_TITLE,
            ]],
            [self::MAGAZINE_INDEX_TEXT_ID, 3, self::MAGAZINE_INDEX_TYPE_TEXT, [
                'title' => self::MAGAZINE_INDEX_TEXT_TITLE,
            ]],
        ] as [$id, $page, $type, $values]) {
            $this->insert('magazine_indices', ['id' => $id], array_merge([
                'magazine_issue_id'      => self::MAGAZINE_ISSUE_ID,
                'magazine_index_type_id' => $this->magazineIndexTypeId($type),
                'page'                   => $page,
                'created_at'             => now(),
                'updated_at'             => now(),
            ], $values));
        }
    }

    /**
     * The id of one of the index types the magazines migration ships.
     */
    private function magazineIndexTypeId(string $name): int
    {
        $id = DB::table('magazine_index_types')->where('name', $name)->value('id');

        if (null === $id) {
            throw new RuntimeException("E2ESeeder: no magazine index type named '{$name}'.");
        }

        return $id;
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

        $this->insert('crews', ['id' => self::CREW_ID], ['name' => self::CREW_NAME]);
    }

    private function seedLinks(): void
    {
        $this->insert('website_categories', ['id' => self::WEBSITE_CATEGORY_ID], [
            'name' => self::WEBSITE_CATEGORY_NAME,
        ]);
        $this->insert('websites', ['id' => self::WEBSITE_ID], [
            'name'   => self::WEBSITE_NAME,
            'url'    => 'https://example.com/',
            'date'   => now()->timestamp,
            'user_id'        => self::USER_ADMIN_ID,
            'imgext' => 'png',
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
        $this->insert('screenshots', ['id' => self::SPOTLIGHT_SCREENSHOT_ID], ['imgext' => 'png']);
        $this->insert('spotlights', ['id' => self::SPOTLIGHT_ID], [
            'screenshot_id' => self::SPOTLIGHT_SCREENSHOT_ID,
            'text'          => 'Playwright test spotlight.',
            'link'          => 'https://example.com/',
        ]);
        $this->seedImage('images/spotlight_screens/' . self::SPOTLIGHT_SCREENSHOT_ID . '.png');

        // The 'Did you know?' card picks a trivia at random and renders its
        // heading either way, so an empty table looks exactly like a working
        // card. One row is the difference.
        $this->insert('trivia', ['id' => self::TRIVIA_ID], ['text' => self::TRIVIA_TEXT]);
    }

    private function seedReferenceData(): void
    {
        $this->insert('ports', ['id' => self::PORT_ID], ['name' => self::PORT_NAME]);
        $this->insert('game_progress_systems', ['id' => self::PROGRESS_SYSTEM_ID], ['name' => self::PROGRESS_SYSTEM_NAME]);
        $this->insert('game_genres', ['id' => self::GENRE_ID], ['name' => self::GENRE_NAME]);
        $this->insert('programming_languages', ['id' => self::PROGRAMMING_LANGUAGE_ID], ['name' => self::PROGRAMMING_LANGUAGE_NAME]);
        $this->insert('engines', ['id' => self::ENGINE_ID], ['name' => self::ENGINE_NAME]);
        $this->insert('controls', ['id' => self::CONTROL_ID], ['name' => self::CONTROL_NAME]);
        $this->insert('sound_hardware', ['id' => self::SOUND_HARDWARE_ID], ['name' => self::SOUND_HARDWARE_NAME]);
        $this->insert('copy_protections', ['id' => self::COPY_PROTECTION_ID], ['name' => self::COPY_PROTECTION_NAME]);
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
