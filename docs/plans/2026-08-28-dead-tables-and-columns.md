# The dead table and column removal

*2026-08-28*

Successor to the [schema consistency sweep](2026-08-26-schema-consistency-sweep.md),
which listed ten candidate dead tables and deferred them to "one review that
asks the same question of all of them". This is that review, widened to columns
and re-measured from scratch rather than from that list.

The end state: the thirteen tables and four columns named below are gone from
the schema; `AdminStatisticsHelper::counts()` has no `Bug reports` entry;
`User::getIsDeletableAttribute()` queries two relations rather than three; no
factory or seeder sets a column that no longer exists; and `php artisan test`
passes.

Every figure was measured against the dev MariaDB 10.11 on 2026-08-28, with the
query named next to it. Decisions were settled with nicolas on 2026-08-28.

**Executed 2026-08-29**, all five units, in plan order. One correction was made
in the doing and is recorded under "The `down()` DDL" below; two acceptance
criteria named things that do not exist and are corrected there too. Nothing
else departed from the text: the schema is at 119 tables on both lineages, and
the full PHPUnit suite passes.

### The `down()` DDL

The plan says each `down()` recreates its tables "from the DDL in
`database/migrations/2020_10_17_161643_create_*_table.php`". That DDL is stale
for three of the thirteen, because later migrations changed the tables it
describes, and a `down()` written from it would have restored a schema the
application no longer expects. Every `down()` was written from
`SHOW CREATE TABLE` against dev instead, and each was verified by rolling the
unit back and diffing the result against what was there before the drop.

What the 2020 files get wrong:

- `bug_report` and `bug_report_type` name their primary keys `bug_report_id`
  and `bug_report_type_id`. The schema consistency sweep renamed both to `id`
  on 2026-08-28, in migrations dated after the create files. The `down()`
  recreates them as `id`.
- `bug_report` had no foreign keys in 2020. It has two `ON DELETE SET NULL`
  today, added by `2026_08_28_120000_user_side_foreign_keys.php`, and the
  `down()` recreates both. `bug_report_text` is also `mediumtext` live against
  the create file's `text()`.
- `dump_user_info`'s foreign key migration says
  `->references('user_id')->on('users')`, stale since `users.user_id` became
  `users.id`. The `down()` references `id`, and also carries the table comment
  the create file omits.

`news_search_wordlist` needed a second departure of a different kind. The 2020
file creates `news_word_id` first and then adds the index and AUTO_INCREMENT by
raw `ALTER`; live, the column sits second, is `mediumint(8) unsigned`, and its
index is already there. The `down()` declares the column in its live position
with its live type and issues one `ALTER` for the AUTO_INCREMENT, still guarded
against SQLite, which cannot express AUTO_INCREMENT on a column that is not the
primary key. This is the same wall the original migration hit, and its `FIXME`
is inherited rather than resolved.

### Two acceptance criteria that named nothing

Unit 2's acceptance says `php artisan users:delete-unverified`. The command is
`user:delete-unverified`, singular; it was run under that name and exited 0.
`CLAUDE.md` carried the same typo and is corrected in the same commit.

Unit 3's acceptance asks that "the news list and a news detail page render".
There is no public news detail route -- `route:list --path=news` shows
`news.index` and `news.submit` and nothing else, the list being the whole of the
public section. The list was checked and returns 200.

### Figures that moved

`php artisan test` is 1010 passed with nothing skipped, not the "992 passed, 18
skipped" the plan measured at 66aabc0. The 18 skips went with the move to Sail
(7edd972), which is a commit newer than the measurement, not a change this plan
made. The count is 1010 before and after all five units; unit 5 removes one
assertion, taking 3594 to 3593.

The reachability script re-run at the end reports 116 tables, one of them still
the `notifications` that no lineage has, so 115 of the 119 are reachable -- the
same 115 as before, since none of the thirteen dropped tables had a model.

