# Timestamp, date and time columns

*2026-09-06*

`2026-08-24-main-text-table-merge.md` ("What this plan does not do", Decision
5) deferred converting `article_date`, `interview_date` and `review_date`
from unix-timestamp integers to a native type, naming the reason: converting
reaches `orderByDesc`, `AdminStatisticsHelper`, every
`Carbon::createFromTimestamp()` call site and the Livewire sort closures, and
doing it would stop a rollback being a pure projection.
`2026-08-31-column-name-consistency.md` ("The date and enum columns") renamed
those three columns and `news_date` to `date`, keeping the type unchanged, and
left the `users` authentication columns — `join_date` and `last_visit` among
them — alone. This plan converts those four columns and the ten others in the
same state, and gives each the name its role has.

End state, clause by clause:

- Every column that holds a moment in time is a native `DATE`/`DATETIME`/
  `TIMESTAMP`, except `database_changes.execute_timestamp` (kept `int(11)` — a
  historical record with no model, see "Out of scope"). Checked by the first
  two censuses in "Out of scope": the moment-column sweep, which returns
  `execute_timestamp` and nothing else, and the type census, which returns all
  fifteen converted columns as `datetime`.
- Every column that records when its row was created is called `created_at`;
  the four editorial content dates are `published_at`; `users.last_visit` is
  `last_visit_at`. Checked by the old-name census in "Out of scope".
- `comments` carries an `updated_at` beside its `created_at`, and no writer
  overwrites the posting time on an edit. Checked by
  `ContentPagesTest::test_a_visitor_can_edit_their_own_comment`, extended in
  Unit 2 to assert both columns.
- No code converts a stored moment by hand:
  `grep -rnE "(^|[^a-z_])time\(\)" app resources database --include=*.php --include=*.blade.php | grep -v "strtotime\|microtime\|freshTimestamp"`
  and `grep -rn "createFromTimestamp" app resources` both return nothing. On
  2026-09-09 they return 15 and 10 lines, every one of them in a file this
  plan edits.
- No legacy cast survives: `grep -rn "datetime:timestamp" app/Models` returns
  nothing once Units 1-3 convert the last `custom_datetime` cast. That proves
  no legacy cast survives, not that every new cast was added; the added casts
  and the Eloquent timestamp handling are covered by each unit's own
  `./vendor/bin/sail artisan test` run and the e2e seed and specs it names,
  since every reader and writer those exercise assumes a `Carbon` instance.

**The delivery unit is one commit per unit, based on `development`, not one
pull request.** Each of the three schema units carries exactly one migration
file, so reversing one is `migrate:rollback --step=1` and reverting the commit
removes that one migration file.

| Unit | Tables | Migration | Commit |
|---|---|---|---|
| 1 | `articles`, `news`, `interviews`, `reviews` | `2026_09_06_110000_content_dates_published_at.php` | own |
| 2 | `changelogs`, `comments`, `game_submissions`, `andreas`, `links`, `link_submissions`, `news_submissions`, `dumps` | `2026_09_06_110100_activity_timestamps_created_at.php` | own |
| 3 | `users` | `2026_09_06_110200_user_timestamps_created_at.php` | own |
| 4 | none (code only) | none | own |

Units ship in order 1, 2, 3, 4. `Admin/News/NewsSubmissionsController.php:29`
assigns a news date from a `NewsSubmission`, and each of the first two units
rewrites that one line: Unit 1 drops the `->timestamp` call, which is only
safe once `News` carries the plain `datetime` cast that unit gives it —
passing a raw `Carbon` instance to the old `'datetime:timestamp'` cast stores
the object's `__toString()` into an `int(11)` column, which this connection's
`STRICT_TRANS_TABLES` mode (`'strict' => true` in `config/database.php:58,78`)
rejects with a truncated-value error rather than silently storing the leading
digits; Unit 2 then renames the attribute it reads. Unit 4 removes a branch of
`AdminStatisticsHelper` that Units 1-3 empty out.

## Unit 1 — the content publication dates

### The columns

`articles.date`, `news.date`, `interviews.date`, `reviews.date`: `int(11)`,
NOT NULL (`news.date` also `DEFAULT 0`), holding a unix timestamp. Measured
2026-09-09 against the dev MariaDB 10.11 database, `SELECT COUNT(*),
SUM(date % 86400 != 0), SUM(date = 0) FROM <table>`:

| Table | Rows | Non-midnight | `date = 0` |
|---|---|---|---|
| `articles` | 5 | 0 | 0 |
| `news` | 435 | 353 | 0 |
| `interviews` | 80 | 0 | 0 |
| `reviews` | 127 | 2 | 0 |

All four are edited through `<input type="date">`
(`resources/views/admin/{articles,news,interviews,reviews}/.../card_edit.blade.php`),
validated `required|date`, and rendered `->format('F j, Y')`
(`resources/views/{articles/card_article,news/card_list,interviews/card_interview,reviews/card_review}.blade.php`)
— nothing reads a time of day. All four carry the same
`'date' => 'datetime:timestamp'` cast and are written through
`Carbon::parse($request->date)->timestamp` in their admin controllers.

`datetime:timestamp` is Laravel's `custom_datetime` cast (any `datetime:`
argument routes there — `HasAttributes::isCustomDateTimeCast()`), not the
plain `datetime` cast `isDateAttribute()` checks for. `custom_datetime` reads
through `asDateTime()` same as `datetime`, so `$model->date` already returns
a correct `Carbon` instance today. It does not write through
`fromDateTime()`, so assigning `$model->date = $value` stores `$value` as
given with no conversion — the four controllers work only because each
pre-converts to a unix-timestamp int by hand before assigning.

**The target type is `DATETIME`, not `DATE`.** `DATE` matches what every
current reader does with these columns, but truncates the 353 non-midnight
`news` rows and 2 non-midnight `reviews` rows, and that truncation cannot be
undone by `down()`. `DATETIME` stores every existing value unchanged and keeps
the migration a byte-for-byte round trip.

