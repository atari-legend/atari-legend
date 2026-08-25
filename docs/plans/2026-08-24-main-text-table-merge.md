# Merging the split content tables

Successor to `2026-08-23-foreign-key-rename.md`, which deferred its Group 2 —
sixteen foreign keys named `article_id`, `interview_id`, `review_id` and
`screenshot_id` — on nicolas's reasoning: *"There's no need to have separate
tables for the content, it could be a single table that contains all the columns
of the two."* Renaming those keys before the merge is work done twice and
discarded once. This plan is that merge.

**Status: proposed, decisions taken.** Every figure below was measured against
this repository and the local MariaDB 10.11.18 on 2026-08-24, and the commands
are named so they can be re-run. The migration template in "The migration, and
what it was tested against" was executed for real — `up`, `rollback`, `up` again
on MariaDB with production data, and a full `migrate` on SQLite — and the dev
database was restored from a dump afterwards. Where a claim was checked by
running something, it says so; where it is a judgement, it says that too.

**Reviewed 2026-08-25**, by re-running the measurements and the migration
template against a scratch copy of the dev database. The findings are folded in
below; the one that would have failed a production deploy is the Phase 5
backfill, which needs `ORDER BY t.id LIMIT 1` because that pair's duplicates are
expected.

**Re-reviewed twice more on 2026-08-25**, each pass re-running every measurement
and the migration template against a scratch copy of the dev database. All of
those findings are folded in here and the review documents are not kept — this
plan is the single source. Three of them change what an implementer does:
hazard 2 fails *silently* rather than loudly and nothing in either suite catches
it; Phase 2's `down()` needs `->nullable()` for the same reason Phases 5 and 6
do; and the Phases 5/6 `down()` was described two contradictory ways and is now
pinned to one shape. The rest were counts, two of which had been "re-measured"
wrongly — where a figure was corrected more than once, what each wrong number
actually counted is recorded next to the right one, so it is not reinstated by
someone re-deriving it.

It is written to be read alongside the two previous plans and does not repeat
what they establish about deploy ordering, `renameColumn` on MariaDB, or the
strictness guards in `AppServiceProvider`.

**One earlier measurement is corrected here, and it is the largest single finding
in this document.** The foreign key plan's reconnaissance called
`individuals`/`individual_text` a "1:0..1" pair that "merges with nullable
columns". It is not 1:0..1. **Fourteen individuals have two text rows each**, so
a naive merge would silently discard one row for each of them. See Phase 5, where
it turns out to be harmless for a reason that had to be checked rather than
assumed — but where it still forces the backfill statement to be written
differently from the other four.

## Decisions taken

Decisions 1-5 were settled with nicolas on 2026-08-24; decision 6 on
2026-08-25. Three of them changed what the phases contain, one added two phases,
and the last changed how they are delivered.

| # | Question | Decision |
|---|---|---|
| 1 | Do the four `*_main` tables get renamed? | **Yes, and plural** — `articles`, `interviews`, `reviews`, `screenshots`. Phase 4. |
| 2 | Nullability of the four review score columns | **Nullable.** Keep the "this review has no score" state that the public page already guards. |
| 3 | Widen `interview_text` from `TEXT` to `MEDIUMTEXT` while it is being recreated? | **No.** Keep `TEXT`. Recorded as a live observation under hazard 5, not acted on. |
| 4 | Are `individuals`/`individual_text` and `pub_dev`/`pub_dev_text` in scope? | **Yes**, as Phases 5 and 6. |
| 5 | Do the integer unix-timestamp date columns become real `datetime` columns? | **No.** A separate campaign with its own blast radius. |
| 6 | One pull request per phase, or one branch? | **One branch, one commit per phase.** All six ship in a single deploy — see "How the six phases reach production", which is where the consequences live. |

### On decision 1, since the question was raised

Laravel's table convention is **plural**, not singular:
`Model::getTable()` is `Str::snake(Str::pluralStudly(class_basename($this)))`
(`vendor/laravel/framework/.../Model.php:1892`). Singular is the *model class*
and the *foreign key*. The 23 models in this repository that carry no
`protected $table` demonstrate it — `User` → `users`, `Menu` → `menus`,
`MenuDisk` → `menu_disks`, `MenuSet` → `menu_sets`, `MagazineIssue` →
`magazine_issues`, `GameVote` → `game_votes`, `SndhArchive` → `sndh_archives`.