| Unit | Scope | Rows destroyed | Code changes |
|---|---|---|---|
| 1 — the empty tables | 7 tables | 0 | `User`, one import |
| 2 — the legacy auth tables | 2 tables | 528 | none |
| 3 — the old news search index | 2 tables | 38,898 | none |
| 4 — the bug report pair | 2 tables | 5 | `AdminStatisticsHelper` |
| 5 — the dead columns | 4 columns on 2 tables | 0 rows; values on 955 | `User`, `ResetPasswordController`, 3 factories/seeders, 1 test |

The delivery unit is one commit per unit, based on `development`. Each unit
carries one migration, so reversing a deployed unit is
`migrate:rollback --step=1` and reverting the commit removes that one migration
file.

The five units are independent of each other and can be executed in any order.
Ordering exists only inside unit 4, where `bug_report` is dropped before
`bug_report_type` because a foreign key points from the first to the second.

## How "dead" was established

Four passes, each run on 2026-08-28. A table or column is dead only when every
pass that applies to it finds nothing.

**Eloquent reachability.** A script instantiated all 83 models in `app/Models`,
called every zero-argument public method declared on the model, and collected
`getTable()` for each returned `Relation` plus `getTable()` for each
`BelongsToMany` pivot. The script collects 116 table names, but one of them —
`notifications`, from the `Notifiable` trait on `User` — is not a table dev has.
115 of the 132 tables are reachable this way, and 17 are not. 49 tables have no
model of their own, and this pass accounts for 32 of
them: they are pivots that a relation resolves, either by derivation
(`belongsToMany(Crew::class)`) or from a name inside the relation
(`belongsToMany(Individual::class, 'crew_individual')`). A grep alone
misclassifies the derived ones.

**Literal reference.** `grep -rnw` for each table and column name across `app`,
`resources`, `routes`, `config`, `database/factories`, `database/seeders` and
`tests`, excluding `database/migrations`. A migration naming a table is not a
reader.

**Hand verification of every hit.** Required, because the grep produces false
positives on common words. `theme` matches `config/livewire-tables.php:7` and
Bootstrap chart colours; `tools` matches the FontAwesome class `fa-tools` in
`resources/views/admin/layouts/nav.blade.php:167`; `dump.info` looked dead
against `$this->info(` in `app/Console/Commands/DiscardFilepondUploads.php` and
is alive at `resources/views/games/releases/card_media.blade.php:57`; `session`
matches `$request->session()` in 14 controllers.

The column pass covered all 546 columns. 47 have no literal reference at all:
27 sit inside the thirteen tables units 1 to 4 drop, 8 are pivot foreign keys
that `belongsToMany` derives, and 12 sit in tables this plan keeps.

A fourth filter caught what a bare grep cannot — a column whose only references
are a model's `$fillable`, a factory or a seeder is declared rather than read.
That is 31 columns: 20 foreign keys a relation names, 2 framework columns
(`migrations.batch` and `users.remember_token`), 6 kept for the reasons in "Out
of scope", and 3 in unit 5 — `users.session`
(`database/factories/UserFactory.php:47`), `users.show_email`
(`UserFactory.php:54` and `database/seeders/E2ESeeder.php:280`) and
`website.user_ip` (`database/factories/WebsiteFactory.php:31`).

Two of those land in this bucket only after hand verification clears the noise
first. `users.session` has 65 literal hits and 64 of them are
`$request->session()`, `config/session.php` prose, or the word in a comment,
leaving `UserFactory.php:47`; `users.karma` has three, one of which is the
sentence "upgrade your karma stats ;-)" at
`resources/views/components/cards/latest-comments.blade.php:13`, leaving
`UserFactory.php:53` and `E2ESeeder.php:279`. Prose in a UI string or a comment
is not a read.

The remaining column in unit 5, `users.password`, passes both automatic filters:
it is in `$fillable`, a controller writes it, and a test asserts on it. It was
found by hand verification of what those sites do — the write sets `null` and
authentication never reads the column, for the reasons in unit 5.

## Unit 1 — the tables that hold nothing

### The tables

Seven tables, no model, no relation, no reference outside
`database/migrations`, and zero rows. Row counts from
`SELECT COUNT(*)` per table, 2026-08-28.