**The target name is `published_at`.** The column is the date the item was
published: an editor sets it on the form, can backdate it, and the public
pages label it as the item's date, so it is not `created_at`. After this unit
it also carries a time, which `date` no longer describes.

### The form

**The admin form edits the time as well as the date.**
`<input type="date">` becomes `<input type="datetime-local">` in the four
`card_edit.blade.php` files: `resources/views/admin/articles/articles/:52-56`,
`news/news/:48-51`, `interviews/interviews/:62-66`,
`reviews/reviews/:62-66`. In each, the label becomes `Published`, the `name`,
`id` and `@error()` key become `published_at`, and the value becomes
`old('published_at', isset($x) ? $x->published_at?->format('Y-m-d\TH:i')
: now()->format('Y-m-d\TH:i'))` — `datetime-local` reads and submits
`Y-m-d\TH:i`, not the space-separated form.

- The validation rule stays `required|date` under the new key
  (`ArticleController.php:229`, `NewsController.php:21`,
  `InterviewsController.php:223`, `ReviewsController.php:175`). Laravel's
  `date` rule is `strtotime()`-backed and accepts `2026-03-14T09:30`, and
  `asDateTime()` falls through to `Date::parse()` for it
  (`HasAttributes::asDateTime()`), so the value reaches the column as a
  `DATETIME` with no conversion in the controller.
- The control is minute-precision: it submits no seconds, so re-saving one of
  the 355 rows that carry a time of day zeroes that row's seconds. Nothing
  reads seconds.
- The time is what orders same-day rows: `orderByDesc('published_at')` drives
  `/news`, `/reviews`, `/interviews`, `/articles`, the home page and the feed.
  The public pages keep `F j, Y` and show no time.

### The migration

New `database/support/UnixTimestampColumnConverter.php`, used by all three
schema units in this plan:

```php
class UnixTimestampColumnConverter
{
    public static function up(string $table, string $from, string $to, bool $nullable): void;
    public static function down(string $table, string $to, string $from, string $sourceType, ?int $length, bool $nullable, mixed $default = null): void;
}
```

Every column this plan converts also changes name, so `up()` needs no
intermediate column: it adds `$to` as a nullable `DATETIME`, backfills it from
`$from`, drops `$from`, then — if `$nullable` is `false` — alters `$to` to NOT
NULL. `down()` is the same four steps in reverse, rebuilding `$from` from
`$sourceType` and `$length` and restoring `$default`.

Both backfills are driver-branched on `DB::connection()->getDriverName()`, on
the same "not sqlite" distinction `TableRenamer` documents
(`TableRenamer.php:48-50`) and implements as an early return
(`TableRenamer.php:91`) — this connection's driver name is `mariadb`, not
`mysql`:

- `up()`: `FROM_UNIXTIME(NULLIF({$from}, ''))` on MariaDB,
  `datetime(nullif({$from}, ''), 'unixepoch')` on SQLite, both
  `WHERE {$from} IS NOT NULL`.
- `down()`: `UNIX_TIMESTAMP({$to})` on MariaDB, `strftime('%s', {$to})` on
  SQLite, both `WHERE {$to} IS NOT NULL`.

`NULLIF(column, '')` on MariaDB evaluates to `NULL` for a `0` value as well as
an empty string (`SELECT NULLIF(0, '')` is `NULL`, since `''` compares equal to
`0`), so it is a no-op on the `int` sources in this unit and Unit 2 only
because none of the nine converted `int` columns holds a `0` value (measured
2026-09-09; see this unit's table and Unit 2's) — it handles the `varchar`
sources in Units 2 and 3 as designed. A `0` reaching a NOT NULL target would
fail the NOT NULL alter, not corrupt a row. The SQLite branch runs even though
every migrated table is empty under `migrate:fresh`, so `up()`/`down()` stay
symmetric regardless of which driver a test runs against.

**`up()` drops the column default and does not replace it.** A `DATETIME`
column cannot carry `DEFAULT 0`, so `news.published_at` comes out NOT NULL
with no default here, and `links`, `link_submissions` and `news_submissions`
likewise in Unit 2. Under `STRICT_TRANS_TABLES` an insert that omits one of
those columns now fails instead of storing 0; every writer in the app sets the
column, or lets Eloquent set it. `down()` restores the default.

Both directions assume the MariaDB session timezone matches PHP's
`date_default_timezone_get()`, since `FROM_UNIXTIME()`/`UNIX_TIMESTAMP()` read
the session zone and every reader this plan touches reads PHP's. Both are UTC
on this stack (`@@session.time_zone = SYSTEM` with `NOW() = UTC_TIMESTAMP()`;
`APP_TIMEZONE` unset, so `config/app.php:68` supplies `UTC` — measured
2026-09-09).

`2026_09_06_110000_content_dates_published_at.php` calls
`UnixTimestampColumnConverter::up($table, 'date', 'published_at', nullable:
false)` for each of the four tables; `down()` passes
`sourceType: 'integer', length: null, nullable: false`, plus `default: 0` for
`news`.

### The code

- **Models** — `Article.php:19,23,56`, `News.php:18,22,50`,
  `Interview.php:19,23,64`, `Review.php:20,26,70`: `'date'` becomes
  `'published_at'` in `$fillable`, the cast becomes
  `'published_at' => 'datetime'`, and `toFeedItem()`'s
  `'updated' => $this->date` becomes `$this->published_at`.
- **Admin controllers** — `Admin/Articles/ArticleController.php:70,99,229`,
  `Admin/News/NewsController.php:21,65,91`,
  `Admin/Interviews/InterviewsController.php:66,97,223`,
  `Admin/Reviews/ReviewsController.php:64,103,175`: the validation key becomes
  `published_at`, and `'date' => Carbon::parse($request->date)->timestamp`
  becomes `'published_at' => $request->published_at`.