So plural tables and singular keys are one rule, not two, and it is the rule the
foreign key campaign already adopted as its Decision 1 ("singularised: `users` →
`user_id`"). `articles` + `article_id` satisfies both ends of it with no
migration on the key side at all.

The repository is genuinely mixed — `game`, `dump`, `media`, `crew`, `website`
are singular, all legacy — so this is siding with Laravel and with the newer half
of the schema, not with a clean sweep. All four target names are free:
`SHOW TABLES` matches none of `articles`, `interviews`, `reviews`, `screenshots`.

## What the tables actually are

Measured with `SHOW CREATE TABLE`. This is the whole subject of the plan and it
is smaller than the names suggest:

| Table | Columns |
|---|---|
| `article_main` | `id`, `user_id`, `article_type_id`, `draft` |
| `article_text` | `id`, `article_id`, `article_title`, `article_text`, `article_date`, `article_intro` |
| `interview_main` | `id`, `user_id`, `individual_id`, `draft` |
| `interview_text` | `id`, `interview_id`, `interview_text`, `interview_date`, `interview_intro`, `interview_chapters` |
| `review_main` | `id`, `user_id`, `review_text`, `review_date`, `review_edit`, `draft` |
| `review_score` | `id`, `review_id`, `review_graphics`, `review_sound`, `review_gameplay`, `review_overall` |
| `individuals` | `id`, `ind_name` |
| `individual_text` | `id`, `individual_id`, `ind_profile`, `ind_imgext`, `ind_email` |
| `pub_dev` | `id`, `pub_dev_name` |
| `pub_dev_text` | **`pub_dev_text`**, `pub_dev_id`, `pub_dev_profile`, `pub_dev_imgext` |
| `screenshot_main` | `id`, `imgext` |

Four things fall straight out of that table.

**`article_main`, `interview_main`, `individuals` and `pub_dev` carry no content
at all.** Two to four columns each, mostly foreign keys. Everything a reader sees
is in the other table. That is why 79 call sites in this repository are spelled
`->texts->first()->something` and another 60 reach through `->text`.

**`review_main` is not the same shape.** It already holds its own text and date;
its partner is `review_score`, four integers.

**`pub_dev_text`'s primary key is a column called `pub_dev_text`** — the table
name, as the key name. It is one of the 36 legacy prefixed primary keys the
primary-key campaign recorded as follow-up, and `PublisherDeveloperText` carries
a `// FIXME: Should be named with _id suffix` about it. Phase 6 deletes the table
and retires that follow-up rather than executing it. The two campaigns cancel
here instead of colliding, which is worth saying because everywhere else they
have had to be ordered against each other.

**`screenshot_main` has no partner and is merged with nothing.** Two columns,
and nothing to merge them with. It is in scope for the Phase 4 rename only, and
only because it is the fourth `_main` name.

### The three `*_main` pairs are strictly 1:1, with no orphans

Re-measured 2026-08-24, because the foreign key plan's reconnaissance had one of
these wrong once already — and, as it turns out, had another wrong still:

```sql
SELECT 'article mains w/o text', COUNT(*) FROM article_main m
  LEFT JOIN article_text t ON t.article_id = m.id WHERE t.id IS NULL;
SELECT 'article mains w/ >1 text', COUNT(*) FROM
  (SELECT article_id FROM article_text GROUP BY article_id HAVING COUNT(*) > 1) x;
-- and the same two for interview_text.interview_id and review_score.review_id
```

| Pair | Parents | Children | Parents with no child | Parents with >1 child | Orphaned children |
|---|---|---|---|---|---|
| `article_main` / `article_text` | 5 | 5 | 0 | 0 | 0 |
| `interview_main` / `interview_text` | 81 | 81 | 0 | 0 | 0 |
| `review_main` / `review_score` | 126 | 126 | 0 | 0 | 0 |

Interviews were 80/80 when the foreign key plan measured them and are 81/81 now;
the shape has not changed. Nothing in the *schema* enforces any of this — each
child has its own surrogate `id` and a plain `KEY` on the parent key, not a
unique one — so the migration asserts it at run time rather than assuming it.

The two pairs added by decision 4 are a different shape entirely and are measured
in Phase 5.

### Nothing references the child tables' `id`

Checked against `information_schema.KEY_COLUMN_USAGE`: sixteen foreign keys
point at the four `_main` tables, and **none** points at `article_text.id`,
`interview_text.id`, `review_score.id`, `individual_text.id` or
`pub_dev_text.pub_dev_text`. Those five primary keys are pure surrogates, read by
nothing.

That is what makes the merge cheap, and it is what makes the rollback lossless —
see "The `down()` is honest, and where it drifts".

## What the merge buys

Not tidiness. Six concrete things, in rough order of size.

**1. 139 call sites collapse.** `grep -rn '\->texts' app resources database tests`
returns 79 lines (49 article, 30 interview) across 14 Blade files and 16 PHP
files; the `->text` singular of Phases 5 and 6 is 60 more — 26 in `app`, 17 in
`resources`, 17 in `tests`, counting relation reads only and not the
`$request->text` and `$table->text()` false positives the raw grep picks up —
split across both phases (`card_gameinfo.blade.php` reaches through both). Every one of them
reaches through a relation to a row that is either always there or never has more
than one. After the merge they are `$x->column`. The review side is another 25-30 sites
through `->score`.

**2. Nine joins disappear** — `FeedHelper` (×2), the public `ArticleController`
(×2), `InterviewController` (×2), one in each of `ArticlesTable` and
`InterviewsTable`, and `GameIndividualsTable`'s `leftJoin('individual_text')`
with the sort that rides on it. That is the whole list: a `join(`/`leftJoin(`
grep against the five child tables returns exactly those nine, and there is no
join to `review_score` anywhere in `app/` — the scores are read through the
`->score` relation, so Phase 3 removes call sites but no joins. Each is a
`select('article_main.*', 'article_text.article_title')` + join pair, which is
precisely the shape `QueryConventionsTest` exists to police — that test came out
of the primary-key campaign because a join plus an unqualified `select()`
hydrates a model from the wrong table's `id` silently. Removing the joins removes
the exposure rather than guarding it.

**3. It fixes a latent bug, verified.** `Article::toFeedItem()` reads
`$this->article_title`, which is not a column on `article_main`. It works only
because its one caller, `FeedHelper`, smuggles the column in through the join.
Called on a plain model it throws:

```
Article THREW Illuminate\Database\Eloquent\MissingAttributeException:
  The attribute [article_title] either does not exist or was not retrieved
  for model [App\Models\Article].
```

That is real output, from booting the application against the dev database and
calling `Article::first()->toFeedItem()`. `Interview::toFeedItem()` does the same
thing correctly (`$this->individual->ind_name`) and does not throw. In production
`preventAccessingMissingAttributes` only logs, so a second caller would ship a
feed entry titled `"Article: "` rather than an error. After the merge
`article_title` is a real column and the trap is gone.

**4. It deletes ~5,250 rows that hold nothing.** 4,237 of 4,528
`individual_text` rows are entirely empty — NULL profile, NULL image extension,
NULL email — and so are 1,009 of 1,185 `pub_dev_text` rows. 94% and 85%. Only 291
individuals and 176 companies have ever had anything written about them. Those
rows exist because the legacy admin created one per parent whether or not there
was anything to put in it. Merging replaces them with NULLs in columns that were
going to be nullable anyway.

**5. It removes the primary-key campaign's blind spot.** That plan's "What the
suites cannot see" records that `article_main` and `article_text` ids move in
lockstep in the fixtures, so a wrong-key hydration returns the right row anyway
and no test can see it. `FactoriesTest::test_related_models_have_distinct_ids_to_prevent_collision`
is the guard that was built for it. Merging the tables removes the class of bug,
and that test with it.

**6. It dissolves the deferred foreign key group instead of deferring it again.**
Sixteen keys were deferred. Three of them — `article_text.article_id`,
`interview_text.interview_id`, `review_score.review_id` — are deleted outright by
this merge. The remaining thirteen become table-correct the moment the tables are
plural (decision 1): `articles` singularises to `article_id`, which is what the
column is already called. The renames the foreign key plan priced at "sixteen
columns, propagating a legacy table name" become **zero columns**.

## Seven hazards

Ordered by how quietly they fail. The first is the only one that can lose data
in production.

### 1. `$fillable` is a silent write in production

`Model::preventSilentlyDiscardingAttributes()` is on **outside** production only
(`AppServiceProvider:94`). In production an assignment to a non-fillable
attribute is dropped without a word. So:

- `Article::$fillable` must gain `article_title`, `article_text`, `article_date`,
  `article_intro`;
- `Interview::$fillable` must gain `interview_text`, `interview_date`,
  `interview_intro`, `interview_chapters`;
- `Review::$fillable` must gain `review_graphics`, `review_sound`,
  `review_gameplay`, `review_overall`;
- `Individual::$fillable` must gain `ind_profile`, `ind_imgext`, `ind_email`;
- `PublisherDeveloper::$fillable` must gain `pub_dev_profile`, `pub_dev_imgext`.

This is the same hazard the foreign key campaign flagged for
`interview_main.ind_id`, and it has the same answer: **the `$fillable` change
ships in the same commit as the migration**, never in a follow-up. The create
paths (`Article::create([...])`, `new Interview([...])`, `new Review([...])`,
`new IndividualText($attrs)` → `$individual->update($attrs)`) are all mass
assignment, so a forgotten entry there is an article saved with an empty body and
no error in the log.

The test environment does throw, so `artisan test` is a real gate on this — but
only for a path a test actually exercises. The controller suites named in each
phase below cover create and update for all five models, which is why they are
the named gates rather than "the suite is green".

### 2. The casts start applying where they did not

`ArticleText::$casts` has `'article_date' => 'datetime:timestamp'` and moves onto
`Article`, which has no `$casts` at all today. `ArticlesTable` reads the date off
the **joined** column, which arrives raw, and compensates:

```php
// app/Livewire/Admin/ArticlesTable.php:40-43
fn ($row) => $row->article_date
    ? Carbon::createFromTimestamp($row->article_date)->toFormattedDateString()
    : '-'
```

After the merge `$row->article_date` is a `Carbon`, and that is where this
hazard earns its place: **it does not throw.** The installed Carbon is 3.11.0,
whose `createFromTimestamp()` is typed `float|int|string`
(`vendor/nesbot/carbon/src/Carbon/Traits/Timestamp.php:29`), not Carbon 2's
`int|float`. A `Carbon` argument is coerced through `__toString()` and
`getIntegerAndDecimalParts()` then sums every number it finds in the stringified
date. Reproduced against this repository's vendor tree:

```
$c = Carbon::createFromTimestamp(1516492800);   // a real article_date
$c->toFormattedDateString();                            // "Jan 21, 2018"
Carbon::createFromTimestamp($c)->toFormattedDateString(); // "Jan 1, 1970"
```

So a forgotten fix here renders **"Jan 1, 1970" in the Date column of every
article**, quietly, with no exception anywhere. The fix is unchanged —
`$row->article_date?->toFormattedDateString() ?? '-'`, which is what
`InterviewsTable:43` already says because it reads through the model — but the
reasoning for it is "silent wrong date", not "TypeError". Anyone re-checking
this hazard by looking for an error will find none and conclude it is not real.

**No gate catches it, and that is the part to plan around.** There is no
`Livewire::test(ArticlesTable::class)` anywhere in `tests/` — grepping
`ArticlesTable|InterviewsTable|ReviewsTable` across `tests/` returns nothing —
and the Playwright admin list assertion is `expectPageRenders()`, which is
status 200, a URL check and an exception-marker scan
(`tests/e2e/support/assertions.js:37-45`), nothing about rendered cells. A 1970
date passes `artisan test`, Playwright and the named Phase 1 gates alike.
So Phase 1 either **looks at the rendered admin article list by hand**, or adds
a `Livewire::test(ArticlesTable::class)` date assertion to
`tests/Feature/Admin/Tables/AdminTablesTest.php`, which already covers
**fourteen** other datatables that way. The second is cheap and is the better
answer, because it is the only one that survives the next person.

Worth knowing before writing it: those fourteen assert on rendered *content* —
ordering, search, presence, in the shape of
`assertSeeInOrder(['Newer submission', 'Older submission'])` — and **not one of
them asserts a rendered date string**. So this assertion is a slightly new shape
even inside that file, which is precisely why it is the one that would catch a
1970 date. `AdminTablesTest` is still the right home for it.

`ReviewsTable:43-47` and `InterviewsTable:41-45` are already safe — both read
through the model or the relation and call `->toFormattedDateString()` on the
Carbon directly — so `ArticlesTable:40-43` is the only site that needs the
change.

Every other site that reads one of the moved date columns needs the same check.
`grep -rn 'article_date\|interview_date' app resources` is the list; the sorts
(`orderByDesc('article_text.article_date')`) lose their table prefix and are
otherwise unaffected, because the cast is a PHP-side thing and SQL never sees it.

### 3. One state stops existing, and one is deliberately preserved

Two `?? new Something()` fallbacks handle a parent whose child row is missing.
Decision 2 splits them, and the split is not arbitrary — the two states have
different support in the code today.

**Reviews: preserved.** `ReviewsController:110` is
`$review->score ?? new ReviewScore()`, and `card_review.blade.php:33` guards the
whole score block with `@isset($review->score)`. The public page handles the
absence, so the absence is a supported state, and decision 2 keeps it: the four
columns land nullable and the guard becomes `@isset($review->review_graphics)`.
`ReviewsControllerTest::test_update_creates_the_score_row_if_it_is_missing`
survives, rewritten to assert that null scores get filled on the next save.

Two consequences worth stating plainly. The four columns move together in every
write path, so guarding on one of them is imprecise in principle and exact in
practice — say so in a comment rather than guarding all four. And because all 126
reviews have a score row, **the merge produces zero NULLs in production**: the
nullability preserves a state only the factory can currently create. That is the
deliberate choice, not an oversight.

**Articles and interviews: not preserved.** `InterviewsController:102` is
`$interview->texts->first() ?? new InterviewText(...)`, which looks like the same
pattern, but the public side does not agree with it.
`interviews/card_interview.blade.php:17` and `interviews/card_list.blade.php:28`
both call `$interview->texts->first()->interview_date->format('F j, Y')` with no
null-safe operator, and `articles/card_article.blade.php:15` does the same. An
interview with no text row already 500s the public site. So the "missing text"
state is a half-guarded accident rather than a supported state, and making the
columns `NOT NULL` makes the schema say what every view already assumes.

That costs one test its subject:
`InterviewsControllerTest::test_update_creates_the_text_row_if_it_is_missing`
(line 141 deletes the text row first). Deleting a test to make a change pass is
normally the wrong move, so say why in the commit — the subject is gone, not the
assertion. **If you would rather keep that state too, the change is to make the
interview and article text columns nullable as well**; it is a one-word
difference in the migration and the rest of the plan is unaffected.

No production page changes either way: all 126 reviews have a score and none is
all-zeros (`SELECT COUNT(*) FROM review_score WHERE review_graphics = 0 AND
review_sound = 0 AND review_gameplay = 0 AND review_overall = 0` → 0), and every
article and interview has its text row.

### 4. `ReviewController::submit()` saves before the scores exist

The public submission path (`ReviewController:94-112`) builds a `Review`, saves
it twice through relations, and only then builds the `ReviewScore`. With the
scores on `review_main` and nullable, the first insert writes NULLs and the
second save fills them — correct, but two writes where one would do. Setting the
four fields before the first `save()` is the tidier rewrite and is
behaviour-neutral. Keep `?? 0` in both write paths: a submitted review with no
scores should still read as zeros, not as "unscored".

### 5. `interview_text` is at 72% of its ceiling — recorded, not acted on

`SELECT MAX(LENGTH(interview_text)) FROM interview_text` → **47,149 bytes**, on
interview 32. The column is `TEXT`, which holds 65,535 **bytes**, not characters,
and the charset is `utf8mb4`. Decision 3 keeps it as `TEXT`.

The reasoning that makes that safe, so it can be re-checked rather than
re-argued: Laravel runs MariaDB in strict mode (`config/database.php:58`), so an
over-long save is a loud `SQLSTATE 22001` and never a silent truncation. The
failure lands on a contributor mid-edit and loses nothing. Re-measure this if
interviews start getting longer; `article_text` and `article_intro` are already
`MEDIUMTEXT`, so the widening has a precedent when it is wanted.

### 6. The historical migrations, and the model guard

303 migrations run in order on every `migrate:fresh`, and thirteen of them name
`article_text`, `interview_text` or `review_score`. All thirteen are dated before
this campaign, so they run against the pre-merge schema and are correct as
written. **No migration added after the merge may name those tables**, and none
of the new ones may use an Eloquent model —
`MigrationModelsTest::test_no_migration_uses_eloquent_models` enforces the second
and would fail the build. The template below uses `DB::table()` and `Schema::`
only.

`MigrationModelsTest`'s own docblock uses `ArticleText::each()` as its worked
example. Update the comment when `ArticleText` stops existing; the test does not
reference the class.

### 7. One test tests a migration through a table this campaign drops

`NormaliseBlankProfilesTest` re-runs `2026_08_09_100000_normalise_blank_profiles`
by hand — `$migration->up()` — over rows it inserts directly into
`individual_text` and `pub_dev_text`. The *migration* is fine: it runs in date
order during `migrate:fresh`, long before Phase 5, and finds its tables. The
*test* is not: it runs after a full `migrate:fresh`, when those tables are gone,
and all four of its cases fail at the insert.

It cannot be repointed at the merged columns, because the migration it re-runs is
frozen history. So it goes, and its subject goes with it — the blank-profile
*behaviour* it was protecting is separately covered by `BlankProfileTest`, which
drives the admin controllers rather than the migration. Check that before
deleting, not after.

## The migration, and what it was tested against

One template, five instances — Phase 4 renames rather than merges and has its
own shape. **The template is a starting point, not a stencil**: the review of
this plan found two places where copying it verbatim is wrong — Phase 5's
backfill subquery, which must be `ORDER BY ... LIMIT 1` because that pair has
expected duplicates, and the `down()` nullability of Phases **2**, 3, 5 and 6. Both are
called out where they belong, below and in the phases. This is the article one, executed on 2026-08-24
against the dev MariaDB with its five real rows, then rolled back, then run
again; and separately through a complete `migrate` on a fresh SQLite file.

```php
public function up(): void
{
    $duplicates = DB::table('article_text')
        ->select('article_id')->groupBy('article_id')
        ->havingRaw('COUNT(*) > 1')->count();

    if ($duplicates > 0) {
        throw new RuntimeException("article_text holds {$duplicates} articles with more than one row.");
    }

    $expected = DB::table('article_text')->count();

    Schema::table('article_main', function (Blueprint $t) {
        $t->mediumText('article_title')->nullable()->after('article_type_id');
        $t->mediumText('article_text')->nullable()->after('article_title');
        $t->integer('article_date')->nullable()->after('article_text');
        $t->mediumText('article_intro')->nullable()->after('article_date');
    });

    foreach (['article_title', 'article_text', 'article_date', 'article_intro'] as $column) {
        DB::table('article_main')->update([
            $column => DB::raw("(SELECT t.`{$column}` FROM `article_text` t WHERE t.`article_id` = `article_main`.`id`)"),
        ]);
    }

    $moved = DB::table('article_main')->whereNotNull('article_title')->count();

    if ($moved !== $expected) {
        throw new RuntimeException("Backfilled {$moved} of {$expected} article_text rows; refusing to drop the table.");
    }

    Schema::table('article_main', function (Blueprint $t) {
        $t->mediumText('article_title')->nullable(false)->change();
        $t->mediumText('article_text')->nullable(false)->change();
        $t->integer('article_date')->nullable(false)->change();
        $t->mediumText('article_intro')->nullable(false)->change();
    });

    Schema::drop('article_text');
}
```

Six things about it are worth stating, because four of them were checked rather
than assumed.

**It needs no driver branch.** The previous campaign's migrations all carry a
`if (DB::connection()->getDriverName() === 'sqlite')` arm, because MariaDB leaves
indexes and constraints named for the old column and SQLite rebuilds the table.
Nothing here renames a column, so nothing here has that problem. The one
statement that looked driver-specific — the correlated subquery, backticks and
all — runs on both. Verified on SQLite with three rows deliberately inserted out
of order, which returned `1 => one, 2 => two, 3 => three`; and on MariaDB against
the real five articles, whose titles came through intact.

**`->after()` is honoured on MariaDB and ignored on SQLite**, which is the
correct outcome in both cases. The MariaDB result puts the four columns between
`article_type_id` and `draft`.

**Nullable, then backfill, then `nullable(false)->change()`** rather than
creating the columns `NOT NULL DEFAULT ''`. It costs one extra `ALTER` and buys
an exact reproduction of the child table's nullability — `article_text` had no
defaults and neither does the merged table. Laravel 11 does this natively, with
no `doctrine/dbal`; on SQLite it rebuilds the table and the two foreign keys on
`article_main` survive the rebuild, which was checked in the resulting
`sqlite_master` row.

**The `change()` doubles as an integrity assertion, but only where the columns
end up `NOT NULL`, and only because strict mode is on.** If any article had no
text row, the correlated subquery leaves NULL and the `nullable(false)` ALTER
aborts before the `DROP`. Probed on a scratch InnoDB table under the exact
`sql_mode` Laravel sets from `config/database.php:58`:

```
ERROR 1265 (01000) at line 5: Data truncated for column 'v' at row 1
```

Two things follow. The message is about as far from "an article is missing its
text" as a message can get. And the guard is `STRICT_TRANS_TABLES`, not the
schema: with strict mode off MariaDB converts the NULLs to empty strings and the
migration *succeeds*, having quietly emptied every article it could not match.
`'strict' => true` is set for both connections and nothing in this campaign
should touch it.

**Which is why the explicit count is there and not optional.** Three of the five
merges — reviews under decision 2, and both pairs in Phases 5 and 6 — land
nullable columns and therefore get no `change()` step and no free assertion at
all. The `$expected` / `$moved` comparison is what carries them, and it says in
one sentence what error 1265 says in none. Keep it in every merge, including the
two that do not need it, so the five migrations read the same.

**`Schema::drop()` is the last statement**, after the data is provably in its new
home. MariaDB will not roll a DDL statement back, so the ordering is the
protection: everything that can fail, fails before the drop.

### The `down()` is honest, and where it drifts

```php
public function down(): void
{
    Schema::create('article_text', function (Blueprint $t) {
        $t->integer('id', true);           // not increments(): see below
        $t->integer('article_id');
        $t->mediumText('article_title');
        $t->mediumText('article_text');
        $t->integer('article_date');
        $t->mediumText('article_intro');
        $t->foreign('article_id')->references('id')->on('article_main')->cascadeOnDelete();
    });

    DB::table('article_text')->insertUsing(
        ['article_id', 'article_title', 'article_text', 'article_date', 'article_intro'],
        DB::table('article_main')->select('id', 'article_title', 'article_text', 'article_date', 'article_intro')
    );

    Schema::table('article_main', function (Blueprint $t) {
        $t->dropColumn(['article_title', 'article_text', 'article_date', 'article_intro']);
    });
}
```

Executed: `migrate:rollback --step=1` brought back all five rows with their
titles, the `article_id` values and the `ON DELETE CASCADE` constraint intact.
The rollback is **lossless in every sense that matters**, because the child table
is a pure projection of the parent and nothing anywhere references its `id`.

The drift: the draft above used `$t->increments('id')`, and Laravel's
`increments()` is `INT UNSIGNED` while the legacy column is `INT(11)` signed. The
rollback produced `int(10) unsigned` where production has `int(11)`. Harmless —
five rows, ids 1 to 5 — but it is a schema difference introduced by a rollback,
which is exactly the kind of thing that is invisible until it is not.
`$t->integer('id', true)` reproduces the original. Worth doing because it is one
word, and worth writing down because the next person to write a `down()` that
recreates a legacy table will reach for `increments()` too.

One more counter drifts, and it is worth naming next to the signedness one so
that "the rollback reproduces production" is not overclaimed. The recreated
`article_text` is column-for-column identical to production, but its
`AUTO_INCREMENT` is not: `SHOW CREATE TABLE` reads `AUTO_INCREMENT=8` where
production has `6`. Reproduced in isolation on this MariaDB — an
`INSERT ... SELECT` of five rows into an empty InnoDB table leaves the counter
at 8, because under `innodb_autoinc_lock_mode=1` a bulk insert reserves values
in powers of two and does not give the surplus back. Entirely harmless here —
the child `id` is a pure surrogate that nothing references, so the next insert
gets 8 instead of 6 — but the claim is "column-for-column identical", not
"byte-identical", and the same will be true of every `down()` in this campaign.

**The larger trap in the template's `down()` is nullability, and it does not
travel.** The `down()` above declares its columns without `->nullable()`, which
is Laravel's `NOT NULL` default. For articles that happens to reproduce
production — all four columns have been `NOT NULL` since `2024_03_24`. It does
not follow for any of the other four merges, and a `down()` copied from the
template would be wrong in three different ways:

- **Phase 2.** `interview_intro` and `interview_chapters` are
  `mediumtext DEFAULT NULL` in production, and **one interview has a NULL
  `interview_chapters`** (re-measured). A template `down()` declares all four
  columns `NOT NULL`, and its `insertUsing` then aborts under strict mode on
  that single interview. **Phase 2's `down()` needs `->nullable()` on
  `interview_intro` and `interview_chapters`**, and `NOT NULL` on
  `interview_text` and `interview_date`, which the template happens to get
  right. It is the same defect as Phases 5 and 6 and it is easy to miss because
  Phase 2 looks like a copy of Phase 1.
- **Phases 5 and 6.** Every original child column was created nullable
  (`2020_10_17_161643_create_individual_text_table:19-21`,
  `..._create_pub_dev_text_table:19-20`), and the merged columns are nullable by
  design. A `NOT NULL` `down()` fails the `insertUsing` under strict mode for
  every parent with a NULL in **any** of its merged columns, and re-measuring
  that correctly is what makes the size of it clear: **all 5,405 individuals**
  and **1,374 of 1,387 companies**. Not one individual in production has
  `ind_profile`, `ind_imgext` and `ind_email` all non-NULL — 213 have a profile
  and nothing else, 7 have a profile and an image but no email — so the
  statement is not "most rows fail", it is that a `NOT NULL` `down()` for Phase
  5 **cannot insert a single row** and aborts on the first one. Companies are
  the same shape: only 13 carry both a profile and a logo, and 26 have a profile
  with no logo. **Both `down()`s must declare their columns `->nullable()`.**

  The distributions, since they are the evidence and they are the thing that is
  easy to assume away:

  | `individual_text` | rows |
  |---|---|
  | profile, image and email all non-NULL | **0** |
  | profile only | 213 |
  | profile + image, no email | 7 |
  | no profile (image and/or email only, or entirely empty) | 4,308 |

  | `pub_dev_text` | rows |
  |---|---|
  | profile + logo | 13 |
  | profile, no logo | 26 |
  | no profile | 1,146 |

  Two earlier figures for this were wrong and are worth naming so they are not
  reinstated. "891 and 202" is the count of parents with *no child row* — a
  different and much smaller set, and the wrong one for this argument.
  "5,185 and 1,348" is the count of parents with a NULL `ind_profile` /
  `pub_dev_profile` specifically, which silently assumes a parent holding a
  profile holds everything else too; it excludes exactly the 220 individuals and
  26 companies that have a profile and a NULL somewhere else, and those are
  precisely the rows that disprove the assumption.
- **Phase 3.** The reverse. `2025_12_30_113644_review_constraints:50-61` made the
  four score columns `INT NOT NULL`, but decision 2 lands them nullable on
  `review_main`. A faithful `NOT NULL` `down()` fails on any review with NULL
  scores — a state only the factory can create today, which the plan records
  under hazard 3 — while a nullable `down()` drifts from the production schema.
  Either is defensible; **take the decision in the migration and say which in its
  docblock**, alongside the row-count drift below.

**Phases 5 and 6 have a second drift, and the shape of their `down()` has to be
chosen rather than inherited.** Two shapes are available and they drift in
opposite directions:

| `down()` shape | Rows recreated (individuals) | Drift from production's 4,528 |
|---|---|---|
| Unfiltered `insertUsing` — the template — one row per parent | 5,405 | 877 extra all-NULL rows; the 14 duplicate pairs collapse to one |
| Filtered to parents with any non-NULL column | 291 | 4,237 empty rows missing |

**Take the unfiltered one**, for three reasons: it is the template shape, so the
five migrations keep reading the same; "one row per parent" is what the legacy
admin actually did, so the extra rows are the same kind of row production is
already full of; and nothing reads an empty child row —
`AdminStatisticsHelper::countWithText()` already counts only non-empty values,
so no figure on the site moves either way. Note that `->nullable()` is required
under *both* shapes: 71 of the 291 individuals with any data have a NULL
`ind_profile` alongside a non-NULL image or email, so even the filtered insert
writes NULLs.

Whichever is chosen, the drift is not recoverable by rolling forward again, so
say it in the migration's docblock alongside the Phase 3 nullability choice.

Nobody should have to discover any of this in an incident, so: **take a dump
before the deploy.** `deploy.sh` does not take one — it runs `artisan down`,
rsyncs, runs `artisan migrate --force`, and comes back up
(`.github/workflows/deploy.sh:88-111`). The site is down for the whole window, so
there is no interval where new code meets old schema or old code meets new; that
part is already safe. But `DROP TABLE` is not `RENAME COLUMN`, and the previous
two campaigns' "revert over SSH first, the revert commit second" is worth having
a dump behind for the first time.

Singular "the deploy" is deliberate, and it is the one thing the branch layout
below changes about the risk. See "How the six phases reach production".

## The phases

**Six commits on one branch**, in this order — not six pull requests. Phases 1,
2, 3, 5 and 6 are independent of each other and could be reordered; Phase 4
depends on 1-3.

One commit per phase is a real constraint, not bookkeeping, because of hazard 1:
a phase's migration, its `$fillable` change, its model and call-site rewrites and
its test edits **must be in the same commit**. Splitting them leaves a commit in
the history where the schema has moved and mass assignment silently drops the
new columns — the exact production-only failure hazard 1 describes. Each commit
should be independently green, so that `git bisect` over the branch means
something.

Every phase holds the same gates, run before its commit:

- `artisan test` green;
- the Playwright suite green — `public/articles.spec.js`, `public/interviews.spec.js`,
  `public/reviews.spec.js`, `public/games.spec.js`, `public-write/content.spec.js`,
  `public-write/reviews.spec.js`, `admin/content.spec.js`, `admin/games.spec.js`,
  `admin-write/content.spec.js` and `admin-write/games-reference.spec.js` are the
  specs that matter and **none of them needs editing**, because they drive the
  site through HTTP and never name a table. They are the regression net for the
  139 rewritten call sites;
- `artisan al:audit-relationship-keys` — see the note below;
- `migrate`, `migrate:rollback --step=1`, `migrate` again on MariaDB **with the
  production dump loaded**, not on an empty database — the whole point is the
  backfill. `--step=1` is right *here*, while you are developing one phase and
  its migration is the only pending one; it is the wrong command in production,
  for the reason below;
- `migrate:fresh` on the e2e database, then `E2ESeeder`.

The audit gate is not uniform, and the difference matters. The relations Phases
1-3 delete — `Article::texts()`, `Interview::texts()`, `Review::score()` — and
the one Phase 5 deletes, `Individual::text()`, are all in the **clean** 132, so
those four phases move the totals (158 → 154, 132 → 128) and leave the divergent
set untouched. `RelationshipKeyConventionsTest` passes without edits; if it
fails, something else moved.

**Phase 6 is the exception.** `PublisherDeveloper::text()` is one of the declined
26 — `actual=pub_dev_id, convention=publisher_developer_id` — and
`RelationshipKeyConventionsTest:61` names it explicitly with its reason. Deleting
the relation means deleting that line, and Phase 6 is the only phase in this
campaign that legitimately changes the divergent set. Expect 26 → 25 there and
nowhere else.

### How the six phases reach production

All six commits land on one branch, so they merge to `development` together and
`artisan migrate --force` runs all six migrations in **one deploy**. Three
consequences follow, and the third is a constraint on how the files are named.

**The campaign is no longer six independently revertible steps.** Six separate
pull requests would have given six deploys, each with its own dump and its own
"revert just this one". One branch gives one deploy and one revert. That is the
trade-off of the chosen layout, and it is acceptable here — the phases are
gated on the same suite and the same production dump, and the site is down for
the whole deploy window either way — but it should be a decision rather than a
surprise. **Take the dump immediately before the merge to `development`, not
before each commit.**

**`migrate:rollback --step=1` is the wrong revert command, and a bare
`migrate:rollback` is the right one.** `Migrator::runPending()` calls
`getNextBatchNumber()` once per `migrate` invocation
(`vendor/.../Migrations/Migrator.php:197`), so all six migrations share one
batch. `getMigrationsForRollback()` with no options falls through to
`getLast()`, which is "every migration in the last batch"
(`.../Migrator.php:285-296`, `DatabaseMigrationRepository::getLast()`). So:

| command | undoes |
|---|---|
| `migrate:rollback` | **all six phases** — the whole batch |
| `migrate:rollback --step=1` | Phase 6 only, leaving 1-5 applied against reverted code |
| `migrate:rollback --step=6` | all six, the long way round |

The previous two campaigns' habit of reaching for `--step=1` is muscle memory
from one-migration deploys and would do the worst possible thing here: revert
the code to pre-campaign while five of the six schema changes stay applied.
**Write the revert command down where whoever deploys will see it** — the merge
commit message is the natural place — so nobody has to derive it at 2am.

**The migration timestamps must be in phase order, and that is load-bearing.**
`getLast()` returns the batch `orderBy('migration', 'desc')`, so a batch rollback
runs `down()` in reverse *filename* order — 6, 5, 4, 3, 2, 1. That ordering is
exactly what Phases 1-3 need, because Phase 4 renames `article_main` to
`articles` and Phase 1's `down()` recreates `article_text` with a foreign key
naming `article_main`. Phase 4's `down()` must rename the tables back *before*
Phase 1's `down()` runs, and reverse-filename order delivers that for free —
**but only if the six files sort in phase order.** All six are authored on the
same branch on the same day, so they will differ only in the time portion of the
timestamp, which makes this easy to get wrong and invisible when it is wrong:
`up()` would still work, and only a rollback would fail. Number them
deliberately, verify with `ls database/migrations | tail -6`, and treat that
listing as part of the Phase 4 gate.

### Phase 1 — articles

The largest of the three `*_main` merges by call sites and the smallest by rows.

Schema: four columns onto `article_main`, `NOT NULL`, `article_text` dropped.

Code: `ArticleText` deleted; `Article::texts()` deleted, `$fillable` extended and
a `$casts` added — `Article` has none today and inherits
`'article_date' => 'datetime:timestamp'` from the model it absorbs;
`ArticleFactory` folds its `afterCreating` into `definition()` and `titled()`
becomes a plain state; 49 `->texts->first()` sites rewritten across `FeedHelper`,
`AdminStatisticsHelper`, both `ArticleController`s, `CommentController`,
`Comment::getTargetAttribute()`, `ArticlesTable` and nine Blade files;
`E2ESeeder::seedContent()` loses its `article_text` insert.

Watch: `ArticlesTable:40-43` (hazard 2 — and note that nothing in either suite
catches a miss there, so this phase adds the `Livewire::test(ArticlesTable::class)`
date assertion described under that hazard), `Article::toFeedItem()` (which
starts working properly), and `FactoriesTest:369`, deleted with a commit message
explaining that its subject no longer exists.

Named gates: `tests/Feature/Admin/Articles/ArticleControllerTest.php` (17 tests)
and `tests/Feature/Public/ContentPagesTest.php` (27), plus the new
`AdminTablesTest` case above — the only gate this phase does not inherit.

### Phase 2 — interviews

The same shape, 30 call sites. `interview_text` and `interview_date` land
`NOT NULL`; `interview_intro` and `interview_chapters` are nullable today and
stay nullable — one interview has a NULL `interview_chapters` and none has a NULL
intro, so tightening either would be a change of meaning, not a tidy-up.
`interview_text` stays `TEXT` (decision 3).

Code, in the same shape as Phase 1: `InterviewText` deleted;
`Interview::texts()` deleted, `$fillable` extended, `$casts` gaining
`'interview_date' => 'datetime:timestamp'`; `InterviewFactory` folds its
`afterCreating` into `definition()` and `withChapters()` — which today reaches
through `texts()->first()->update()` — becomes a plain state.

Watch: `InterviewsController::update`'s `?? new InterviewText(...)` and the test
that covers it (hazard 3) — this is the phase where the "missing text row" state
is deliberately given up. And **the `down()`, which is not a copy of Phase 1's**:
`interview_intro` and `interview_chapters` must be declared `->nullable()` there
or the `insertUsing` aborts under strict mode on the one interview with a NULL
`interview_chapters`. See the nullability list under "The `down()` is honest".

Named gate: `tests/Feature/Admin/Interviews/InterviewsControllerTest.php` (13
tests).

### Phase 3 — reviews

Different from the other two: four integers, not four blobs, and `review_main`
already holds its own text.

Schema: `review_graphics`, `review_sound`, `review_gameplay`, `review_overall`
onto `review_main` **nullable** (decision 2); `review_score` dropped. No
`change()` step, so the explicit `$expected`/`$moved` count is the only
integrity assertion this migration has. The `down()` has to choose between
reproducing the production `NOT NULL` and matching the new nullability — see the
nullability caveat under "The `down()` is honest" — and record the choice in its
docblock.

Code: `ReviewScore` deleted; `Review::score()` deleted, `$fillable` extended;
25 sites across both `ReviewsController`s, `ReviewFactory::scored()`,
`card_review.blade.php` and the admin edit form.

Watch: `@isset($review->score)` → `@isset($review->review_graphics)` with a
comment saying why one column stands for four, and `ReviewController::submit()`'s
save order (hazards 3 and 4).

Named gates: `tests/Feature/Admin/Reviews/ReviewsControllerTest.php` (15 tests)
and `tests/Feature/Public/ReviewPagesTest.php` (17).

### Phase 4 — the four table renames

`article_main` → `articles`, `interview_main` → `interviews`, `review_main` →
`reviews`, `screenshot_main` → `screenshots` (decision 1).

`Schema::rename()` is one line per table and was tested on `article_main`. Two
findings from that test, both checked against `SHOW CREATE TABLE` afterwards:

**The child tables look after themselves.** InnoDB rewrites the referenced table
name in every foreign key that points at the renamed table.
`article_user_comments` and `screenshot_article` came out reading
``REFERENCES `articles` (`id`)`` with no intervention, and their own constraint
names — which name the *child* — are correctly unchanged.

**The renamed table does not.** `articles` kept `article_main_user_id_foreign`
and `article_main_article_type_id_foreign`, on both the index and the constraint.
That is cosmetic until a later migration writes `dropForeign(['user_id'])` on
`articles`, at which point Laravel derives `articles_user_id_foreign`, does not
find it, and fails with SQLSTATE 42000 / 1091 — the identical trap the foreign
key campaign documented for renamed columns, one level up. So Phase 4 needs the
same information_schema-driven rename of the table's own indexes and constraints
that `2026_08_23_200500_individual_foreign_keys.php` uses, not a bare
`Schema::rename()`.

Five such names to fix, plus two legacy indexes a rename makes no worse and which
could be tidied in the same breath:

| Table | Its own indexes and constraints |
|---|---|
| `article_main` | `article_main_user_id_foreign`, `article_main_article_type_id_foreign` (index + constraint each) |
| `interview_main` | `interview_main_individual_id_foreign`, `interview_main_user_id_foreign` (constraint), plus a legacy index literally called `user_id` |
| `review_main` | `review_main_user_id_foreign`, plus a legacy index called `user_id` |
| `screenshot_main` | none — but a redundant legacy index called `screenshot_id` sitting on the primary key column |

Code: four `protected $table` lines disappear, from `Article`, `Interview`,
`Review` and `Screenshot`, and are not replaced — plural tables are exactly what
Eloquent derives. The rest is the table name written out longhand, and
`grep -rn 'article_main\|interview_main\|review_main\|screenshot_main' app
resources database tests` is the list — **48 lines today** outside the historical
migrations (124 including them, and those must not be touched), of which Phases
1-3 delete roughly a third before Phase 4 starts. What is left after them is
roughly: eight `DB::table()` counts in `AdminStatisticsHelper` plus one in
`StatisticsHelper`; the surviving qualified selects and joins in `ReviewsTable`,
`InterviewsTable` and `ArticlesTable`; eight `insert()` calls in `E2ESeeder`; two
in `StatisticsTest`; and the prose that mentions a `_main` or text table by
name — three PHP comments (`GameScreenshotsController:68`,
`GameSubmissionController:100`, `Game.php:31`), one in
`tests/e2e/public-write/games.spec.js:154`, one in
`tests/e2e/admin-write/games-reference.spec.js:57` (which names
`individual_text.ind_imgext`, so Phase 5 touches it rather than Phase 4), and
**three** mentions in `tests/e2e/README.md` — line 247 (`screenshot_main`), line
414 (`review_main`) and line 422 (`screenshot_main`). All of them are comments,
so no spec logic changes and "none of them needs editing" still holds; the list
is short by two in earlier drafts, which is precisely why the instruction is to
**re-run the grep after Phase 3 rather than working from this list**.

**The historical migrations keep the old names and must not be touched.** Every
migration that exists today runs before the rename, against the schema of its
day. Only a migration dated after Phase 4 is bound by the new names.

The relationship audit is unaffected, because Eloquent derives keys from class
and method names, never from table names.

And this is the phase that closes the foreign key plan's Group 2: after it,
thirteen columns named `article_id`, `interview_id`, `review_id` and
`screenshot_id` are table-correct under the campaign's own rule, with no
migration and no `article_main_id` anywhere.

### Phase 5 — individuals

Added by decision 4, and the phase with the surprise in it.

**The pair is not 1:0..1. It is 1:0..n, and the foreign key plan said
otherwise.** Measured 2026-08-24:

| | |
|---|---|
| `individuals` | 5,405 |
| `individual_text` rows | 4,528 |
| rows with a NULL parent | 0 |
| individuals with no text row | 891 |
| **individuals with more than one text row** | **14** |
| `individual_text` rows that are entirely empty | 4,237 (94%) |

The model already suspected it. `Individual::text()` reads:

```php
public function text()
{
    // FIXME: The DB structure actually allows many
    return $this->hasOne(IndividualText::class);
}
```

It allows many and it *has* many, fourteen times over. The merge resolves the
FIXME by making the schema agree with the `hasOne`.

**The fourteen are harmless, and that had to be checked rather than assumed.**
All 28 rows involved — both rows for each of the fourteen individuals — have
NULL `ind_profile`, NULL `ind_imgext` and NULL `ind_email`:

```sql
SELECT t.individual_id, i.ind_name, COUNT(*) n,
       COUNT(t.ind_profile) profiles, COUNT(t.ind_imgext) imgs, COUNT(t.ind_email) emails
FROM individual_text t JOIN individuals i ON i.id = t.individual_id
GROUP BY t.individual_id, i.ind_name HAVING COUNT(*) > 1;
```

returns fourteen rows, every one of them `n=2, profiles=0, imgs=0, emails=0`. So
any collapse rule produces the same *answer*, and the merge loses nothing.
**Re-run that query as the first statement of the migration and abort if it
returns a row with a non-zero count** — the duplicate check from the template is
not enough here, because duplicates exist and are expected. What must not exist
is a duplicate carrying data.

**But "any collapse rule" is a statement about the data, not about SQL, and the
template's backfill is not a collapse rule at all.** The template writes a scalar
correlated subquery:

```php
'ind_profile' => DB::raw("(SELECT t.ind_profile FROM individual_text t
    WHERE t.individual_id = `individuals`.`id`)"),
```

A scalar subquery may return at most one row. For each of the fourteen it returns
two, and MariaDB aborts the whole `UPDATE`:

```
ERROR 1242 (21000) at line 1: Subquery returns more than 1 row
```

Reproduced on a scratch copy of the dev database on 2026-08-25 during the review
of this plan. Note what the sequence would be in production: the guard above
**passes** — all twenty-eight rows are empty, which is exactly what it is
checking — so the migration adds the three columns to `individuals` and then dies
in the backfill, with a message that says nothing about duplicates. Nothing is
dropped, so it is recoverable, but it is a failed deploy on the first run.

**Phase 5's backfill therefore needs an explicit single-row rule:**

```sql
(SELECT t.`ind_profile` FROM `individual_text` t
 WHERE t.`individual_id` = `individuals`.`id` ORDER BY t.`id` LIMIT 1)
```

The guard is what makes the `LIMIT` safe rather than arbitrary: with "no
duplicate carries data" already asserted, any row `LIMIT 1` skips is provably
all-NULL and therefore identical to the one it keeps. Keep the guard, add the
`LIMIT` — neither is sufficient alone. Verified on the scratch copy: with
`ORDER BY t.id LIMIT 1` the update touches all 5,405 individuals and leaves 220
non-NULL profiles, the same 220 that
`2026_08_09_100000_normalise_blank_profiles` recorded as "an actual bio".

Phases 1-3 and 6 need no such change — their pairs are strictly 1:1, re-verified
as zero parents with more than one child in all four.

Schema: `ind_profile`, `ind_imgext`, `ind_email` onto `individuals`, all
nullable, keeping their `ind_` prefixes (renaming those is a separate concern —
see "What this plan does not do"). `individual_text` dropped. No `change()` step;
the explicit count is the assertion.

Code: `IndividualText` deleted, and its three accessors move onto `Individual` —
`getFileAttribute()`, `getImageUrlAttribute()`, `getPathAttribute()`. The first
gets simpler on the way: it currently reads
`Helper::filename($this->individual_id, ...)` with a comment explaining why it
cannot use `getKey()`, and on `Individual` it becomes
`Helper::filename($this->getKey(), ...)` with the comment deleted. Deleting it is
more than cosmetic: the comment says "this model's key is `ind_text_id`", and the
primary key campaign renamed that column to `id` in `2026_08_22_192100`, so the
reason it gives is already wrong.
`Individual::getAvatarAttribute()` stops reaching through `$this->text?->file`.
`Individual::text()` deleted. Then the individual half of the 60 `->text` sites,
the largest clusters being `GameIndividualController` (8),
`card_gameinfo.blade.php` (7, shared with Phase 6), the five interview Blade
files (7 between them) and `GameIndividualsTable`'s `leftJoin` plus the sort that
rides on it. `IndividualFactory::withBio()` becomes a plain state.

**Phase 5 forces a decision on `ImportStonishData`.** It imports
`App\Models\IndividualText` and calls `$individual->text()->save($text)` at lines
393-395. That command is a one-off that has already been run and is dead code, so
porting it is wasted work — but leaving it referencing a deleted class is worse.
**Recommendation: delete the command in this phase.**

The recommendation is stronger than "it is dead code", and the stronger reason is
worth having in the commit message: **the command is already broken.** It reads
`$individual->ind_id` at lines 379 and 383, and the primary-key campaign renamed
that column to `id` in `2026_08_22_192000_individuals_primary_key`. It no longer
matches the schema it would import into, independently of this merge. Deleting it
is not scope creep smuggled into a table merge; it is removing code that stopped
working three days before this plan was written.

Named gates: `tests/Feature/Admin/Games/GamePanelsTest.php`,
`tests/Feature/Admin/BlankProfileTest.php`,
`tests/Feature/Public/ResourceControllersTest.php` (which builds
`IndividualText::forceCreate()` fixtures in three places),
`tests/Feature/Public/GamePageTest.php:125`, and
`tests/Feature/Admin/StatisticsTest.php:153-173`
(`test_coverage_ignores_individuals_without_a_bio`), which inserts straight into
`individual_text` with `DB::table()` and so fails at the insert the moment the
table is dropped — the `artisan test` gate catches it either way, but it belongs
on the list. `NormaliseBlankProfilesTest` is deleted here — hazard 7.

### Phase 6 — publishers and developers

The same shape as Phase 5 and better behaved:

| | |
|---|---|
| `pub_dev` | 1,387 |
| `pub_dev_text` rows | 1,185 |
| rows with a NULL parent | 0 |
| companies with no text row | 202 |
| companies with more than one text row | **0** |
| `pub_dev_text` rows that are entirely empty | 1,009 (85%) |

Schema: `pub_dev_profile` and `pub_dev_imgext` onto `pub_dev`, nullable;
`pub_dev_text` dropped. That deletes the legacy primary key column called
`pub_dev_text` and retires one of the primary-key campaign's 36 outstanding
renames without executing it.

Code: `PublisherDeveloperText` deleted, its `getFileAttribute()` moving onto
`PublisherDeveloper` and losing the same comment for the same reason;
`PublisherDeveloper::text()` deleted; `GameCompanyController` (8 sites),
`card_gameinfo.blade.php`, `admin/games/companies/card_edit.blade.php`, and the
`countWithText('pub_dev_text', ...)` calls in `AdminStatisticsHelper`, which
collapse to a plain `whereNotNull` on the merged table.

`RelationshipKeyConventionsTest:61` loses its `PublisherDeveloper::text()`
entry — the one deliberate change to the divergent set in this campaign.
`pub_dev` also carries a redundant legacy index called `pub_dev_id` on its
primary key column, the same artefact `screenshot_main` has; tidy it here or
leave it, but do not discover it later and wonder.

Named gates: `tests/Feature/Admin/Games/GamePanelsTest.php:144`,
`tests/Feature/Admin/BlankProfileTest.php`,
`tests/Feature/Admin/Tables/AdminTablesTest.php:260-263`, and
`FactoriesTest:363`.

## What this plan does not do

- **The date columns.** `article_date`, `interview_date` and `review_date` are
  integer unix timestamps with a `datetime:timestamp` cast, and they stay that
  way. Decision 5. The reasoning worth keeping: converting them reaches
  `orderByDesc`, `AdminStatisticsHelper::contentByYear()`, every
  `Carbon::createFromTimestamp()` call site and the two Livewire sort closures —
  a second blast radius layered on the first, and it would make the rollback stop
  being a pure projection, which is the one property that makes these migrations
  safe to deploy.
- **`pub_dev` and `individuals` do not get renamed.** Phase 4 covers the four
  `_main` names only. `pub_dev` is a legacy abbreviation of a table whose model
  is `PublisherDeveloper`, and renaming it to `publisher_developers` would move
  `pub_dev_id` on four tables (`game_developer`, `game_release`,
  `game_release_distributor`, `pub_dev_text`) across six relations, turning
  `PublisherDeveloper::games()`, `::releases()` and `::text()` from declined into
  clean — a real prize, and a campaign of its own with its own foreign key work.
  Not smuggled in here.
- **The column prefixes.** `articles.article_text`, `individuals.ind_profile`
  and `pub_dev.pub_dev_profile` keep their prefixes after the merge, which is
  redundant on a table already called `articles`. Renaming them is a third
  campaign with no data risk and a large diff, and there is no reason to bundle
  it here.
- **`screenshot_main` gains nothing but a name.** Nothing to merge it with.