| Table | Rows | Note |
|---|---|---|
| `theme` | 0 | |
| `theme_style` | 0 | |
| `theme_template` | 0 | |
| `tools` | 0 | |
| `gameinfo_screenshot` | 0 | |
| `dump_user_info` | 0 | holds two `RESTRICT` foreign keys into `dump` and `users` |
| `personal_access_tokens` | 0 | Sanctum's table; Sanctum is not installed |

`laravel/sanctum` appears in `composer.lock` only inside another package's
`require-dev` block, and `vendor/laravel/` contains `framework`, `prompts`,
`serializable-closure` and `ui`. Nothing publishes or reads
`personal_access_tokens`.

**`personal_access_tokens` exists only on the production lineage.** No migration
creates it: `grep -rln 'personal_access_tokens' database/migrations` finds
nothing, and a `migrate:fresh` on SQLite produces 131 tables against dev's 132,
the difference being exactly this table. Its `up()` therefore uses
`Schema::dropIfExists()`, and its `down()` recreates it unconditionally, which
is correct for the production rollback the `down()` exists to serve.

### The `User` change

`dump_user_info` is queried in one place,
`app/Models/User.php:168`, inside `getIsDeletableAttribute()`:

```php
return ! DB::table('dump_user_info')->where('user_id', $this->getKey())->exists();
```

It blocks no account. `SELECT COUNT(DISTINCT user_id) FROM dump_user_info`
returns 0, against 113 for `game_submitinfo` and 4 for `dump`. Removing the
check changes no answer the method gives today.

The method returns `! $this->dumps()->exists()` after the two existing guards.
The docblock above it names three blocking relations and becomes two, and
`use Illuminate\Support\Facades\DB;` goes with the call — line 168 is the only
`DB::` use in the file.

### The migration

One migration, `drop_empty_legacy_tables`. `up()` drops the seven with
`Schema::dropIfExists()`. `down()` recreates all seven empty, from the DDL in
`database/migrations/2020_10_17_161643_create_*_table.php` for the six that
have one and from `SHOW CREATE TABLE personal_access_tokens` for the seventh.

A `down()` restores structure, never rows. This matters for units 2, 3 and 4
and is stated once here.

### Acceptance

- `SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'atarilegend' AND TABLE_NAME IN ('theme','theme_style','theme_template','tools','gameinfo_screenshot','dump_user_info','personal_access_tokens')`
  returns 0.
- `php artisan test` passes: 992 passed, 18 skipped, measured on `development`
  at 66aabc0 on 2026-08-28. No test names any of the seven, so the suite is a
  regression gate rather than a proof; the query above is the real gate.
- `php artisan migrate:rollback --step=1` followed by `php artisan migrate`
  succeeds, which is what a bad deploy does.
- `grep -rn dump_user_info app` returns nothing.
- An account with neither a game submission nor a dump still shows a delete
  button on `/admin/users`
  (`resources/views/admin/users/users/datatable_actions.blade.php:5`).

## Unit 2 — the legacy authentication tables

### The tables

| Table | Rows | Data range | Superseded by |
|---|---|---|---|
| `users_reset` | 22 | 2017-10-01 to 2020-07-14 | `password_resets`, named at `config/auth.php:96` |
| `users_login_attempts` | 506 | 2017-09-20 to 2024-06-10 | Laravel's login throttle |

Neither has a model, a relation, or a reference outside
`database/migrations`. Neither carries a foreign key, so neither participates
in a cascade and `users` rows delete without touching them. Date ranges from
`SELECT FROM_UNIXTIME(MIN(time)), FROM_UNIXTIME(MAX(time))` on each, 2026-08-28.

`users_reset` holds a `password varchar(128)` column of SHA-512 reset tokens
alongside a `user_id` that no foreign key constrains. 11 of its 22 rows name an
account that no longer exists.

The only mention of either table in the application is prose:
`app/Models/User.php:141` lists them among the relations that do not block a
user deletion. That comment loses two names.

### No framework path reaches either table

A table can be written without the application naming it, so both were checked
against the framework rather than only against `app/`. Re-verified 2026-08-28.