- **Public controllers and helpers** — the column name in
  `ArticleController.php:17,36,43`, `InterviewController.php:17,26,33`,
  `NewsController.php:16`, `ReviewController.php:37,60,181`,
  `HomeController.php:17`, `SitemapController.php:23`,
  `FeedHelper.php:14,17,21,25`, `View/Components/Cards/Reviews.php:30`. The
  three JSON-LD `datePublished` reads (`ArticleController.php:43`,
  `InterviewController.php:33`, `ReviewController.php:60`) keep
  `->format('Y-m-d')`.
- **`ReviewController.php:99`** (public review-submission flow):
  `$review->date = time()` becomes `$review->published_at = now()`.
- **`Admin/News/NewsSubmissionsController.php:29`**:
  `'date' => $submission->date->timestamp` becomes
  `'published_at' => $submission->date` (see the header for the ordering
  reason; Unit 2 renames the attribute being read).
- **Livewire tables** — `ArticlesTable.php:20,39-44`,
  `NewsTable.php:19,33`, `InterviewsTable.php:20,41-46`,
  `ReviewsTable.php:23,43-50`: the `setDefaultSort()` key, the column field
  and, where the Date column has a sort closure, the `orderBy()` inside it.
  `NewsTable`'s Date column has none — `NewsTable.php:33` is a plain
  `->sortable()`, so there it is the key and the field only.
  `ArticlesTable.php:44` keeps its `orderBy('published_at')` unqualified
  because that builder joins nothing (`:59` is
  `Article::query()->select('articles.*')`); `InterviewsTable.php:46` and
  `ReviewsTable.php:50` stay qualified, as they are today.
- **Views** — `articles/card_article.blade.php:15`,
  `articles/card_list.blade.php:24`,
  `articles/card_latest_articles.blade.php:12`,
  `news/card_list.blade.php:18`, `home/card_news.blade.php:9`,
  `interviews/card_interview.blade.php:17`,
  `interviews/card_list.blade.php:28`,
  `interviews/card_latest_interviews.blade.php:12`,
  `interviews/partial_card.blade.php:17`,
  `reviews/card_review.blade.php:15`, `reviews/partial_list.blade.php:8`,
  `reviews/card_author.blade.php:21`,
  `components/cards/reviews.blade.php:9`,
  `components/cards/screenstar.blade.php:27` (a `Review`), and
  `admin/users/users/card_activity.blade.php:20`
  (`$user->news->sortByDesc('date')`).
- **Factories** — `ArticleFactory.php:24`, `NewsFactory.php:27`,
  `InterviewFactory.php:23`, `ReviewFactory.php:25`:
  `'date' => now()->timestamp` becomes `'published_at' => now()`. Delete the
  "unix timestamp in an integer column" comments at `NewsFactory.php:17-18`
  and `ReviewFactory.php:17-18`.
- **`database/seeders/E2ESeeder.php:397,415,443,466,478`** (`articles`,
  `reviews`, `interviews`, `news`, and the news filler loop): the key becomes
  `published_at` and `now()->timestamp` becomes `now()`. These rows are written
  through `DB::table()->updateOrInsert()` (`E2ESeeder.php:667`), which applies
  no cast, so an unconverted int reaches MariaDB raw and the seed aborts:

  ```
  SQLSTATE[22007]: Invalid datetime format: 1292 Incorrect datetime value:
  '1789000000' for column `atarilegend`.`news`.`published_at` at row 1
  ```

  `.github/workflows/build-and-deploy.yml:140-141` runs `migrate:fresh` then
  `db:seed --class=E2ESeeder`, so without this change every e2e spec fails
  from this unit on.
- **`AdminStatisticsHelper::countByYear()` (591-616) buckets with `substr`,
  not `Carbon`.** Its `$epoch = false` branch is
  `(int) Carbon::parse($date)->year` today; it becomes
  `(int) substr($date, 0, 4)`. Unit 2 sends all 61,799 changelog rows down
  that branch, and the comment at line 600-601 rules out building a Carbon
  instance per row ("costs ~700ms"). Every value that reaches it is `'Y-m-d'`
  or `'Y-m-d H:i:s'`: the columns Units 1-3 convert, plus
  `game_releases.date` and `menus.date`, which already arrive as date strings.
- **`AdminStatisticsHelper::contentByYear()` (418-445)**: the four
  `pluck('date')` calls at `:421-424` become `pluck('published_at')` and now
  return `'Y-m-d H:i:s'` strings, not epoch ints; call
  `countByYear($timestamps, false)` at this one call site (`:430`).
  `countByYear` keeps its `$epoch` parameter until Unit 4.
- **Tests** — replace `Carbon::parse(...)->timestamp` / `strtotime(...)`
  fixture values with a `Carbon` instance or datetime string, rename the key,
  and rewrite any assertion of a raw int, in:
  `Helpers/FeedHelperTest.php:39,47,53,60,106`,
  `Public/ContentPagesTest.php:36,43,164,351`,
  `Public/ReviewPagesTest.php:32`,
  `Admin/Articles/ArticleControllerTest.php:29,76-77,93`,
  `Admin/Interviews/InterviewsControllerTest.php:28,72-73,111`,
  `Admin/News/NewsControllerTest.php:25,64,111`,
  `Admin/Reviews/ReviewsControllerTest.php:29,88,125`,
  `GameHelperDescriptionTest.php:57-59`,
  `Admin/Tables/AdminTablesTest.php:422-433`. The three `getRawOriginal()`
  assertions now compare against `'2026-03-14 09:30:00'` rather than an epoch
  int, and the posted fixtures become `'2026-03-14T09:30'`, the form's own
  format.

### Acceptance

