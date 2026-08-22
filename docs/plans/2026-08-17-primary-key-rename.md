# Preparing the primary-key rename

## Status — 2026-08-22

Everything before Phase B has landed on `docs/primary-key-rename-plan`. The
per-phase headings below still read as proposals; this is what is actually
done.

| Phase | State |
|---|---|
| A — `getKey()` pre-pass | **Done.** Models, controllers, helpers, Blade. Two sites deliberately left: `Ajax/GameController:73` and `components/cards/tops.blade:36` read `DB::table()` rows, which are `stdClass`, so `getKey()` there is a fatal error, not a refinement. Both carry a comment saying so. |
| A2 — qualify every select | **Done.** The nine joined queries, plus the other 22 zero-argument `select()` calls. `tests/Feature/QueryConventionsTest.php` now fails on any that comes back. |
| Harness 1 — distinct id ranges | **Done**, and proven: `FactoriesTest::test_related_models_have_distinct_ids_to_prevent_collision` fails without it. The hook cannot be named `afterRefreshingDatabase()` — see the note under that section. |
| Harness 2 — no models in migrations | **Done.** Five migrations rewritten, `MigrationModelsTest` guards it. |
| Harness 3 — MariaDB migration gate | **Done**, as `migrate:fresh` + `migrate:rollback --step=1`. See the note about why not a bare rollback. |
| Harness 4 — pin the wire format | **Done.** All six endpoints assert their id key, and `pickAutocompleteBy` now rejects the string `"undefined"` in the hidden companion field, so every autocomplete spec guards the wire format. See the note below on why an emptiness check was not enough. |
| B — the renames | Not started. |

Run both suites before Phase B work, not just `artisan test`. The five defects
above were all invisible to it.

`artisan test` is 991 green, 18 skipped; Playwright is 319 green across all four
projects. Both were run against the full stack.

**Playwright earned its description as the real net.** It was run for the first
time after all of the above had landed and PHPUnit had been green throughout,
and it found five defects the feature suite could not see:

- `components/cards/tops.blade.php` called `getKey()` on `DB::table()` rows.
  `Call to undefined method stdClass::getKey()` — **`/games` returned 500 for
  every visitor**, as did every page carrying that card.
- `IndividualText` and `PublisherDeveloperText` build an image filename from
  their *parent's* id, but their own keys are `ind_text_id` and `pub_dev_text`.
  Rewritten to `getKey()`, every individual avatar and company logo 404d.
- `E2ESeeder` would not run at all: `user_id` is auto-increment and not
  fillable, so moving `USER_ADMIN_ID` to 101 left the rows at 1..6 and the
  first foreign key insert failed.
- Four seeder constants stayed at `1` while `fixture.js` moved to the new
  ranges, and `fixture.js` gave `magazine` and `magazineIssue` the same id.

The pattern in the first two is the one Phase A's rule exists to prevent, and
worth restating: **the receiver must own the column as its primary key.** Where
it does not, the token is a foreign key and stays. `PublisherDeveloperText` is
called out below as "especially unsafe" for token matching; that turned out to
be exactly right.

Known pre-existing defect, not caused by this work and not fixed by it: a
full-history `migrate:rollback` fails at `2022_01_15_163533_add_news_foreign_keys`
(`SQLSTATE 1830`), and there are likely more behind it.

## Context

36 of the 88 models in `app/Models/` declare a legacy prefixed primary key
(`game.game_id`, `individuals.ind_id`, `screenshot_main.screenshot_id`, …). The
other 52 already use `id`. The goal is to converge on the Laravel convention so
that `$primaryKey`, `setPrimaryKey()` and the explicit local-key arguments on
relationships can all disappear.

At the schema level the count is larger: **62 of the 136 tables have a primary
key that is not `id`.** The 26 without a model — `screenshot_game`,
`review_game`, `game_user_comments`, `theme*`, `tools`, `bug_report*`, … — are
deliberately left alone, because nothing in `app/Models/` names them and the
convention this plan chases is Eloquent's. Two of them can never converge at
all: `crew_menu_set` and `game_sndh` have composite primary keys.

The proposal under assessment: first rewrite the code to read primary keys
through `->getKey()` instead of naming the column, then rename the columns and
only touch each model's `$primaryKey`.

**The approach is sound, but for a different reason than the one that motivated
it, and it covers less ground than it appears to.** This plan records the
assessment, then the work.

### Where the safety argument holds, and where it inverts

`app/Providers/AppServiceProvider.php:78-96` already calls
`Model::preventAccessingMissingAttributes()`. Its docblock says, verbatim, that
it exists so "a column that has been renamed or dropped" does not "evaluate to
NULL, so the page still renders and still returns a 200". After a rename,
`$game->game_id` throws in dev and test — today, with no pre-pass. String column
references in queries fail just as loudly, with an "Unknown column" SQL error.
Grouped queries too: Laravel sets `ONLY_FULL_GROUP_BY` per session through
`'strict' => true`, so MariaDB rejects a `groupBy` naming a column that no
longer exists rather than resolving around it.

**In production none of that is true.** Read `AppServiceProvider.php:80-94`
again: it throws *outside* production. Inside it,
`handleMissingAttributeViolationUsing()` logs a warning and the read returns
`null`, so the page renders 200 with a hole in it. `preventSilentlyDiscardingAttributes()`
is likewise dev-and-test only. The guard was written to make production degrade
rather than break, which is the right default for a live site and exactly the
wrong one for a rename campaign: it hides a half-finished rename in the only
environment that matters.