**Nothing in the framework knows the names.** `grep -rn` for both across
`vendor/` returns nothing, so no package can reach them by convention. The only
table names any config sets are `password_resets` (`config/auth.php:96`),
`migrations`, `cache`, `sessions`, `jobs`, `job_batches` and `failed_jobs`.

**Password resets go elsewhere, and the evidence is live.**
`AUTH_PASSWORD_RESET_TOKEN_TABLE` is unset in `.env` and `.env.example`, so the
broker uses `password_resets`, which holds 13 rows whose newest is dated
2026-08-20 — eight days before this plan. `users_reset` last took a row in 2020.

**The login throttle never touches a table.** `LoginController` uses
`AuthenticatesUsers`, which uses `ThrottlesLogins`
(`vendor/laravel/ui/auth-backend/AuthenticatesUsers.php:12`), whose `limiter()`
resolves `Illuminate\Cache\RateLimiter`
(`vendor/laravel/ui/auth-backend/ThrottlesLogins.php:100`). Attempts are counted
in the cache store, `CACHE_STORE` defaulting to `file`. The only `throttle`
middleware in the app is `throttle:6,1` on email verification
(`app/Http/Controllers/Auth/VerificationController.php:37`).

**The database does nothing on its own.** No foreign key points into or out of
either table, and the schema has no trigger, view, stored routine or event at
all — four `information_schema` queries, all empty.

**The writer was CPANEL, and it is gone.** The retired code inserts into
`users_login_attempts` at `public/php/lib/user_functions.php:86` and `:142` and
counts back from it at `:162`; it writes, reads and deletes `users_reset` at
`public/php/common/login/db_reset.php:61`, `:161`, `:189` and `:220`. That is
what the post-Laravel rows are: 5 in 2021, 5 in 2022, 2 in 2024, and none since
2024-06-10, against CPANEL's retirement on 2026-08-22. Its checkout is not
mounted by any Compose service and no workflow deploys it.

### The migration

One migration, `drop_legacy_auth_tables`, dropping both. `down()` recreates
both empty from
`database/migrations/2020_10_17_161643_create_users_reset_table.php` and
`database/migrations/2020_10_17_161643_create_users_login_attempts_table.php`.
`users_login_attempts` has no primary key and the recreation keeps it that way.

### Acceptance

- Both tables absent from `information_schema.TABLES`.
- `php artisan test` passes.
- `php artisan user:delete-unverified` runs to completion on the dev database
  without `--delete`, which exercises `getIsDeletableAttribute()` over every
  unverified account.

## Unit 3 — the old news search index

### The tables

`news_search_wordlist` (6,584 rows) and `news_search_wordmatch` (32,314 rows),
counted 2026-08-28. A pre-Laravel inverted index over news headlines and bodies.

Neither has a model, a relation, or a reference outside
`database/migrations`. `news_search_wordmatch` has no primary key;
`news_search_wordlist` has a `varchar(50)` primary key on `news_word_text`.
Neither carries a foreign key: `news_search_wordmatch.news_id` is a
`mediumint(8) unsigned` that does not match `news.id`'s `int(11)`, so no
constraint could be added without changing its type.

Nothing writes them. The newest row cannot be dated — neither table has a
timestamp column.

Unit 3 destroys more rows than the other four together, and those rows are
derived data: an index over `news` is rebuildable from `news`, which this unit
does not touch.

### The migration

One migration, `drop_news_search_index`, dropping both. `down()` recreates both
empty, including `news_search_wordlist`'s primary key on `news_word_text` and
the two secondary indexes on `news_search_wordmatch`.

### Acceptance

- Both tables absent from `information_schema.TABLES`.
- `SELECT COUNT(*) FROM news` returns 435, unchanged.
- `php artisan test` passes.
- The news list and a news detail page render.

## Unit 4 — the bug report pair

### The tables

`bug_report` holds 1 row, a report about the pre-Laravel site quoting
`https://www.atarilegend.com/games/games_detail.php?game_id=5221`.
`bug_report_type` holds 4 lookup rows: Bug, Layout, General, Suggestion.