- `SELECT COUNT(*) FROM {articles,news,interviews,reviews} WHERE UNIX_TIMESTAMP(published_at) != <pre-migration int>`, joined on primary key, returns 0 for every row, checked before dropping the pre-migration dump.
- `php artisan migrate:rollback --step=1` then `php artisan migrate` restores the original `int(11)` values exactly, including the 353 `news` and 2 `reviews` non-midnight rows.
- `./vendor/bin/sail artisan test` passes.
- `php artisan db:seed --class=E2ESeeder` completes, and `tests/e2e/public/{articles,reviews,interviews,news}.spec.js`, `tests/e2e/admin/content.spec.js` and `tests/e2e/admin-write/content.spec.js` pass.
- Creating a news item, an article, an interview and a review in the admin UI with a time of day other than midnight stores that time, redisplays it in the form, and renders the date as `F j, Y` on the public page; two news items created on the same day list newest-first by their time.

## Unit 2 — the activity and submission timestamps

### The columns

Row counts, NULLs, zeros, empty strings and non-numeric values measured
2026-09-09 (`SELECT COUNT(*), SUM(<col> IS NULL), SUM(<col> = 0)` for the
`int` columns; `SUM(<col> = '')` and
`SUM(<col> IS NOT NULL AND <col> NOT REGEXP '^[0-9]+$')` for the `varchar`
ones):

| Table | Column | Type | Rows | NULL | `= 0` / `= ''` | New name |
|---|---|---|---|---|---|---|
| `changelogs` | `timestamp` | `int(11)` NOT NULL | 61,799 | 0 | 0 | `created_at` |
| `dumps` | `date` | `int(11)` NULL | 217 | 0 | 0 | `created_at` |
| `links` | `date` | `int(11)` NOT NULL DEFAULT 0 | 188 | 0 | 0 | `created_at` |
| `link_submissions` | `date` | `int(11)` NOT NULL DEFAULT 0 | 0 | — | — | `created_at` |
| `news_submissions` | `date` | `int(11)` NOT NULL DEFAULT 0 | 5 | 0 | 0 | `created_at` |
| `game_submissions` | `timestamp` | `varchar(32)` NULL | 2,873 | 0 | 0 | `created_at` |
| `comments` | `timestamp` | `varchar(32)` NULL | 985 | 0 | 0 | `created_at` |
| `andreas` | `timestamp` | `varchar(32)` NULL | 17 | 0 | 0 | `created_at` |

The three `varchar` columns hold no empty string and no non-numeric value,
which is what the rollback gate below relies on.

Each of these records when its row was created, and nothing else:
`ChangelogHelper::insert()` stamps a changelog entry as it writes it;
`links.date` is set in `Admin/Links/LinkController::store()` and not in
`update()`, and is rendered as "Added on …"
(`resources/views/links/card_links.blade.php:48`); the three submission
columns are set once, on submit; `dumps.date` is set in `storeDump()` and
shown next to the uploader (`games/releases/card_media.blade.php:55`);
`andreas.timestamp` has no writer in the app at all — `AboutController:17` is
its only reader. **All eight become `created_at`.** No table in this unit has
a `created_at` or `updated_at` today, so no rename collides — the
Laravel-column census in "Out of scope" is what says so.

`comments.timestamp` is the exception in kind: it is written when a comment is
posted (`GameController.php:185`, `ArticleController.php:61`,
`InterviewController.php:50`, `ReviewController.php:159`) and written again
when its owner edits it (`CommentController.php:39`), so the date the site
shows on an edited comment is the date of the edit. **`comments` gets both
Laravel columns: `timestamp` becomes `created_at`, and a nullable `updated_at`
is added alongside it.** The edit paths stop assigning either — Eloquent
maintains `updated_at` — so the comment keeps showing when it was posted.

`changelogs.timestamp` carries `changelogs_timestamp_index`, added as
`change_log_timestamp_index` by
`database/migrations/2026_08_09_000000_change_log_indexes.php:15` and renamed
with its table. It is the only index on any of the fourteen columns this plan
converts (`SELECT table_name, index_name, column_name FROM
information_schema.statistics WHERE table_schema = DATABASE() AND column_name
IN ('date', 'timestamp', 'join_date', 'last_visit')`, 2026-09-09: one row).

Every one of these except `andreas.timestamp` is written via `time()` or
`Carbon::now()->timestamp` and read back through a manual
`Carbon::createFromTimestamp()`. The two tables behind a `varchar` column —
`CommentsTable` and `Games/GameSubmissionsTable` — also sort with
`orderByRaw("{column} + 0 …")`, needed only because a `varchar` column sorts
lexicographically. `CommentFactory.php:21-22` already names the symptom:
*"anything date-shaped there sorts wrongly."*

### The migration

`2026_09_06_110100_activity_timestamps_created_at.php` calls
`UnixTimestampColumnConverter::up()` for all eight columns
(`nullable: false` for `changelogs`, `links`, `link_submissions`,
`news_submissions`; `nullable: true` for the rest). `down()` passes
`sourceType: 'integer', length: null` for the five `int` columns (plus
`default: 0` for `links`, `link_submissions`, `news_submissions`) and
`sourceType: 'string', length: 32` for the three `varchar` columns.

**The migration drops `changelogs_timestamp_index` before the conversion and
creates the index on the new column after it**, and mirrors both in `down()`.
MariaDB drops a single-column index with its column, but SQLite refuses to
drop an indexed column at all, and `migrate:fresh` runs the index migration
above on both engines:

```
SQLSTATE[HY000]: General error: 1 error in index changelogs_timestamp_index after drop column: no such column: timestamp
```

With `'prefix_indexes' => true` and an empty prefix,
`Schema::table('changelogs', fn (Blueprint $t) => $t->index('created_at'))`
derives `changelogs_created_at_index`. Nothing else in this plan needs the
index: `changesByMonth()` filters `WHERE created_at >= ?` over 61,799 rows and
`ChangelogTable` sorts and range-filters on the column.