So the `getKey()` pre-pass **is** a production-safety measure, not merely a
disambiguation aid — an earlier draft of this plan had that backwards. For the
duration of the campaign, consider making production throw as well, or at
minimum alerting on `Accessed missing attribute` in the logs.

### The failure mode that is actually silent

Everything above is about the **old** column name, and the old name does fail
loudly. The **new** one does not. Renaming a legacy key to `id` collides with
every other table in the same query that already has an `id`, and those sites
never mention the old token — so no grep for `game_id` can find them.

Two tables exposing `id` in one result set is not an error. PHP keeps the last:

```
za JOIN zb  ->  $row->id = 99     (za.id = 10, zb.id = 99)
zb JOIN za  ->  $row->id = 10
```

Eloquent then hydrates the model from that array, so `getKey()` returns the
*other* table's key. No exception, no SQL error, no log line. Confirmed against
MariaDB 10.11.18 through the `DB` facade.

Every site below is a bare `select *` across a join where **both** tables are in
this campaign:

| Site | Joined to | Goes wrong when |
|---|---|---|
| `app/Livewire/Admin/ReviewsTable.php:65` `Review::select()` | `review_game`, `game` | `game` renames — last in the order below |
| `app/Livewire/Admin/NewsTable.php:57` `News::query()->…->select()` | `news_image` | both renamed |
| `app/Http/Controllers/ArticleController.php:17,38` `Article::select()` | `article_text` | both renamed |
| `app/Http/Controllers/InterviewController.php:17,28` | `interview_text` | both renamed |
| `app/Helpers/FeedHelper.php:21,27` | `interview_text`, `article_text` | both renamed |
| `app/Livewire/Admin/Games/GameSubmissionsTable.php:77` `GameSubmitInfo::select()` | `game`, `users`, joined **inside sort/filter closures** | only when a user sorts on that column |

`ReviewsTable` is the worst of them: the admin reviews list would carry game ids
as review keys, so every edit and delete link targets the wrong record. And it
only appears once `game` renames, long after the review PR was signed off.
`FeedHelper` is the quietest: wrong GUIDs go out over RSS and get cached by
subscribers' readers, where nothing here can retract them.

There is a loud cousin worth naming, because it is just as grep-invisible:
`Tops.php:40` selects `count(id)` over `pub_dev ⋈ game_release`. Renaming
`pub_dev.pub_dev_id` makes that ambiguous — verified, `ERROR 1052 (23000):
Column 'id' in SELECT is ambiguous`.

The fix pattern is already in the repo, and so is the scar tissue:
`ChangelogTable.php:66` carries the comment *"Qualify the select: sorting on
User joins `users`, which also has a `user_id`"*. `ArticlesTable:62`,
`InterviewsTable:63`, `CommentsTable:69`, `GameIndividualsTable:61` and
`TopGames:32` all qualify correctly. Phase A2 below applies the same treatment
everywhere else, *before* any column moves.

### Why the `getKey()` pass is still worth doing

Disambiguation. The same token means two different things, often on one line:

```php
// app/Http/Controllers/GameController.php:143
->where('game_id', '=', $game->game_id)
```

The string `'game_id'` is a foreign key on `game_votes` and must **not** change.
`$game->game_id` is a primary-key read and must. A find-and-replace over the
token is actively dangerous — roughly 85-90% of the ~1,400 raw occurrences in
the repo are foreign keys on other tables. Converting the primary-key reads to
`getKey()` first leaves behind a corpus where every remaining literal is a
foreign key, and the rename becomes mechanical rather than a judgement call at
each of ~1,400 sites.

The codebase is already half-way there: `getKey()` appears ~890 times, and
`database/factories/` consistently uses `getKey()` for PK reads and column
strings for FK values. This is finishing an established pattern, not
introducing one.

### What the pre-pass does not cover

`getKey()` is a method on a model instance. It cannot express:

| Category | Approx. sites | Notes |
|---|---|---|
| Query-builder string columns | ~41 in `app/` | `where`, `orderBy`, `pluck`, `groupBy`, `value` |
| Raw SQL / explicit joins | ~50 | `Tops.php`, `TopGames.php`, `LatestComments.php`, `AdminStatisticsHelper.php`, `FeedHelper.php` |
| `setPrimaryKey()` in Livewire tables | 20 | `app/Livewire/Admin/` |
| Migration PK declarations | 36 | `database/migrations/`, left as historical |
| `E2ESeeder` forced PK values | ~65 | `database/seeders/E2ESeeder.php` |
| Validation `exists:`/`unique:` | 6 | |
| AJAX JSON keys + `data-autocomplete-id` | ~10 + 19 | wire contract, see below |
| Unqualified selects across a join | 9, in 6 files | the silent class — Phase A2 |

So the pre-pass covers property reads and Blade output — real, but a minority of
the work. It should be presented as "make the rename mechanical", not "make the
rename safe".

### Decisions taken

- **CPANEL is retired — this gate is now clear (2026-08-22).** The legacy admin
  panel wrote to the same database, and every deploy runs
  `artisan migrate --force`, so a rename would have broken it the moment it
  landed. That is no longer a constraint. Worth confirming once before the first
  rename ships rather than taking it on trust: that nothing still holds
  credentials to this database outside this application, and that no cron or
  script left behind by the panel still runs against it. A retired *panel* and a
  retired *set of writers* are not automatically the same thing.