Neither has a model or a relation. Nothing writes either. The single reader is
one count on the admin statistics page,
`app/Helpers/AdminStatisticsHelper.php:139`:

```php
'Bug reports'       => DB::table('bug_report')->count(),
```

**The statistics entry is removed with the tables.** It reports a figure that
has been 1 since 2020 for a form the application does not have.

`bug_report` carries two foreign keys, both `ON DELETE SET NULL`:
`bug_report_bug_report_type_id_foreign` into `bug_report_type` and
`bug_report_user_id_foreign` into `users`. Dropping `bug_report` first drops
both with it, so `bug_report_type` drops without an explicit
`dropForeign()`.

### The migration

One migration, `drop_bug_report_tables`, dropping `bug_report` then
`bug_report_type`. `down()` recreates `bug_report_type` first, then
`bug_report` with both foreign keys, from
`database/migrations/2020_10_17_161643_create_bug_report_table.php` and
`database/migrations/2020_10_17_161643_create_bug_report_type_table.php`.

Dropping `bug_report_type` first fails, verified against the dev database on
2026-08-28:

```
ERROR 1451 (23000): Cannot delete or update a parent row: a foreign key constraint fails
```

### The code change

`app/Helpers/AdminStatisticsHelper.php:139` is deleted. The `Community` group
of `counts()` goes from nine entries to eight.

The docblock at `app/Models/User.php:141` lists `bug_report` among the relations
that do not block a user deletion, and loses that one name. Unit 2 removes two
other names from the same sentence; whichever unit lands second edits what the
first left.

No test asserts on the `Bug reports` label: `grep -rn 'Bug report' tests` finds
nothing, and the three specs that cover the statistics page —
`tests/Feature/Admin/StatisticsTest.php`,
`tests/Feature/Helpers/StatisticsHelperTest.php` and
`tests/e2e/admin/others.spec.js` — name no `bug` at all.

### Acceptance

- Both tables absent from `information_schema.TABLES`.
- `/admin/others/statistics` renders and shows no `Bug reports` row.
- `php artisan test` passes, including
  `tests/Feature/Admin/StatisticsTest.php`.

## Unit 5 — the dead columns

### The columns

Four columns on two live tables. Values counted 2026-08-28 with
`SELECT COUNT(*), SUM(col IS NULL), COUNT(DISTINCT col)` per column.

| Column | Type | Rows carrying a value | Why it is dead |
|---|---|---|---|
| `users.password` | `varchar(255) NULL` | 409 of 767 | `User::getAuthPasswordName()` returns `sha512_password` (`app/Models/User.php:65`), so `getAuthPassword()` resolves to that column and never to this one |
| `users.session` | `varchar(32) NULL` | 345 of 767 | CPANEL session tokens; only `database/factories/UserFactory.php:47` names it, setting `null` |
| `users.show_email` | `tinyint(1) NOT NULL DEFAULT 0` | 1 of 767 set to 1 | no page renders a user's email address, and no form offers the toggle |
| `website.user_ip` | `varchar(32) NULL` | 95 of 188 | submitter IP addresses; only `database/factories/WebsiteFactory.php:31` names it, setting `null` |

`users.password` is written and never read. `setUserPassword()` at
`app/Http/Controllers/Auth/ResetPasswordController.php:46` assigns `null` to it
under the comment "Empty old MD5 password", and `UserFactory` and `E2ESeeder`
fill it with a bcrypt hash that authentication does not consult. Authentication
runs through `App\Providers\Auth\UserProvider::validateCredentials()`, which
compares `$user->sha512_password` against
`UserHelper::hashPassword($credentials['password'], $user->salt)`. The framework
reaches the column only through `getAuthPassword()`, whose implementation at
`vendor/laravel/framework/src/Illuminate/Auth/Authenticatable.php:66` is
`return $this->{$this->getAuthPasswordName()};`.

`website.user_ip` holds 95 IP addresses that nothing reads and no retention rule
covers.

### The code changes