`comments.updated_at` is added by the same migration, as a nullable `DATETIME`
matching the type the converter produces, and backfilled from the column the
converter has just written: `UPDATE comments SET updated_at = created_at`,
which needs no driver branch. The value is accurate for every existing row —
today's column already holds the time of the last edit — and `down()` drops
the column.

### The models

**Every model whose table now has a `created_at` turns on Laravel's timestamp
handling.** `public $timestamps = false` becomes `true`, and a model whose
table has no `updated_at` declares `const UPDATED_AT = null;`:
`HasTimestamps::updateTimestamps()`
(`vendor/laravel/framework/src/Illuminate/Database/Eloquent/Concerns/HasTimestamps.php:62-79`)
null-checks both column names, and `Builder::addUpdatedAtColumn()`
(`vendor/laravel/framework/src/Illuminate/Database/Eloquent/Builder.php:1266-1271`)
returns early on a null `UPDATED_AT`, so no write names a column that does not
exist. Eloquent then sets `created_at` on insert and casts it on read
(`HasAttributes::getDates()`, `:1571-1577`, feeding `isDateAttribute()` at
`:1162-1166`), which is why none of these models declares a `datetime` cast
for it, and why the writers below lose their line instead of gaining a
`now()`.

| Model | Timestamps | Other |
|---|---|---|
| `Changelog` | `UPDATED_AT = null` | `$fillable` `'timestamp'` → `'created_at'`; delete `$casts` (`:24-26`), which held only the legacy cast |
| `Comment` | both columns | `$fillable` `'timestamp'` → `'created_at'` (`:21`) |
| `GameSubmission` | `UPDATED_AT = null` | — |
| `Andreas` | `UPDATED_AT = null` | — |
| `Link` | `UPDATED_AT = null` | drop `'date'` from `$fillable` (`:19`) |
| `LinkSubmission` | `UPDATED_AT = null` | — |
| `NewsSubmission` | `UPDATED_AT = null` | delete `$casts` (`:14-16`) |
| `Dump` | `UPDATED_AT = null` | drop `'date'` from `$casts` (`:20`) and `$fillable` (`:24`) |

`Changelog` and `Comment` keep the column mass-assignable because callers pass
a value of their own: `Admin/Games/GameSubmissionController.php:71-74` builds
the approved comment with the submission's own time, and four test fixtures
build changelog entries at chosen dates (`Admin/ChangelogTableTest.php:43-53`,
`Admin/StatisticsTest.php:28`, `Public/ContentPagesTest.php:330-341`,
`Public/GameSearchTest.php:318-328`). Eloquent leaves an attribute that is
already dirty alone, so an explicit value survives.

### The code

- **Writers** — the assignment goes away entirely, since Eloquent now sets the
  column: `ChangelogHelper.php:32`, `Admin/Links/LinkController.php:73`,
  `Http/Controllers/LinkController.php:49`,
  `Http/Controllers/NewsController.php:30`,
  `Http/Controllers/GameController.php:185,206`,
  `Http/Controllers/InterviewController.php:50`,
  `Http/Controllers/ReviewController.php:159`,
  `Http/Controllers/ArticleController.php:61`,
  `Http/Controllers/CommentController.php:39` (the comment edit — this is the
  line that moved the posting time),
  `Admin/Games/Releases/ReleaseMediasDumpsController.php:80`.
  `Admin/Games/GameSubmissionController.php:73` keeps its explicit value and
  becomes `'created_at' => $submission->created_at`.
- **Readers** — `resources/views/links/card_links.blade.php:48` and
  `resources/views/components/cards/link.blade.php:19`:
  `date('F j, Y', $link->date)` becomes
  `$link->created_at->format('F j, Y')`.
  `resources/views/about/card_andreas.blade.php:64`: the same change for
  `Andreas`. `resources/views/components/cards/partial_comment.blade.php:74`
  (the game, review, interview and article comment sections, and
  `components/cards/latest-comments.blade.php`): `date('F j, Y',
  $comment->timestamp)` becomes `$comment->created_at?->format('F j, Y')`.
  `Admin/User/CommentController.php:35`,
  `admin/users/comments/card_edit.blade.php:9`,
  `admin/games/submissions/card_show.blade.php:9`: drop
  `Carbon::createFromTimestamp(...)` and call `->toDayDateTimeString()` on the
  attribute directly, keeping the rendered format.
  `admin/users/users/card_activity.blade.php:33,46,57`: the `sortByDesc()`
  keys for news submissions, link submissions and game submissions.
  `Http/Controllers/ChangelogController.php:13,58`,
  `Admin/HomeController.php:16`, `View/Components/Cards/LatestComments.php:46`,
  `AboutController.php:17`: the `orderBy()`/`sortByDesc()` column.
  `resources/views/changelog/card_changelog.blade.php:12,93,94,95` and
  `resources/views/admin/home/card_activity.blade.php:30,31`: the attribute
  name, formats unchanged.
  `Http/Controllers/GameSearchController.php:277`:
  `whereBetween('timestamp', [$from->getTimestamp(), $to->getTimestamp()])`
  becomes `whereBetween('created_at', [$from, $to])` — the query builder
  formats a `DateTimeInterface` binding with the connection's date format.
- **Livewire tables** — drop the `+ 0` `orderByRaw` sort and the
  `Carbon::createFromTimestamp()` formatter in
  `Games/GameSubmissionsTable.php:50-63` and `CommentsTable.php:21,33-46`, and
  rename the sort key and column field in both.
  **The Date column sorts an explicitly qualified column.**
  `CommentsTable`'s User sort joins `users` and `GameSubmissionsTable`'s Game
  and User sorts join `games` and `users`; after this unit and Unit 3 all four
  tables carry a `created_at`, and an unqualified order clause in a query
  carrying one of those joins is an error:

  ```
  ERROR 1052 (23000): Column 'created_at' in ORDER BY is ambiguous
  ```

  No table in this repository calls `setSingleSortingDisabled()`, and
  `WithSorting::$singleColumnSortingStatus` defaults to `true`, so one sort
  closure runs at a time and the join and the order clause do not meet today;
  `->sortable(fn (Builder $q, $d) => $q->orderBy('comments.created_at', $d))`
  and its `game_submissions` equivalent keep that from being load-bearing.
  `ChangelogTable.php:21,52-60,75-90`: the default sort key and the column
  field become `created_at`, the column takes the same qualified sort closure
  (its User column left-joins `users`), and the `from`/`to` `DateFilter`
  closures compare against `Carbon::parse($value)->startOfDay()` /
  `->endOfDay()` instead of `->timestamp`.