- **The database server is not what gates this.** Development, CI and both
  servers run MariaDB 10.11, which makes each rename migration a one-liner (see
  Phase B step 1). With CPANEL gone, Phase B's only remaining prerequisites are
  Phase A2 and the harness changes.
- **Primary keys only, one table at a time.** Foreign key names stay exactly as
  they are. `game_release.game_id` pointing at `game.id` is already the Laravel
  convention.
- **Pre-pass limited to what `getKey()` genuinely fits** — property reads and
  Blade. Everything else is handled inside each table's rename PR, where the
  context is fresh.
- **Selects get qualified first, in their own pass.** The `id` collision above
  is created by *pairs* of tables, so no per-table PR can be verified against
  it in isolation. Phase A2 removes the whole class up front, as a behavioural
  no-op that is testable today. It is a hard prerequisite for Phase B, not a
  nicety.
- **The unit of change is the join-closure, not the table.** Tables that appear
  together in a query rename in the same PR — which the order below already
  does for `article_main` + `article_text` + `article_comments`. `game` is the
  exception that cannot be clustered, because it joins to nearly everything;
  Phase A2 is what makes `game` safe to do alone.
- **Each rename deploy is a scheduled operation, not a normal push.** See
  "Deploying a rename" below. Migrations do not run in a transaction on any
  driver here, and `deploy.sh` ships code *before* it migrates.

---

## Phase A — the `getKey()` pre-pass (can start now)

Rewrite primary-key **reads on a model instance** to `->getKey()`, across
`app/`, `resources/views/`, `tests/`. Roughly 150 property-access sites and 91
Blade lines.

The rule for each hit: *is the receiver an instance of the model that owns this
column as its PK?* If yes, `getKey()`. If it is any other model, the token is a
foreign key — leave it alone.

Representative targets:

- `app/Models/Screenshot.php:30` — `Helper::filename($this->screenshot_id, ...)`;
  note line 58 in the same file already uses `getKey()`.
- `app/Http/Controllers/Admin/HomeController.php:15` and 15 other sites —
  `Auth::user()->user_id`.
- `app/Http/Controllers/GameVoteController.php:40-41`.
- `app/Livewire/Admin/MenuImport.php:307,310,409` — careful here: the array
  *keys* (`'game_id' => …`) are payload names, only the right-hand side changes.
- `resources/views/components/cards/partial_comment.blade.php` (10 sites),
  `resources/views/games/card_gameinfo.blade.php` (6),
  `resources/views/admin/links/links/card_edit.blade.php` (5).
- `resources/views/admin/reviews/reviews/card_edit.blade.php:180` already mixes
  both styles on one line — a good marker for the intended end state.

**Explicitly out of scope for this phase**, because renaming them later is not
what `getKey()` is for:

- Array keys and JSON payload keys, where the endpoint builds them explicitly.
  `app/Http/Controllers/Ajax/IndividualController.php:35` emits
  `'ind_id' => $individual->ind_id` — only the value changes. The key is read by
  `resources/js/autocomplete.js:83` via
  `feedback.selection.value[el.dataset.autocompleteId]`, driven by 19
  `data-autocomplete-id` attributes in Blade naming a legacy key (7 `game_id`,
  7 `ind_id`, 4 `user_id`, 1 `crew_id`, 1 `pub_dev_id`).

  **This only holds for the two endpoints that build an array literal** —
  `Ajax/IndividualController.php:33-36` and `Admin/Ajax/GameController.php:51-55`.
  The other four serialise models straight to JSON, so their keys *are* the
  column names and the rename silently changes the wire format:
  `Ajax/CrewController.php:13` (`select('crew_name', 'crew_id')`),
  `Ajax/CompanyController.php:13` (bare `select()`),
  `Ajax/GameController.php:19,23`, and
  `Admin/Ajax/UserController.php:13` (`select('userid', 'user_id')`).
  A missing key makes `autocomplete.js:83` assign `undefined` into the hidden
  companion field, which is then submitted. No PHP error, no SQL error, nothing
  in the log. Endpoint and Blade attribute must move in the same PR — see
  Phase B step 8.
- `database/migrations/` — historical migrations stay truthful.

Two latent bugs surfaced during exploration. Fix separately, not in this pass:

- `app/Models/Screenshot.php:75` — `hasMany(ScreenshotReview::class, 'screenshot_review_id')`
  joins `screenshot_review.screenshot_review_id` (the pivot's own PK) to
  `screenshot_main.screenshot_id`. The FK should be `screenshot_id`.
- `app/Models/PublisherDeveloperText.php:11` — PK is `pub_dev_text`, with an
  in-code `// FIXME`. See "Not renameable" below.

## Phase A2 — qualify every select that crosses a join (prerequisite, can start now)

Give every query that joins two tables an explicit, table-qualified select list,
so that no result set can ever carry two columns called `id`. This is a
no-op against today's schema, which is what makes it safe to land now and
verifiable now.

**Qualifying is not the same as narrowing.** The obvious rewrite is wrong
wherever the caller reads a column off the joined table, because `select
('main.*')` stops fetching it:

```php
// before
return Review::select()
    ->leftJoin('review_game', …)
    ->leftJoin('game', …);

// WRONG - ReviewsTable.php:31 renders $row->game_name, which this drops
return Review::select('review_main.*')…

// right: qualify the base table, then name what the caller actually reads
return Review::select('review_main.*', 'game.game_name')
    ->leftJoin('review_game', …)
    ->leftJoin('game', …);
```

`ArticlesTable.php:62` is the existing model for this — it selects
`article_main.*` *plus* `article_text.article_title` and
`article_text.article_date`, for exactly this reason.

This mistake is loud, unlike the collision it protects against: the naive
rewrite was applied to all nine call sites and `artisan test` failed 7 tests
with `The attribute [game_name] … was not retrieved` and
`The attribute [article_title] … was not retrieved`. So the suite is a real
gate here — but only outside production, where the same read logs and returns
null (see "Where the safety argument holds, and where it inverts").

The nine call sites, with the select each one actually needs. Verified: with
these, `artisan test` is 986 green and the Playwright suite 316 green.

| Site | Correct select |
|---|---|
| `ArticleController.php:17,38` | `Article::select('article_main.*', 'article_text.article_title')` |
| `FeedHelper.php:27` (articles) | `Article::select('article_main.*', 'article_text.article_title')` |
| `FeedHelper.php:21` (interviews) | `Interview::select('interview_main.*')` |
| `InterviewController.php:17,28` | `Interview::select('interview_main.*')` |
| `ReviewsTable.php:65` | `Review::select('review_main.*', 'game.game_name')` |
| `NewsTable.php:57` | `->select('news.*')` |
| `LatestComments.php:39` | `Comment::select('comments.*')` |
| `LinkController.php:24` | `Website::select('website.*')` |
| `GameSubmissionsTable.php:77` | `GameSubmitInfo::select('game_submitinfo.*')` |

Only two of the nine need a joined column. The rest reach the joined data
through a relationship or an accessor, which issues its own query and is
therefore unaffected — `NewsTable`'s `$row->news_image` is
`News::getNewsImageAttribute()` going through `$this->image`, and its
`news_image_ext` sort is an `order by`, which works regardless of the select
list. `ChangelogTable` is the same shape and already correct.

`GameSubmissionsTable:77` needs care for a different reason: its joins live in
filter and sort closures, so the qualified select goes on `builder()` while the
closures stay as they are.

Two rules to carry forward afterwards, because this pass only fixes what exists
today:

- A query that joins tables never selects `*` unqualified. `Model::select()` and
  `Model::query()->…->select()` with no arguments are the shapes to watch for.
- Aggregates and order-by clauses over a join name their table too —
  `count(game_release.id)`, not `count(id)`.

### Make the second rule a lint, not a convention

Fixing the nine is a snapshot, and Phase B is a campaign that runs for months —
months during which these exact queries are the ones being edited. The day
somebody adds a join to a query that still selects `*`, the silent class is
back, and by then the plan that explains why it matters is a merged document
nobody re-reads.

Audited: `app/` holds **33** zero-argument `select()` calls, and only the nine
above sit in a query that joins anything. The other 24 are one join away from
being the tenth. Two shapes account for all of them —
`Model::select()` used as a way to start a query, and `->…->select()` at the end
of a chain.

So close the class rather than the instances:

1. Give all 33 an explicit select list, naming the query's own table:
   `Game::select('game.*')`. **Do not reach for `Model::query()` on the sites
   that do not join today** — an earlier draft of this plan called that
   behaviour-identical, and it is not. A zero-argument `select()` leaves the
   builder's `columns` as `[]` rather than `null`, and Rappasoft's
   `DataTableComponent` treats the two differently: it narrows the select until
   the primary key is missing. Measured, not reasoned — the `query()` version
   failed 16 admin table tests with `MissingAttributeException: The attribute
   [game_id] ... was not retrieved for model [App\Models\Game]`. Qualifying
   preserves the builder state and states the rule positively: every query
   names its own table, whether or not it joins today.
2. Add a test that fails on a zero-argument `select()` anywhere under `app/` —
   the same shape as harness change 2's ban on models in migrations, and cheap
   for the same reason: a `grep` over a directory, not a runtime assertion.

That test, had it existed, finds all nine call sites on its own without anybody
knowing the collision exists. It is the only part of this campaign that keeps
working after the campaign ends.

The first rule cannot be linted the same way — a qualified aggregate over a
join is not distinguishable by grep — so it stays a review convention, and
Verification step 4 stays the place it is checked.

Acceptance criterion: this changes no behaviour, so a green `artisan test` and a
green Playwright run are the whole of it. The new arch test is the exception —
it should fail before the 33 are qualified and pass after, which is worth
confirming in that order.

## Phase B — per-table rename (gated on Phase A2 and the harness changes)

One table per PR. Each PR does all of:

1. **Migration**, appended with a current timestamp — do *not* edit the 2020
   `create_*` migrations. Historical migrations must keep building the schema in
   the order it actually happened, or a fresh SQLite test database breaks.

   Follow the existing precedent,
   `database/migrations/2022_07_16_155143_magazines_tables.php`, which renamed
   `magazine.magazine_id` → `id`. Note its comment: *"The renameColumn calls are
   in separate blueprints to be compatible with SQLite for unit tests."*

   A plain `renameColumn()` is enough, including for a parent column with
   inbound foreign keys. Laravel emits native `ALTER TABLE … RENAME COLUMN` on
   MariaDB 10.5.2 and above, which touches the column name and nothing else.

   Verified against 10.11.18, on the real schema, renaming `game.game_id` with
   all **16** of its inbound foreign keys in place: the primary key and
   `AUTO_INCREMENT` survive, and MariaDB rewrites every inbound foreign key
   definition to point at the new name — named and unnamed constraints alike,
   including the self-referential one. The constraints stay live: an orphan
   child insert still fails with 1452 and deleting a referenced parent still
   fails with 1451. So no dropping and re-adding foreign keys around the rename,
   and no need to account for the `references()` clauses aimed at the renamed
   column. `foreign_key_checks` can stay on.

   The server also accepts the rename with an explicit `ALGORITHM=INSTANT`,
   which means it is metadata-only whatever the row count — `game` is no more
   expensive than `trivias`. `renameColumn()` does not emit that clause, but the
   fact that MariaDB *would* accept it is what says a table copy is never on the
   table. The full `migrate:fresh` → `migrate:rollback` round trip was run on
   MariaDB, and the PHPUnit suite on SQLite: SQLite handles `renameColumn` on a
   16-FK parent without complaint, and every stale reference fails loudly
   (`no such column: game.game_id`).

   **Write the `down()`, and test it.** `renameColumn()` back the other way is
   one line, which is exactly why it gets skipped — and it is the only thing
   standing between a bad rename and an unrecoverable production state, because
   the revert commit takes the migration file with it. See "Deploying a rename"
   for the ordering this forces.

   **One `renameColumn()` per migration.** `Grammar::$transactions` is `false`
   in the base class with no override for any driver, so no migration in this
   project runs in a transaction — including on SQLite. A migration that renames
   several columns and fails part-way leaves partial state with nothing to
   unwind it. `deploy.sh` runs under `set -eu`, so the deploy aborts, but the
   new code is already on the server by then.

   After a table renames, **any future migration adding a foreign key to it must
   use `references('id')`**. `database/migrations/` currently holds 242
   `->foreign()` calls modelling the old convention, and they stay truthful
   because they ran before the rename did.

   One trap to respect: guard any raw DDL with
   `if (DB::connection()->getDriverName() !== 'sqlite')`, never `=== 'mysql'`.
   The driver name is `mariadb`, so a `=== 'mysql'` test silently no-ops on
   development *and* CI — which is how the `sndhs` FULLTEXT index once went
   missing. `database/migrations/` holds 38 `!== 'sqlite'` guards and no
   `=== 'mysql'` test.

2. **Model** — delete `protected $primaryKey`. **Check first whether any
   migration drives this model.** A data migration written against the
   pre-rename schema still resolves the model as it is *today*, so dropping
   `$primaryKey` breaks it retroactively:
   `2020_11_08_140622_update_links_in_content.php:94` runs
   `ArticleText::where(…)->each(…)`, `each()` chunks by the model's key, and
   after the rename `migrate:fresh` dies with
   `Unknown column 'article_text.id' in 'ORDER BY'`.

   Seven models are used inside `database/migrations/` — `ArticleText`, `Game`,
   `GameFact`, `InterviewText`, `News`, `Review`, `Sndh` — and six of them are
   in this campaign. Rewrite those call sites to `DB::table()` with an explicit
   `orderBy` on the historical column name, in the same PR.

   `artisan test` cannot see this. That migration is guarded by
   `!== 'sqlite'` at line 47, so PHPUnit skips it wholesale: the suite was
   observed green at 986 passing while `migrate:fresh` on MariaDB was broken.
   Only a MariaDB `migrate:fresh` catches it.

3. **Relationships** — drop the now-redundant local-key argument. Only four
   places pass one:
   - `app/Models/Game.php:125` — `hasMany(Release::class, 'game_id', 'game_id')`
   - `app/Models/GameAka.php:22` — `hasOne(Game::class, 'game_id', 'game_id')`
   - `app/Models/GameSubmitInfo.php:28` — `belongsTo(User::class, 'user_id', 'user_id')`
   - `app/Models/MenuDisk.php:43` — `belongsTo(Individual::class, 'donated_by_individual_id', 'ind_id')`

   The other ~84 relationship arguments name foreign keys and stay.

4. **Livewire** — change `setPrimaryKey('<legacy>')` to `setPrimaryKey('id')`
   in `app/Livewire/Admin/`. It cannot be deleted: Rappasoft's
   `DataTableComponent` throws *"You must set a primary key using setPrimaryKey
   in the configure method"*, which is why `MagazineIssuesTable:17`,
   `GameSeriesTable:15`, `SoftwareTable:18` and `MagazinesTable:15` already call
   it with `'id'`. Also fix any table-qualified references in sort/search join
   closures (`ChangelogTable.php:37,66,109`, `NewsTable.php:58,75`,
   `InterviewsTable.php:64-65,81`) and HTML formatters (`CrewsTable.php:32`).

   This one the suite does catch — `ArticleControllerTest` fails immediately.

5. **Raw SQL** — still the riskiest category, because a wrong edit can change
   join semantics rather than erroring. Table-qualified occurrences
   (`game.game_id`, `individuals.ind_id`, `pub_dev.pub_dev_id`, …) all have to
   change. Key sites, at current line numbers:
   `app/View/Components/Cards/Tops.php:29-32,39-44,50-53,60-62`,
   `LatestComments.php:40` (an *unqualified* `comments_id` in a join ON clause —
   qualify it before deciding),
   `app/Helpers/AdminStatisticsHelper.php:272-274,323-325,342-344`,
   `app/Helpers/FeedHelper.php:20,26`, `Ajax/GameAndSoftwareController.php:19,23`
   (`select('game_id as id', …)` becomes a redundant alias).

   `Cards\TopGames:27-34` is the mild case: it aggregates the votes in a
   `joinSub`, so `game.game_id` appears once, in the join condition.

   `ONLY_FULL_GROUP_BY` works in this plan's favour here. Every legacy primary
   key used as a grouping key is named twice — once as the key, once beside the
   label that depends on it, as in
   `groupBy('pub_dev.pub_dev_id', 'pub_dev.pub_dev_name')`. A half-finished
   rename therefore raises an unknown-column or group-by error rather than
   quietly returning the wrong row.

   That guarantee covers references to the *old* name only. References that
   become ambiguous under the *new* one are Phase A2's job, and `Tops.php:40`
   (`count(id)`) is the reason Phase A2 exists.

6. **Seeder** — `database/seeders/E2ESeeder.php` forces PK values
   (`$this->insert('game', ['game_id' => self::GAME_ID], …)`, ~65 sites). Note
   its own comment near line 232 about `user_id` being the PK and not fillable.

7. **Validation** — five rules name a legacy key:
   `Admin/Links/LinkController.php:22` (`exists:website_category,website_category_id`),
   `Admin/Reviews/ReviewsController.php:58` (`exists:game,game_id`) `,181`,
   `Admin/Interviews/InterviewsController.php:57,231`,
   `Admin/Articles/ArticleController.php:240`.

8. **AJAX wire format** — for the four endpoints that serialise models directly
   (listed under Phase A's out-of-scope note), the JSON key changes with the
   column. Move the endpoint and every matching `data-autocomplete-id` in Blade
   in the same commit, or neither. This is the one path in the whole campaign
   that fails in the browser with nothing on the server side to notice it.

Route model binding needs no work: there is no `getRouteKeyName()`,
`resolveRouteBinding()` or `Route::bind()` anywhere, so implicit binding just
follows `$primaryKey`. No model carries its own primary key in `$fillable`
either, so nothing is silently discarded on write.

### Suggested order

Start with a low-fanout table to prove the recipe end to end, then work up:

1. `trivias` / `trivia_quotes`, `article_type`, `news_image` — few inbound FKs.
2. `website_category`, `crew`, `spotlight`, `change_log`.
3. `article_main` + `article_text` + `article_comments`, then the review and
   interview equivalents — these already have recent `*_constraints.php`
   migrations to model the FK drop/re-add on.
4. `individuals`, `pub_dev`, `users`, `comments`, `screenshot_main`.
5. `game` last — the widest fanout by a distance.

### Not renameable by this plan

Two tables use the PK column as the foreign key to their parent, which is a
schema change rather than a rename. Leave them declaring `$primaryKey`, and
record why:

- `website_validate.website_id` (`app/Models/WebsiteValidate.php:10`) — also the
  name of `website`'s PK.
- `pub_dev_text.pub_dev_text` (`app/Models/PublisherDeveloperText.php:11`) — the
  table also has a `pub_dev_id` FK and a `pub_dev_profile` column, so token
  matching is especially unsafe here.

`andreas.comments_id` (`app/Models/Andreas.php:9`) collides in name with
`comments.comments_id` but is an independent PK; it renames normally.

### Recorded as follow-up, not done here

Foreign key names that do not match the Laravel convention (which derives the FK
from the **model** name, not the table):

- `ind_id` vs `individual_id` — both styles already exist for `Individual`.
- `game_release_id` vs `release_id` — both point at `game_release.id`; model is
  `Release`, so `release_id` is the correct one.
- `comments_id` vs `comment_id` — `article_user_comments` disagrees with the
  game/review/interview equivalents.
- `dev_pub_id` in `game_developer` → `pub_dev.pub_dev_id`.

Fixing these would let the remaining ~84 explicit relationship arguments go, but
FK names leak into `$fillable`, form field names and request payloads — a wider
and much quieter blast radius. Separate campaign.

## Harness changes (prerequisites)

The suites as they stand cannot see the failure this campaign risks — see "What
the suites cannot see" below, where a complete rename with a known bug in it ran
986 + 318 tests green. Four changes fix that. Each was prototyped and measured
before being written down here; land them before Phase B.

### 1. Give every table a distinct id range

The root cause of the blindness is that every fixture makes parent and child ids
identical, so a key read from the wrong table returns the right number by
coincidence. Distinct ranges make cross-table confusion arithmetically visible,
and the fix is small:

```php
// tests/TestCase.php, or a trait the RefreshDatabase tests use
protected function afterRefreshingDatabase(): void
{
    foreach (['article_main' => 1000, 'article_text' => 2000, /* … */] as $table => $base) {
        DB::table('sqlite_sequence')->updateOrInsert(['name' => $table], ['seq' => $base]);
    }
}
```

Verified: `article_main.id = 1001, article_text.id = 2001`. One trap —
`sqlite_sequence` already holds 62 rows after the migrations run, and it carries
no unique constraint, so a plain `insert()` silently adds a duplicate row and
the offset is ignored. It has to be `updateOrInsert()`.

The hook fires often enough for this to hold. In
`Illuminate\Foundation\Testing\RefreshDatabase::refreshDatabase()`,
`afterRefreshingDatabase()` sits *outside* the `RefreshDatabaseState::$migrated`
guard, so it runs for every test rather than once per process — and it runs
after `beginDatabaseTransaction()`, which means the `sqlite_sequence` write is
part of the test's own transaction and is rolled back with it, then re-applied
by the next test. `sqlite_sequence` is an ordinary transactional table, so that
round trip is sound.

Do not hand-maintain the table→offset map. There are 62 candidate tables, and a
table missing from a hand-written list is silently back to the old behaviour —
the same failure this change exists to remove. Enumerate the tables from
`sqlite_master` and assign `base = index * 1000`, so a table added later is
covered without anyone remembering to add it.

**`E2ESeeder` needs a different fix from the one an earlier draft gave it.**
`ALTER TABLE … AUTO_INCREMENT = n` does nothing there: the seeder inserts
*explicit* primary keys (`['game_id' => self::GAME_ID]`), so the auto-increment
counter is never consulted. The colliding values are the constants themselves —
**37 of the 51 `*_ID` constants in `E2ESeeder` are literally `1`**, and
`tests/e2e/support/fixture.js` mirrors them one for one (`game.id`,
`release.id`, `review.id`, `company.id`, `individual.id`, `crew.id`, … all `1`).

So the e2e half of this change is: give the constants distinct values, one
range per table, and update `fixture.js` in the same commit. That is a wider
edit than the SQLite hook — every constant is referenced from both sides — but
without it the Playwright suite stays exactly as blind as it was in the article
experiment, which is the suite Verification step 2 calls "the real net".

This is the single change that would have turned the article experiment red. It
also generalises well past this campaign: any test that passes only because two
unrelated ids both happen to be `1` starts failing honestly.

### 2. Ban Eloquent models in migrations

Phase B step 2's trap has a permanent fix — a test asserting that no file under
`database/migrations/` contains `use App\Models\`. Five files violate it today:

- `2020_11_08_140622_update_links_in_content.php` — `ArticleText`, `Game`,
  `GameFact`, `InterviewText`, `News`, `Review`
- `2023_02_02_194320_games_add_slug.php`, `2023_02_18_102016_fix-slugs.php` — `Game`
- `2021_05_22_160818_insert_sndh_45.php`, `2026_01_02_111723_insert_sndh_2026.php` — `Sndh`

Six of those seven models are in this campaign. Convert the call sites to
`DB::table()` with an explicit `orderBy` on the historical column name, then the
test locks the rule in and migrations stop being coupled to present-day model
state.

### 3. Make the MariaDB migration run an explicit CI gate

`artisan test` skips every `!== 'sqlite'`-guarded migration wholesale, which is
how a green 986-test run coexisted with a `migrate:fresh` that was broken on
MariaDB. CI catches it today only as a side effect of the "Prepare the E2E
database and storage" step, which is not where anyone looks when a migration
fails.

Add a job that runs `migrate:fresh` and then `migrate:rollback --step=1`
against MariaDB, named for what it does, so this class fails fast and legibly
rather than surfacing as a confusing e2e setup error.

**`--step=1`, not a bare rollback.** After `migrate:fresh` every migration sits
in a single batch, so a bare `migrate:rollback` tries to reverse all 262 of
them. Measured: it dies after 32, on `down()` methods written years ago and
never run since —

- `2022_09_10_120014_magazine_individual.php` drops a column whose index an
  inbound foreign key still needs (`SQLSTATE 1553`). Fixed.
- `2022_01_15_163533_add_news_foreign_keys.php` restores a column to `NOT NULL`
  while a `SET NULL` foreign key still points at it (`SQLSTATE 1830`). Not
  fixed, and there are likely more behind it.

Reversing the entire history is not what this campaign needs and has never
worked; making the gate demand it would just paint CI red. What has to work is
reversing **the newest migration**, which is exactly what a bad rename deploy
would do — see "Deploying a rename". `--step=1` tests that, and it passes
today.

The full-history rollback is worth fixing eventually, but as its own piece of
work, not as a prerequisite hidden inside this one.

### 4. Pin the autocomplete wire format

`tests/Feature/Public/AutocompleteTest.php` asserts on `game_name` and never on
the id key, and the e2e specs check that the endpoints respond rather than that
a selected id round-trips. The `undefined`-in-a-hidden-field failure from Phase B
step 8 therefore has nothing watching it. Two cheap additions:

- `assertJsonStructure` on each of the six endpoints whose payload carries a
  legacy id key — `Ajax/Crew`, `Ajax/Company`, `Ajax/Game`, `Ajax/Individual`,
  `Admin/Ajax/User`, `Admin/Ajax/Game` — naming that key explicitly, so a
  changed key is a failed assertion rather than a changed page.
- A guard on the hidden companion field, in `pickAutocompleteBy`. **Checking it
  is non-empty is not enough**, which is why this is worth spelling out:
  `autocomplete.js:83` reads `value[dataset.autocompleteId]`, and assigning a
  missing key to `input.value` stringifies it, so the field holds the *string*
  `"undefined"` and submits happily. Rejecting `"undefined"`/`"null"` there
  turns every existing autocomplete spec into a wire-format guard, which is a
  wider net than one new spec would have been.
- One write spec that picks a value from an autocomplete, saves the form, and
  asserts the resulting association **by id** — the round trip, not just the
  response. The similar-games step of `admin-write/games.spec.js` does this.

## Verification

Per rename PR, in order:

1. `docker compose run --rm php artisan test` — 76 PHPUnit files, 37 using
   `RefreshDatabase`, running all 262 migrations against SQLite in-memory. This
   catches the migration itself, the model, relationships, and validation rules.
2. `npx playwright test` — 37 specs under `tests/e2e/{public,admin,public-write,admin-write}`.
   This is the real net: strict mode turns any missed property read into a 500,
   and any missed query string into an SQL error. Both surface as failing specs.
   The write projects matter most — they exercise the Livewire tables and the
   `setPrimaryKey` paths.
3. Grep for the old token restricted to the renamed table's own name and its
   qualified form (`game.game_id`), and confirm every survivor is a foreign key
   on another table.
4. **Grep the other way too**, which step 3 cannot do: list every query that
   joins the renamed table to anything, and confirm each one has a qualified
   select list. The dangerous sites do not contain the old name — see
   Phase A2 — so a token grep will never surface them.
5. Manually exercise the autocomplete on an affected admin form — the
   Blade/JSON/JS contract is the one path where a mistake is silent, because the
   key is a data-driven string on both sides, and `undefined` reaches the
   database as readily as a real id would.
6. Against a MariaDB copy of production data, not just SQLite: run
   `artisan migrate` and then `artisan migrate:rollback` for the new migration
   only. This is mostly about the rollback path, which has to reverse the
   rename, and about the raw `ENUM` / `AUTO_INCREMENT` DDL meeting real data
   volume. SQLite exercises neither.

### What the suites cannot see

Measured, not assumed. A complete rename of `article_main.article_id` and
`article_text.article_text_id` was applied on a scratch branch — migration,
both models, the Livewire table, the seeder, every join condition — leaving
*only* the unqualified `Article::select()` in place. Results:

- PHPUnit: **986 passed, 0 failed**
- Playwright `public` + `admin`: **252 passed, 0 failed**
- Playwright `admin-write` + `public-write`: **66 passed**

Including `public/articles.spec.js:13`, which clicks the article link on the
list page — the page the unqualified join builds — and asserts the id in the
resulting URL.

The fixtures cannot express the bug. After the rename `article_main.id` is 1 and
`article_text.id` is 1, so `getKey()` returning the wrong row's key returns the
same number. It is structural rather than an oversight: `ArticleFactory::configure()`
creates exactly one `ArticleText` per `Article`, so the two sequences advance in
lockstep forever, and `E2ESeeder` keys `article_text` by `article_id` rather
than by its own primary key. `interview_main`/`interview_text` and
`review_main`/`game` are 1 and 1 for the same reason.

Letting the sequences drift the way production data drifted long ago —
`Article::texts()` is a `hasMany` — the bug is immediate:

```
article_main.id = 2, article_text.id = 3, hydrated getKey() = 3
```

**So Verification steps 1 and 2 are not a net for this class** — not as the
fixtures stand. Phase A2 is the control. Harness change 1 is what makes the
suites able to disagree with it: with distinct id ranges the same experiment
fails, because the wrong key is then a visibly wrong number rather than the
same `1`.

## Deploying a rename

`.github/workflows/deploy.sh` rsyncs the whole tree first and runs
`artisan migrate --force` afterwards, over four separate SSH calls. For every
other change that ordering is harmless. For a rename it is backwards: there is
no schema that both the old and the new code can read, so between the first
file landing and the migration finishing, the server runs new code against the
old columns. `rsync -avvz --delete` over the tree is not atomic either, so
partial code states are served during the transfer.

Nothing in the schema work makes this dangerous — the DDL is metadata-only and
reversible, and a rename cannot lose data. What it produces is a window of
broken pages, and in production those pages return 200 with holes rather than
failing. Bound the window rather than hoping it is short:

- **Take a dump immediately before the migration runs, and confirm it restores.**
  The daily export under `public/data/database-dumps/` is not that; it can be a
  day old. The point is not the rename — it is that a bad deploy at that moment
  has no other floor.
- **`artisan down` before the rsync, `artisan up` after `optimize`.** A
  maintenance page for the duration is a better answer than a minute of wrong
  ids. It has to be a workflow input or a commit marker (e.g. `[offline]`) in
  `.github/workflows/deploy.sh`, so the site only goes down for these
  migrations and not for every deploy. Prose in this document is not a
  mechanism: `deploy.sh` runs on push to `master`, so there is no moment at
  which an operator could wedge the commands in by hand. The flag file itself
  survives the transfer — `--filter=- /storage` keeps rsync off Laravel's
  maintenance marker — so the only missing piece is the two `ssh` calls.
- **Deploy renames on their own**, never bundled with unrelated changes.
- **Reverting is two steps, in this order, and the order is not negotiable.**
  "Revert the commit" is not a rollback plan here: `deploy.sh` only ever runs
  `artisan migrate`, never `migrate:rollback`, and the revert commit *deletes*
  the migration file. Deploy it first and the server is left with the new
  schema, the old code, and nothing on disk that knows how to reverse the
  rename. So:

  1. `artisan migrate:rollback --step=1` over SSH, **while the migration file
     is still on the server**.
  2. Then push the revert.

  This is what makes a real `down()` on every rename migration a hard
  requirement rather than a formality — see Phase B step 1.
- Ship to `development` and let dev.atarilegend.com sit for a day before the
  same PR goes to `master`. The whole campaign is optional work; nothing about
  it needs to be fast.