Dropping a column that a factory still sets fails loudly, because
`app/Providers/AppServiceProvider.php:80` and `:94` enable
`Model::preventAccessingMissingAttributes()` and
`Model::preventSilentlyDiscardingAttributes()`:

```
Add [password] to fillable property to allow mass assignment on [App\Models\User].
The attribute [password] either does not exist or was not retrieved for model [App\Models\User].
```

Every site, all of them found by
`grep -rnw --include='*.php' -e password -e session -e show_email -e user_ip`
filtered by hand:

| File | Line | Change |
|---|---|---|
| `app/Models/User.php` | 29 | drop `'password'` from `$fillable` |
| `app/Http/Controllers/Auth/ResetPasswordController.php` | 45-46 | delete the comment and `$user->password = null;` |
| `database/factories/UserFactory.php` | 36 | delete the `'password'` line, the `protected static ?string $password` property at line 18 and its docblock, and the `use Illuminate\Support\Facades\Hash;` import at line 7 |
| `database/factories/UserFactory.php` | 47 | delete the `'session'` line |
| `database/factories/UserFactory.php` | 54 | delete the `'show_email'` line |
| `database/seeders/E2ESeeder.php` | 271 | delete the `'password'` line; `Hash` is imported at line 9 and used only here |
| `database/seeders/E2ESeeder.php` | 280 | delete the `'show_email'` line |
| `database/factories/WebsiteFactory.php` | 31 | delete the `'user_ip'` line |
| `tests/Feature/Public/AuthTest.php` | 486 | delete `$this->assertNull($user->password);` and the clause about the MD5 password in the docblock at line 466 |

`UserFactory`'s class docblock states that every column of `users` is set
because the models are strict; it stays true with three fewer lines.

`'password'` also appears as a request field in validation rules and as a form
input name — `app/Http/Controllers/Auth/RegisterController.php:52`,
`app/Http/Controllers/Auth/UserController.php:69`, and 33 Blade lines across the
six files under `resources/views/auth/`. Those name the submitted field, not the
column, and are unchanged.

### The migration

One migration, `drop_dead_user_and_website_columns`, dropping the four columns
in two `Schema::table()` calls. None of the four carries an index: `users` has
only `PRIMARY` and a non-unique index on `userid`, and `website` has no index on
`user_ip`.

`down()` re-adds all four with the types in the table above, and `show_email`
comes back `NOT NULL DEFAULT 0` so the recreation matches the current DDL.
Values are not restored, and the columns come back at the end of their tables
rather than at ordinal positions 3, 8 and 18 in `users` and 10 in `website`.

### Acceptance

- `SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'atarilegend' AND ((TABLE_NAME = 'users' AND COLUMN_NAME IN ('password','session','show_email')) OR (TABLE_NAME = 'website' AND COLUMN_NAME = 'user_ip'))`
  returns 0.
- `php artisan test` passes, in particular
  `tests/Feature/Public/AuthTest.php` and `tests/Feature/Public/ProfileTest.php`,
  which cover registration, login, the profile password change and a password
  reset. With strict models on, a missed factory line fails the suite rather
  than passing silently.
- The Playwright `public-write/account.spec.js` password round trip passes:
  it changes the password, signs in with the new one, and changes it back
  (`tests/e2e/public-write/account.spec.js:130`).
- `php artisan db:seed --class=E2ESeeder` completes.

## Verification

Run after every unit:

```
php artisan test
php artisan migrate:rollback --step=1 && php artisan migrate
```

Run once at the end, against dev:

```sql
SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'atarilegend';
```

It returns 119, down from 132 measured 2026-08-28.

Re-run the Eloquent reachability script from "How dead was established" and
confirm it reports no table that the schema no longer has.

## Deploying

A `DROP TABLE` cannot be undone by a rollback, and 39,431 rows across units 2,
3 and 4 do not exist anywhere else. Take a `mysqldump` of the production
database before deploying any of the five units, and keep it until all five are
deployed.

Restoring that dump over a live database requires `DROP DATABASE` first.
`mysqldump` output does not drop tables absent from it, so restoring onto a
database where a later migration has already run leaves both schemas present and
the next `migrate` fails.