- **`AdminStatisticsHelper.php`**:
  - `changesByMonth()` (198-232): `select('timestamp', 'action')` and
    `where('timestamp', '>=', $from->getTimestamp())` at `:213-214` become
    `select('created_at', 'action')` and `where('created_at', '>=', $from)`;
    `date('Y-m', (int) $change->timestamp)` at `:219` becomes
    `substr($change->created_at, 0, 7)` — the plucked value is a
    `'Y-m-d H:i:s'` string on both MariaDB and SQLite, which keeps the file's
    own no-driver-specific-date-function rule (`:14-17`) and stays cheap over
    61,799 rows the way the comment at `:600-601` requires.
  - `changesByYear()` (239-241) and `commentsByYear()` (482-484): the plucked
    column name, and `bucketByYear($pluck, false)`, which reaches the `substr`
    branch Unit 1 put in `countByYear()`.
- **Factories** — `CommentFactory.php:34`: `'timestamp' => time()` becomes
  `'created_at' => now()`; delete the comment at `:21-22`.
  `LinkFactory.php:25`, `NewsSubmissionFactory.php:30`, `DumpFactory.php:27`:
  the same rename, `now()->timestamp`/`time()` becoming `now()`. A factory
  writes through `Model::unguarded()`, so an unfillable `created_at` still
  lands.
- **`database/seeders/E2ESeeder.php:320,487,616`** (`game_submissions`,
  `comments`, `links`): the key becomes `created_at` and
  `(string) now()->timestamp` / `now()->timestamp` becomes `now()`, for the
  reason in Unit 1's "The code". The `comments` row also gets
  `'updated_at' => now()`, since `DB::table()->updateOrInsert()` runs no model
  and the column has no default.
- **Tests** — rename the column and replace int / `(string) time()` /
  `mktime()` fixtures and assertions with `Carbon` instances or datetime
  strings in: `FixMenuSoftwareChangelogSectionTest.php:52`,
  `Admin/StatisticsTest.php:28`, `Admin/ChangelogTableTest.php:52`,
  `Public/ContentPagesTest.php:340`, `Public/GameSearchTest.php:327`,
  `Admin/Games/GameSubmissionTest.php:21-22,43,128` (the docblock describes the
  string column, and `:128` asserts
  `assertSame($submission->timestamp, (string) $comment->timestamp)` — it
  becomes an assertion that the approved comment carries the submission's
  `created_at`), `Admin/Games/GameControllerTest.php:280`,
  `Admin/Tables/AdminTablesTest.php:144-156,203,389-395`,
  `Admin/AdminRenderTest.php:98`.
  `Public/ContentPagesTest.php:369-389`
  (`test_a_visitor_can_edit_their_own_comment`) gains the assertion that the
  edit leaves `created_at` where it was and moves `updated_at`.
  `Public/ContentPagesTest.php:361-365` (`test_the_about_pages_render`) seeds
  one `Andreas` row (`name`, `comment`, `created_at`) before requesting
  `route('about.andreas')`: the table is empty in every test and in
  `E2ESeeder`, so `card_andreas.blade.php:64` renders zero rows today and no
  gate otherwise reaches that reader change.

### Acceptance

- `SELECT COUNT(*) FROM {changelogs,dumps,links,link_submissions,news_submissions} WHERE UNIX_TIMESTAMP(created_at) != <pre-migration int>`, and the equivalent comparison for the three `varchar` columns, return 0, row-joined on primary key.
- `SELECT COUNT(*) FROM comments WHERE updated_at != created_at` returns 0 immediately after the migration.
- `SHOW INDEX FROM changelogs` lists `changelogs_timestamp_index` on `timestamp` before the migration, `changelogs_created_at_index` on `created_at` after it, and `changelogs_timestamp_index` again after the rollback round trip below.
- `php artisan migrate:rollback --step=1` then `php artisan migrate` restores the original values exactly, `varchar` columns included — `down()` cannot distinguish an originally-empty string from a `NULL` it just wrote, but the three `varchar` columns have no empty-string rows today (see "The columns"), so this gap has no row to expose.
- `./vendor/bin/sail artisan test` passes.
- `php artisan db:seed --class=E2ESeeder` completes, and `tests/e2e/public/{links,games,about}.spec.js`, `tests/e2e/public-write/{links,games,content}.spec.js` and `tests/e2e/admin/{links,games}.spec.js` pass.
- The admin Statistics page's changes-by-month, changes-by-year and comments-by-year charts show the same figures before and after.
- Submitting a link and a game through the public forms, approving the game submission in admin, and posting a comment on a game all store the current time and sort correctly in their admin tables; editing one's own comment changes its text and leaves the date the page shows unchanged.

## Unit 3 — the user account columns

### The columns

`users.join_date`, `users.last_visit`: `varchar(32)`, nullable, 767 rows each
(`SELECT COUNT(*) FROM users`, measured 2026-09-09). 20 `join_date` and 9
`last_visit` rows hold an empty string rather than a unix-timestamp string or
`NULL` (`SELECT COUNT(*) FROM users WHERE <col> IS NOT NULL AND <col> NOT
REGEXP '^[0-9]+$'`, 2026-09-09); `UnixTimestampColumnConverter`'s
`NULLIF(column, '')` turns these into `NULL`. Both readers of those rows
already branch on falsiness (`admin/users/users/card_edit.blade.php:17,27` and
`UsersTable`'s formatters), so `''` and `NULL` render the same `-`.

**`join_date` becomes `created_at` and `last_visit` becomes
`last_visit_at`.** `join_date` is written once, at registration
(`Auth/RegisterController.php:79`), and is the row's creation time under
another name. `last_visit` is not a row-lifecycle column, so it keeps its own
name and takes the `_at` suffix the table already uses on
`email_verified_at`. `users` has no `created_at` or `updated_at` today, so
neither rename collides (the Laravel-column census in "Out of scope").

`users.email_verified_at` is already `timestamp`, nullable — no type change.
`User` has no `$casts` at all, so none of its three timestamp columns is
wrapped in `Carbon`; `hasVerifiedEmail()` only needs `is_null()`, and
`markEmailAsVerified()` only works today because `Carbon::__toString()`
happens to format as `'Y-m-d H:i:s'`.

`UsersTable.php:43-60` sorts both columns with `orderByRaw('{column} + 0 …')`,
the same varchar-sort workaround as Unit 2's Livewire tables.

### The migration

`2026_09_06_110200_user_timestamps_created_at.php` calls
`UnixTimestampColumnConverter::up('users', 'join_date', 'created_at',
nullable: true)` and `up('users', 'last_visit', 'last_visit_at', nullable:
true)`. `down()` passes `sourceType: 'string', length: 32, nullable: true` for
both — restoring the 29 empty-string rows as `NULL` rather than `''`, the one
gap named in Unit 2's acceptance gate, applying here to `created_at` (20 rows)
and `last_visit_at` (9 rows).

### The code

- **`User.php:21,27-32`**: `$timestamps` becomes `true` with
  `const UPDATED_AT = null;`, under the rule in Unit 2's "The models";
  `'join_date'` leaves `$fillable` (nothing mass-assigns the new name); and
  `protected $casts = ['last_visit_at' => 'datetime', 'email_verified_at' =>
  'datetime'];` is added — `created_at` needs no entry, Eloquent casts it.
- **Writers** — `Auth/RegisterController.php:79`: the `'join_date' => time()`
  line goes, Eloquent stamps the row. `Http/Middleware/OnlineUsers.php:39`:
  `Auth::user()->last_visit = time()` becomes
  `Auth::user()->last_visit_at = now()`; `saveQuietly()` on the next line is
  unaffected.
- **Readers** — `OnlineUsers.php:24-31`: both windows compare the column
  against `Carbon::now()->subMinute()` / `->subDay()` directly, dropping the
  `->timestamp` on each. `DeleteUnverifiedUsers.php:50-51,74`:
  `where('created_at', '<', $minDate)`, `orderBy('created_at')`, and
  `$user->created_at->toDateTimeString()` — the `where` already excludes the
  NULL rows, so the log line needs no guard.
  `admin/users/users/card_edit.blade.php:17-19,27-29`: read
  `$user->created_at` and `$user->last_visit_at` directly, keeping
  `toDayDateTimeString()` and `diffForHumans()`.
- **`UsersTable.php:43-60,71-74`**: both columns take the field name and a
  plain `orderBy()` sort closure; `users` is the base table and nothing joins,
  so the column needs no qualification. The `direction()` helper at `:71-74`
  exists only for the two raw sorts and goes with them.
- **`AdminStatisticsHelper::userSignupsByYear()` (452-454)**: the plucked
  column name, and `bucketByYear($pluck, false)`.
- **Factories** — `UserFactory.php:36-39`: `'join_date'`/`'last_visit'` become
  `'created_at'`/`'last_visit_at'` and `(string) now()->timestamp` becomes
  `now()`; the comment at `:36-37` about varchar columns goes.
- **`database/seeders/E2ESeeder.php:274-275`**: the `join_date` line goes —
  these rows are written through `User::updateOrCreate` (`:265`), so Eloquent
  stamps `created_at` — and `'last_visit' => (string) now()->timestamp`
  becomes `'last_visit_at' => now()`. That value is discarded either way:
  `last_visit` is not in `$fillable` today and `last_visit_at` is not added to
  it here, so the seeded users have no last visit before or after this unit.
- **Tests** — `Console/MaintenanceCommandsTest.php:33,67`,
  `Admin/Tables/AdminTablesTest.php:94-104` (which currently asserts the
  varchar sort bug directly, with `'999999999'` against `'1000000000'`).

### Acceptance

- `SELECT COUNT(*) FROM users WHERE (created_at IS NOT NULL AND UNIX_TIMESTAMP(created_at) != <pre-migration join_date>) OR (last_visit_at IS NOT NULL AND UNIX_TIMESTAMP(last_visit_at) != <pre-migration last_visit>)` returns 0.
- `php artisan migrate:rollback --step=1` then `php artisan migrate` restores the original non-empty values exactly; the 20 + 9 empty-string rows come back `NULL` instead of `''`, recorded here rather than treated as a failure.
- `./vendor/bin/sail artisan test` passes.
- `php artisan db:seed --class=E2ESeeder` completes, and `tests/e2e/public/{auth,account}.spec.js`, `tests/e2e/public-write/account.spec.js` and `tests/e2e/admin/users.spec.js` pass.
- Registering an account stores the current time in `users.created_at` with no code assigning it; browsing while signed in moves `last_visit_at`; the admin Users table sorts correctly on both columns.

## Unit 4 — retire `AdminStatisticsHelper`'s epoch branch

### The findings

After Units 1-3, every producer `countByYear()`/`bucketByYear()` receive is a
date or datetime string — the six call sites are `changesByYear`,
`releasesByYear`, `menuDisksByYear`, `contentByYear`, `userSignupsByYear`,
`commentsByYear`. Four of them — `changesByYear`, `contentByYear`,
`userSignupsByYear`, `commentsByYear` — only stopped passing epoch ints in
Units 1-3 above. `releasesByYear` and `menuDisksByYear` already passed date
strings, since `game_releases.date` and `menus.date` were `DATE` columns
before this plan. No call site still needs `$epoch = true`.

### The change

`countByYear()` (`AdminStatisticsHelper.php:591-616`): delete the `$epoch`
parameter and the `date('Y', (int) $date)` branch, leaving the
`(int) substr($date, 0, 4)` Unit 1 put in place as the only path.
`bucketByYear()` (562-565): delete its `$epoch` parameter and stop forwarding
it. Update all six call sites to drop the second argument.

### Acceptance

- `grep -n '\$epoch' app/Helpers/AdminStatisticsHelper.php` returns nothing.
- `./vendor/bin/sail artisan test` passes.
- The admin Statistics page's six year-bucketed charts show the same figures as immediately before this unit.

## Verification

Run after every unit, not once at the end: `./vendor/bin/sail artisan test`,
then `php artisan migrate:fresh` and `php artisan db:seed --class=E2ESeeder`
against the e2e database followed by the specs named in that unit's
acceptance. `E2ESeeder` writes most of its rows through
`DB::table()->updateOrInsert()`, which no cast and no model reach, so it is
the one gate that catches a raw unix timestamp, or an old column name, still
being handed to a converted column.

Against the long-lived dev database, before dropping the pre-migration dump
named in "Deploying": each unit's acceptance queries above, run once before
migrating (recording the values compared against) and once after.

## Deploying

A dump immediately before each of the three schema units — three separate
deploys, not one, since each changes real row data on a table with live
writers, unlike the metadata-only index rename in
`2026-09-06-index-name-consistency.md`. Unit 4 deploys with no dump, since it
changes no schema and no stored data.

## Out of scope

- **`sndhs.year`** — a calendar year (`AdminStatisticsHelper::YEAR_MIN`/
  `YEAR_MAX` = 1980/2030), not a timestamp. Correctly typed.
- **`database_changes.execute_timestamp`** — the CPANEL schema-change ledger.
  `2026-08-26-schema-consistency-sweep.md` ("dead table — out of scope") and
  `2026-08-31-column-name-consistency.md` ("kept record tables, not
  model-backed entities") already ruled this a kept historical record with no
  reader and no model. Its name is left alone with its type.
- **`game_releases.date`, `magazine_issues.published`, `menus.date`** —
  already `DATE`, and each is a fact about the thing rather than a record of
  the row, so none becomes a `_at` column.
  **`created_at`/`updated_at`** on `Game`, `GameVideo`, `GameVote`,
  `Magazine*`, `Menu*` and `password_resets` — already Laravel-managed
  `TIMESTAMP`, already auto-cast, already named to the convention. The
  `TIMESTAMP`/`DATETIME` split between those tables and the columns this plan
  writes is left as it is: `DATETIME` is what keeps the conversion a
  round trip and what keeps the 2038 limit out of the converted columns.
- **`users.email_verified_at`** — already `timestamp` and already named to
  the convention; Unit 3 adds its cast and nothing else.
- **Column order** — the converter's add/backfill/drop cycle moves each
  converted column to the end of its table, in both directions. A
  `SHOW CREATE TABLE` diff across the migration is therefore not empty even
  though every value round-trips; the acceptance gates compare values and the
  index, not column position.

The four censuses, run against the dev database. The first three describe the
end state; the fourth also gates the renames before they run, so its expected
result is given for both sides of the migration:

```sql
-- The moment-column sweep: anything whose name says it holds a moment and is
-- not typed as one.
-- On 2026-09-09 this returns the fourteen columns this plan converts and
-- `database_changes.execute_timestamp`; it expects execute_timestamp alone
-- once Units 1-3 ship. The word boundaries matter: a bare `date` also matches
-- the three `database_changes` columns that spell `update`.
SELECT table_name, column_name, data_type
FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND column_name REGEXP '(^|_)(date|time|timestamp|visit|published|join)($|_)'
  AND data_type NOT IN ('date', 'datetime', 'timestamp');
```

```sql
-- The type census: expects 15 rows once Units 1-3 ship, every data_type `datetime`.
-- Listing rather than filtering, so a column that is missing under its new
-- name is a short count rather than a pass.
SELECT table_name, column_name, data_type
FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND (table_name, column_name) IN (
    ('articles', 'published_at'), ('news', 'published_at'),
    ('interviews', 'published_at'), ('reviews', 'published_at'),
    ('changelogs', 'created_at'), ('dumps', 'created_at'), ('links', 'created_at'),
    ('link_submissions', 'created_at'), ('news_submissions', 'created_at'),
    ('game_submissions', 'created_at'), ('comments', 'created_at'),
    ('comments', 'updated_at'), ('andreas', 'created_at'),
    ('users', 'created_at'), ('users', 'last_visit_at')
  )
ORDER BY table_name, column_name;

-- The old-name census: expects 0 rows once Units 1-3 ship.
SELECT table_name, column_name
FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND column_name IN ('date', 'timestamp', 'join_date', 'last_visit')
  AND table_name IN (
    'articles', 'news', 'interviews', 'reviews', 'changelogs', 'comments',
    'game_submissions', 'andreas', 'links', 'link_submissions',
    'news_submissions', 'dumps', 'users'
  );

-- The Laravel-column census: every table that already carries a Laravel
-- timestamp column, so a rename into that name would collide.
-- On 2026-09-09 this returns `games`, `game_videos`, `game_votes`,
-- `magazines`, `magazine_indices`, `magazine_issues`, the nine `menu*`
-- tables and `password_resets` — the tables named above under "Out of scope"
-- and no others, which is what makes every rename in Units 2 and 3 safe.
-- Once Units 1-3 ship it returns those plus the nine converted tables.
SELECT table_name, column_name
FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND column_name IN ('created_at', 'updated_at')
ORDER BY table_name, column_name;
```