Deploy the units one at a time. `.github/workflows/build-and-deploy.yml` runs
`migrate --step=1` on rollback, and a unit is one migration, so a bad deploy of
one unit reverses without touching the others.

## Out of scope

### The tables that hold data nothing reads

Both were measured and are deliberately kept.

**`game_gallery`** — 118 rows, magazine adverts for 101 distinct games, with
captions such as "Advert from magazine" and an `image_ext` column. All 118 image
files are on disk in `storage/app/public/images/game_gallery/`. It is content
waiting for a feature rather than a dead table, and the plan does not touch the
table, the rows or the files.

**`database_change`** — 267 rows, CPANEL's own schema-change ledger, from
2017-09-19 to 2020-10-18, the last entry being "Create migrations table for
Laravel". Kept as a record of the pre-Laravel schema history.

### The columns that hold data nothing reads

| Column | Values | Kept because |
|---|---|---|
| `users.karma` | 48 distinct, 60 accounts at 10 | real per-account history |
| `website.rate_number` | 21 distinct, up to 1,295 | vote counts from the retired link rating |
| `website.rate_score` | 50 distinct, up to 9,885 | scores from the same feature |
| `website.website_count` | 95 distinct, up to 7,405 | per-link view counter |
| `crew_individual.individual_nick_id` | 35 of 470 rows | records the nickname a person used in a crew; `Crew::individuals()` does not `withPivot()` it |
| `news_image.news_image_name` | 256 distinct of 264 rows | image names |
| `location.country_iso3` | 246 distinct of 254 rows | ISO 3166-1 alpha-3 codes; the site uses `country_iso2` |
| `sndhs.default_subtune` | NULL in all 10,371 rows | `app/Console/Commands/GenerateSNDHJson.php:134` writes `defaultSubtune` into the JSON index and not into this column |

### The columns a grep alone calls dead

28 columns are alive because a relation derives them, in two classes.

8 have no literal reference anywhere: `game_control.control_id`,
`game_programming_language.programming_language_id`,
`game_release_copy_protection.copy_protection_id`,
`game_release_disk_protection.disk_protection_id`,
`game_release_emulator_incompatibility.emulator_id`,
`game_release_resolution.resolution_id`, `game_sound_hardware.sound_hardware_id`
and `screenshot_game_fact.game_fact_id`. Each is one half of a `belongsToMany`
that names neither key.

20 more are referenced only from a model, a factory or a test —
`crew_individual.crew_id`, `crew_menu_set.crew_id`, `game_release_crew.crew_id`,
`sub_crew.crew_id`, `sub_crew.parent_id`, `individual_nicks.nick_id`,
`media.media_type_id`, `media_scan.media_id` and
`screenshot_game_submitinfo.game_submitinfo_id` among them.

Both classes are listed so the next audit does not count them as a finding.

`users.remember_token` and `migrations.batch` are alive for a different reason:
the framework reads both.

### The storage directories

`storage/app/public/images/game_art_gallery/` and
`storage/app/public/images/thumbnails/` hold zero files, and
`storage/app/public/images/news_image/` holds one against
`news_images/`'s 265. Files on disk are a separate inventory from tables and
columns, and no unit here deletes a file.

### The tables with no primary key

Ten tables have no primary key today, counted against dev on 2026-08-28 by
joining `information_schema.TABLES` to `information_schema.TABLE_CONSTRAINTS`
on `CONSTRAINT_TYPE = 'PRIMARY KEY'`: `game_control`, `game_developer`,
`game_engine`, `game_genre_cross`, `game_programming_language`,
`game_release_crew`, `game_release_distributor`, `news_search_wordmatch`,
`password_resets` and `users_login_attempts`.

Two leave the list here, by being dropped rather than fixed:
`users_login_attempts` in unit 2 and `news_search_wordmatch` in unit 3. Eight
remain, and those eight were ruled on by the schema consistency sweep and are
not reopened.

The sweep's own "nine remaining" list omitted `news_search_wordmatch`, even
though the same document flagged the table as having no primary key at all. The
count above was re-measured rather than inherited.
