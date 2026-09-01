# The column-name consistency sweep

*2026-08-31*

Successor to the [plural table rename](2026-08-29-plural-table-rename.md), the
[schema consistency sweep](2026-08-26-schema-consistency-sweep.md) and the
[foreign-key rename](2026-08-23-foreign-key-rename.md). Those campaigns
converged the table names, the primary keys and the foreign-key rule; this plan
closes the column names they left behind: the two kept tables that still carry a
prefixed primary key, the three columns that still break the foreign-key rule,
the columns named after their own table, and the legacy table-stem prefixes on
the content columns. It is naming only — no column changes type, no date column
becomes a `datetime`, no enum is touched, and the custom `users` authentication
columns (`userid`, `sha512_password`, `salt`, `permission`, `join_date`,
`last_visit`, `karma`, `inactive`) are left alone.

**The delivery unit is one commit per unit, based on `development`, not one pull
request.** Start from a clean tree: on 2026-08-31 `development` carried an
uncommitted fix in `app/Livewire/Admin/CommentsTable.php` (the join reading
`users.user_id` instead of `users.id`, left over from the primary-key
campaign), and Unit 3 edits that same file. Commit or drop it before Unit 1, so
no unit's diff carries someone else's change. A unit carries one migration per
renamed column, so reversing a
deployed unit is `migrate:rollback --step=N` for the N migrations in that commit,
and reverting the commit removes all N migration files at once. N is named in
each unit's "The migrations".

The end state, checked clause by clause:

- Every primary key is the single column `id`: the primary-key census under
  "Verification" returns only the two composite pivots.
- Every foreign-key column is the singularised referenced-table name + `_id`,
  except the four role-qualified keys named under Out of scope: the rule census
  under "How the inventory was obtained" returns zero rows.
- No column in the campaign's inventory carries its own table's name as a
  prefix: the named-column census under "Verification" returns zero rows.
- No column anywhere in the schema names its own table without a recorded
  reason: the word census under "Verification" returns the six rows named
  there, each one a standing decision, and nothing else.
- `RelationshipKeyConventionsTest::DECLINED` holds 18 entries, down from 19,
  because `GameSubmitInfo::screenshots()` converges (Unit 2): `php artisan test`
  passes, with `test_no_relation_diverges_from_the_convention_without_a_reason`
  at 18 entries — the test's other assertion checks for redundant key
  arguments and does not count `DECLINED`.

| Unit | Scope | Columns | Migrations |
|---|---|---|---|
| 1 — the kept tables' primary keys | `game_gallery`, `database_change` | 3 | 3 |
| 2 — the three columns off the rule | `game_similar`, `screenshot_game_submitinfo`, `website_validates` | 3 | 3 |
| 3 — the columns named after their own table | `spotlights`, `trivia_quotes`, `trivia`, `game_facts`, `article_types`, `comments`, `game_submit_infos`, the three `screenshot_*_comments` | 10 | 10 |
| 4 — the game's display names | `games`, `game_akas` | 2 | 2 |
| 5 — the people and company columns | `individuals`, `pub_devs`, `crews` | 10 | 10 |
| 6 — the link columns | `websites`, `website_categories`, `website_validates` | 10 | 10 |
| 7 — the news columns | `news`, `news_submissions`, `news_images` | 8 | 8 |
| 8 — the article columns | `articles` | 4 | 4 |
| 9 — the interview columns | `interviews` | 4 | 4 |
| 10 — the review columns | `reviews` | 7 | 7 |
| 11 — the user-profile social columns | `users` | 4 | 4 |

The units are independent of each other and can be executed in any order. Units
2 and 6 both touch `website_validates`, on different columns, so their relative
order is free. No unit renames a table, so `RENAME TABLE`'s foreign-key
rewriting does not apply; each unit is a set of `renameColumn` calls.

## How the inventory was obtained

Not by reading the models. The column set is every column whose name begins with
the singular or plural stem of its own table, plus the two prefixed primary keys
and the three columns the foreign-key rule names — two of them constrained, one
not. The list is then checked against two
censuses under "Verification", because one is not enough.

The first is the **stem census**: a column whose name starts with its own table
name, or that name minus a trailing `s`. On 2026-08-31 it surfaced four columns
the hand application had missed — `comments.comment`,
`news_images.news_image_name`, `news_images.news_image_ext` and
`database_change.database_change_script` — folded into Units 3, 7 and 1. But it
only sees a stem sitting at the front of the column and spelled the way the
table spells it, so it is blind to a stem that is one word of a multi-word table
name (`game_akas.aka_name`), a stem that ends the column rather than starting it
(`game_gallery.game_description_gallery`), a table whose stem is longer than the
column's (`news_submissions.news_headline`), and any abbreviation
(`individuals.ind_name`).

The second is the **word census**, which compares every underscore-delimited
word of the column against every word of the table, each side also tried with a
trailing `s` trimmed. It is the completeness check the first census cannot be.
Run on 2026-08-31 it returned 62 rows, of which six were columns the earlier
hand application had missed:

| Column | Rows | Why the stem census missed it |
|---|---|---|
| `game_akas.aka_name` | 282 | `aka` is the table's second word |
| `game_submit_infos.submit_text` | 2,873 | `submit` is the table's second word |
| `screenshot_article_comments.comment_text` | 78 | `comment` is the table's third word |
| `screenshot_interview_comments.comment_text` | 1,168 | same |
| `screenshot_review_comments.comment_text` | 792 | same |
| `magazine_issues.issue` | 69 | the column equals the table's second word |

The first five are folded into Units 3 and 4 on the same reasoning that moved
`comments.comment` and `games.game_name`. `magazine_issues.issue` is examined
and left, and so is `game_gallery.game_description_gallery`; both are recorded
under Out of scope with their reasons. Neither
census can see an abbreviation, so the four `individuals.ind_*` columns are in
the campaign on the hand application alone — which is the standing limit of the
method, not a gap this campaign closes.

The foreign-key rule is *foreign-key column =
singularised referenced-table name + `_id`*; singularisation is not a SQL
function, so the census is the rule applied by hand to the 142 declared foreign
keys, which on 2026-08-31 leaves exactly two constrained columns off it:

```sql
SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME
FROM   information_schema.KEY_COLUMN_USAGE
WHERE  TABLE_SCHEMA = DATABASE()
AND    REFERENCED_TABLE_NAME IS NOT NULL
AND    COLUMN_NAME IN ('game_similar_cross','game_submitinfo_id');
```

```
game_similar                game_similar_cross    games
screenshot_game_submitinfo  game_submitinfo_id    game_submit_infos
```

A third column is off the rule without being a foreign key:
`website_validates.website_category`, an unconstrained `int(11)` that names a
category without the `_id` suffix. It does not appear in `KEY_COLUMN_USAGE` and
is listed in Unit 2 on its own evidence. The four role-qualified keys
(`individual_nicks.nick_id`, `menu_disks.donated_by_individual_id`,
`game_vs.atari_id`, `sub_crew.parent_id`) also break the rule by necessity and
are the standing exceptions, named under Out of scope.

The reference counts below are line counts, measured 2026-08-31 with

```
rg -n -w '<column>' app resources tests database/factories database/seeders | wc -l
```

over `app`, `resources`, `tests`, `database/factories` and `database/seeders`,
excluding `database/migrations`. A migration naming a column is not a reader.
The counts are the lines that name the token; the column references are the
subset of those that the rewriter touches, and the bare target names (`name`,
`text`, `date`, `url`, …) make the gap large — see "The false-positive hazard".

### The false-positive hazard

The target names are common words. `name` appears in
`LinkColumn::make('Name', 'name')` on lookup tables that already use it, in
`$request->name`, in route names and in prose; `text` is a form-field name
(`name="text"`, `id="text"`, `@error('text')` at
`resources/views/admin/reviews/reviews/card_edit.blade.php:112`) before it is a
column name, and the English word besides; `date` is the same
(`type="date"`, `name="date"`, `@error('date')` at `:64` of that file). A blind
token substitution would rewrite unrelated code. The rewrite is therefore
judgement per site, the same rule the primary-key and foreign-key campaigns
applied: rename a reference only when it names the
column being moved — a `$fillable` entry, a `$casts` key, a controller
assignment key, a Blade `old()`/attribute read, a Livewire column definition and
its search/sort keys, a qualified query-builder column, a column inside a
`selectRaw()` or `DB::raw()` string, a `pluck()` result key, a factory or seeder
key, a test assertion. Leave every other occurrence of the word.

Two consequences follow. First, the reference count is a ceiling, not a work
estimate: the unit's real size is the column-reference subset, and the
acceptance gate is "the token no longer names the column", not "the token is
gone". Second, the bare targets mean a unit's acceptance cannot be a bare
`rg -w '<target>'` returning nothing — `name` and `text` will always have
legitimate hits. The gate is the old token: `rg -n -w '<old>'` over the five
trees returns nothing except the historical migrations.

Two old tokens fail even that gate, because they are words the code uses for
things other than the column. `comment` survives as a form-field name, a route
name, a `data-comment-*` JS hook, the `$comment` variable and the English word;
`spotlight` survives as the `$spotlight` route-model-binding parameter and
variable — 72 of its 119 lines are that, and none of them move. For those two
the gate is not the token count but the site list: every reference under the
unit's "The code" reads the new name. Two more (`game_fact`, `article_type`)
each keep a single prose line in a code comment, which the gate reads past.

### The code shape of a prefix rename

The controllers already read bare request keys and write prefixed columns —
`'news_headline' => $request->headline` at
`app/Http/Controllers/Admin/News/NewsController.php:63`,
`'game_name' => $request->name` at
`app/Http/Controllers/Admin/Games/GameController.php:80`,
`'user_website' => $request->website` at
`app/Http/Controllers/Auth/UserController.php:30` — and the Blade form fields
are bare (`name="headline"`, `name="name"`, `name="website"`). So a prefix rename
changes the column side only:

- the migration (`renameColumn`);
- the model's `$fillable` entry and, for the date columns, its `$casts` key —
  shipped in the same commit, because `preventSilentlyDiscardingAttributes` is
  off in production and a stale `$fillable` entry is a silent drop there;
- the controller assignment key (`'news_headline' =>` becomes `'headline' =>`);
- the Blade reads of the column (`$news->news_headline` becomes
  `$news->headline`, including the `old('headline', $news->news_headline)`
  fallbacks, whose first argument is the request key and stays);
- the Livewire column definitions and their `->searchable()` / `->sortable()`
  closures. The tables use `Column` and `LinkColumn`, which take the column as
  an optional second argument: `LinkColumn::make('Headline', 'news_headline')`
  at `app/Livewire/Admin/NewsTable.php:25` names the column there and again in
  its `->title()` closure and its `->searchable()` `where`, while
  `LinkColumn::make('Name')` at `app/Livewire/Admin/Games/GamesTable.php:22`
  names it in the closures only. There is no `TextColumn` in this codebase;
- the qualified query-builder columns (`->orderBy('news.news_date')` becomes
  `->orderBy('news.date')`);
- the column names inside `selectRaw()`, `orderByRaw()` and `DB::raw()`
  strings, which no builder method wraps and no model mediates —
  `app/View/Components/Cards/Tops.php` is the campaign's worst case, with
  `selectRaw('count(game_id) as game_count, pub_dev_name, pub_devs.id')` beside
  a bare `->orderBy('pub_dev_name')`, and the six
  `orderByRaw('instr(aka_name, ?)')` pairs in Unit 4 are the same shape;
- the collection key strings — `pluck()`, `keyBy()`, `sortBy()`, `implode()` —
  that name an **attribute or result-set** key rather than a query column, and
  move with the column or the select that produced them:
  `AdminStatisticsHelper::topPublishers()` selects `pub_devs.pub_dev_name` and
  then reads `$rows->pluck('total', 'pub_dev_name')`, and both halves move
  together or the chart loses its labels;
- the factory and seeder keys;
- the test assertions;
- the `data-autocomplete-key` attributes that name the column in the JSON
  payload of an autocomplete endpoint (Units 4 and 5) — `autocomplete.js:78`
  reads `feedback.selection.value[el.dataset.autocompleteKey]`, so the attribute
  names a payload property, not the form field, and it moves with the column.

The form-field `name=` attributes and the `$request->` keys do not move.

Where a rename collapses several tables' columns onto one bare name — Unit 4
puts `games` and `game_akas` both on `name`, Unit 5 adds `individuals`,
`pub_devs` and `crews` — the unqualified query-builder references at the rename
sites are qualified while they move (`games.name`, `pub_devs.name`), so
correctness does not depend on inner-scope resolution. The exception is a
`whereHas()` closure, whose innermost scope holds only the related table and
its pivot: a bare `name` there already resolves to the related table, MySQL
resolves a correlated subquery innermost-first, and qualifying it would only
repeat what the closure already says. Those stay bare, and each unit's "The
code" says which they are.

No target name collides with a column the table already holds: checked
2026-08-31 by joining the campaign's 65 (table, old, new) triples against
`information_schema.COLUMNS`, which returns no row where the new name already
exists. The two collision candidates a reader will look for are both false —
`individuals.ind_email` becomes `email` on `individuals`, not on `users`, and
`website_validates.website_description` becomes `description` on
`website_validates`, where `websites.description` is a different table.

### The migrations

One `renameColumn` per migration, in its own blueprint, the shape the primary-key
campaign settled: no migration in this project runs in a transaction
(`Grammar::$transactions` is false with no driver override), so a migration that
renames several columns and fails part-way leaves partial state with nothing to
unwind it, and separate blueprints keep `renameColumn` working on SQLite, which
is what the unit suite runs against. Each `down()` is the single `renameColumn`
back. None of the renamed columns outside Unit 2 carries a secondary index.
The 29 affected tables carry 29 secondary indexes between them, measured
2026-08-31 against `information_schema.STATISTICS`, and every one of them sits
on a column this campaign does not rename (`user_id`, `game_id`,
`article_type_id`, `games.slug`, `users.userid`, …) — except the two Unit 2
foreign keys. So no index name is left stale outside Unit 2, and no
`information_schema` rename half is needed, unlike the table renames.

Where a renamed column is a foreign key (Unit 2), the constraint and its index
keep their old names: `ALTER TABLE … CHANGE` rewrites the constraint's column
reference, and the name stays. The schema consistency sweep's "Index and
constraint names" records the standing decision to leave such names; a later
`dropForeign` would derive the new name and fail with 1091, so the literal form
is used if one is ever needed.

## Unit 1 — the two kept tables' primary keys

### The columns

The primary-key campaign renamed every prefixed primary key to `id` except the
26 tables with no model, which it left alone because nothing in `app/Models/`
named them. The dead-tables review then examined those and kept two —
`game_gallery` (118 rows, magazine adverts) and `database_change` (267 rows, the
pre-Laravel schema ledger) — so they now carry prefixed keys in an otherwise
all-`id` schema. Measured 2026-08-31:

| Table | Primary key | Rows | Inbound FKs | Code references |
|---|---|---|---|---|
| `game_gallery` | `game_gallery_id` | 118 | 0 | 0 |
| `database_change` | `database_change_id` | 267 | 0 | 0 |

Neither table has a model, a relation, or a reference outside
`database/migrations`; `rg -n -w game_gallery_id` and `rg -n -w
database_change_id` over the five trees return nothing. Each primary key is the
table's only key, so the rename leaves no stale index.

`database_change` also holds a `database_change_script` column — the ledger's
SQL text — which carries the table's name as a prefix. **Decision: it becomes
`script`.** It has no code references, so the rename is a migration only.

**Decision: both keys become `id`.** The tables stay singular — `game_gallery`
and `database_change` are kept record tables, not model-backed entities, so the
plural rule does not reach them; this unit renames the two keys and
`database_change`'s prefixed script column, and nothing else.

### The migrations

Three, one per column: `2026_08_31_100000_game_gallery_primary_key`,
`2026_08_31_100100_database_change_primary_key` and
`2026_08_31_100200_database_change_script_rename`, each a single `renameColumn`
and its reverse. `database_change` also holds a `database_update_id` column; it
is not a foreign key and is not renamed.

### Acceptance

- `SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE
  WHERE TABLE_SCHEMA = DATABASE() AND CONSTRAINT_NAME = 'PRIMARY' AND
  COLUMN_NAME <> 'id'` returns only the four composite-pivot rows
  (`crew_menu_set` ×2, `game_sndh` ×2). This is one row per key column; the
  grouped form under "Verification" is the same assertion returning the two
  tables rather than their four columns.
- `php artisan test` passes. No test names either column, so the suite is a
  regression gate and the query above is the real one.
- `php artisan migrate:rollback --step=3` followed by `php artisan migrate`
  succeeds on the dev MariaDB with its data loaded.

## Unit 2 — the three columns off the rule

### The columns

The rule census returns two constrained columns and one unconstrained one,
measured 2026-08-31:

| Column | Table | References | Becomes |
|---|---|---|---|
| `game_similar_cross` | `game_similar` | `games.id` | `similar_game_id` |
| `game_submitinfo_id` | `screenshot_game_submitinfo` | `game_submit_infos.id` | `game_submit_info_id` |
| `website_category` | `website_validates` | — (no constraint) | `website_category_id` |

`game_similar` is a self-referential pivot: both `game_id` and
`game_similar_cross` reference `games`, one row per direction (415 rows, 0
self-references, 294 distinct unordered pairs of which 121 are reciprocal —
242 directed rows — measured 2026-08-31). The second column
is named after its own table plus `cross`, not after the referenced table; the
sibling is `game_id`. A table cannot hold two `game_id` columns, so the second
takes a role name. **Decision: `game_similar_cross` becomes `similar_game_id`.**

`game_submitinfo_id` references `game_submit_infos`, which the plural campaign
renamed from `game_submitinfo`; the singularised rule now gives
`game_submit_info_id`. **Decision: it becomes `game_submit_info_id`.** This is
the one foreign-key break the plural campaign recorded in Out of scope and
priced as "one follow-up".

`website_validates.website_category` is an `int(11)` that names a
`website_categories` row without the `_id` suffix; the table holds 0 rows and the
column has no constraint. **Decision: it becomes `website_category_id`.**

### The code

`game_similar_cross` is named in four lines: the two `belongsToMany` key
arguments at `app/Models/Game.php:180` and `:185`, and the two
`RelationshipKeyConventionsTest::DECLINED` reason strings at
`tests/Feature/RelationshipKeyConventionsTest.php:47-48`. The relations stay
self-referential — the pivot's two keys must differ — so both entries
remain, with the reason text updated to `game_similar needs game_id and
similar_game_id` and `game_similar needs similar_game_id and game_id`.

`game_submitinfo_id` is named in two lines: the key argument at
`app/Models/GameSubmitInfo.php:21` and the `DECLINED` entry at
`tests/Feature/RelationshipKeyConventionsTest.php:59`. The rename converges the
relation: Eloquent derives `game_submit_info_id` from the class `GameSubmitInfo`
and `screenshot_id` from `Screenshot`, so both key arguments become redundant and
`screenshots()` becomes
`$this->belongsToMany(Screenshot::class, 'screenshot_game_submitinfo')` — the
pivot-table argument stays, because the derived pivot name
(`game_submit_info_screenshot`) is not the table. The `DECLINED` entry is
deleted, taking the list from 19 to 18; leaving it fails
`test_no_relation_diverges_from_the_convention_without_a_reason` on a declared
exception that no longer diverges, and leaving the arguments fails
`test_no_relation_passes_a_key_argument_it_would_derive_anyway`.

`website_category` has no code references.

### The migrations

Three, one per column: `2026_08_31_100300_game_similar_cross_rename`,
`2026_08_31_100400_game_submitinfo_id_rename`,
`2026_08_31_100500_website_category_id_rename`. The first two leave their
constraint and index names stale by the standing rule; the third has no
constraint.

### Acceptance

- The rule census query returns zero rows.
- `php artisan al:audit-relationship-keys` reports `GameSubmitInfo::screenshots()`
  as clean and `Game::similarGames()` / `similarGamesReverse()` as divergent on
  `similar_game_id`; the `--pivots` snapshot is unchanged, because no pivot
  table moved.
- `php artisan test` passes, including
  `test_no_relation_diverges_from_the_convention_without_a_reason` at 18
  entries.
- `php artisan migrate:rollback --step=3` followed by `php artisan migrate`
  succeeds on the dev MariaDB with its data loaded.

## Unit 3 — the columns named after their own table

### The columns

Ten columns carry their own table's name, measured 2026-08-31:

| Column | Table | Type | Becomes | Token lines |
|---|---|---|---|---|
| `spotlight` | `spotlights` | `mediumtext` | `text` | 119 |
| `trivia_quote` | `trivia_quotes` | `mediumtext` | `quote` | 12 |
| `trivia_text` | `trivia` | `mediumtext` | `text` | 15 |
| `game_fact` | `game_facts` | `mediumtext` | `fact` | 19 |
| `article_type` | `article_types` | `varchar(50)` | `name` | 20 |
| `comment` | `comments` | `mediumtext` | `text` | 368 |
| `submit_text` | `game_submit_infos` | `mediumtext` | `text` | 9 |
| `comment_text` | `screenshot_article_comments` | `mediumtext` | `text` | 30 (shared) |
| `comment_text` | `screenshot_interview_comments` | `text` | `text` | (in the 30) |
| `comment_text` | `screenshot_review_comments` | `mediumtext` | `text` | (in the 30) |

All but one hold the row's content and take the bare content word; the
exception is `article_type`, a lookup-table label, which takes `name`, the label
column every other lookup table uses (`controls.name`, `emulators.name`,
`game_genres.name`). **Decision: the targets above.** `spotlights.spotlight`,
`comments.comment`, `game_submit_infos.submit_text` and the three
`screenshot_*_comments.comment_text` all become `text`, the word the parallel
content columns take in Units 7-10; the last four are the word census's
findings, invisible to the stem census because the stem is not the column's
first word. `andreas.comment` is a different table's column — the proper-noun
guestbook — and does not move.

### The code

The sites are the standard shape under "The code shape of a prefix rename": the
`$fillable` entries at `app/Models/Spotlight.php:14`, `TriviaQuote.php:11`,
`Trivia.php:14`, `GameFact.php:9`, `ArticleType.php:14` and `Comment.php:21`;
the controller assignment keys and changelog `section_name` reads in
`app/Http/Controllers/Admin/Other/SpotlightController.php`,
`…/TriviaController.php` and `…/QuoteController.php`; the article-type sites in
`app/Http/Controllers/Admin/Articles/ArticleTypeController.php` — the
`orderBy('article_type')` at `:16`, the create at `:32`, the update at `:50` and
the changelog `section_name` / `sub_section_name` reads at `:38,41,56,59,73,76`
— plus the two `orderBy('article_type')` calls that fill the type dropdown in
`ArticleController.php:32,48`; the Livewire columns; the Blade reads; the
factories and seeders; the test assertions.
`comment` adds its own sites: the `CommentsTable` title and searchable at
`app/Livewire/Admin/CommentsTable.php:54,57`; the display and textarea at
`resources/views/components/cards/partial_comment.blade.php:43,53`; the five
public comment controllers (`GameController.php:171`, `ReviewController.php:158`,
`InterviewController.php:49`, `CommentController.php:38`,
`ArticleController.php:60`) and the admin one
(`Admin/User/CommentController.php:50`); the `CommentFactory.php:32` and
`E2ESeeder.php:486` keys; the test assertions. `comment` is the high-noise
token of the unit — 368 lines name it, and most are the `Comment` model, the
`comments` table, the form field, the route names, the `data-comment-*` JS
hooks, the `$comment` variable and the English word, none of which move.
`spotlight` is next at 119, of which 72 are the `$spotlight` variable and the
route-model-binding parameter (`public function edit(Spotlight $spotlight)`),
plus the `Spotlight` model, the `spotlights` table, the `spotlight_screens`
storage folder and the route names — none of which move.

`submit_text` and `comment_text` are the quiet end of the unit, and they split
on the mass-assignment hazard. `GameSubmitInfo` and `ScreenshotReviewComment`
declare no `$fillable` at all, and their writes are direct attribute assignment,
so there is no entry to move and a missed site fails loudly as SQL error 1054.
The other two do carry one — `$fillable = ['comment_text']` at
`app/Models/ScreenshotArticleComment.php:10` and
`$fillable = ['screenshot_interview_id', 'comment_text']` at
`app/Models/ScreenshotInterviewComment.php:11` — and the article and interview
writes go through it: `new ScreenshotArticleComment(['comment_text' => …])` and
`$comment->update(['comment_text' => …])` at
`Admin/Articles/ArticleController.php:200,204` and the same shape at
`Admin/Interviews/InterviewsController.php:195,199`. For those two the hazard is
the campaign's standing one: with `preventSilentlyDiscardingAttributes` off in
production, a `$fillable` entry left on the old name drops the description
silently rather than erroring, so it ships in the same commit as the migration.

`game_submit_infos.submit_text` is the submitter's note. Its nine lines are the
write at `app/Http/Controllers/GameController.php:194`, the read at
`app/Http/Controllers/Admin/Games/GameSubmissionController.php:72` (which feeds
a `'comment'` payload key that is not a column and stays), the display at
`resources/views/admin/games/submissions/card_show.blade.php:23`, five test
fixtures and assertions, and `E2ESeeder.php:321`.

The three `comment_text` columns are the screenshot descriptions the merge
campaign left behind, reached through the `ScreenshotArticle`,
`ScreenshotInterview` and `ScreenshotReview` pivot models' `comment()`
relations. The relation name is not the column: `$screenshot->pivot->comment`
stays and only the `->comment_text` on the end of it moves, which is the whole
shape of the thirty lines — the `$fillable` entries at
`app/Models/ScreenshotInterviewComment.php:11` and
`ScreenshotArticleComment.php:10`; the `'comment_text' => $value` writes in
`Admin/Articles/ArticleController.php:200,204` and
`Admin/Interviews/InterviewsController.php:195,199`; the direct assignments in
`Admin/Reviews/ReviewsController.php:116,129` and
`ReviewController.php:129`; the `card_images.blade.php` textareas for articles
and interviews, the reviews equivalent at
`admin/reviews/reviews/card_edit.blade.php:181`, and the public
`card_article`, `card_interview` and `card_review` reads; the test assertions;
and two `E2ESeeder` keys.

### The migrations

Ten, one per column, `2026_08_31_100600` through `2026_08_31_101500`, in the
table's row order. The three `comment_text` renames are three separate
migrations on three separate tables, not one.

### Acceptance

- `rg -n -w` for each old token over the five trees returns nothing except the
  historical migrations — except `comment` and `spotlight`, whose surviving
  lines are named under "The false-positive hazard" and whose gate is instead
  the column-reference sites above reading `text`, and `game_fact` and
  `article_type`, which each keep one prose line in a code comment.
- `php artisan test` passes.
- `php artisan migrate:rollback --step=10` followed by `php artisan migrate`
  succeeds on the dev MariaDB with its data loaded.

## Unit 4 — the game's display names

### The columns

Two columns, measured 2026-08-31:

| Column | Table | Type | Becomes | Token lines |
|---|---|---|---|---|
| `game_name` | `games` | `varchar(255)` | `name` | 398 |
| `aka_name` | `game_akas` | `varchar(128)` | `name` | 40 |

`games.game_name` is the game's display name. **Decision: it becomes `name`,**
the column every lookup table uses for its label and the one `magazines`,
`menu_sets` and `crews` (Unit 5) already converge on. `games` holds no `name`
column today, so the target is free. 398 lines in 134 files name the token; the
column references are the subset, and the token is the campaign's largest.

`game_akas.aka_name` is the same name on the alternate-title table (282 rows),
and it moves with it. **Decision: it becomes `name`.** It is a word-census
finding — `aka` is the table's second word, so the stem census never saw it —
and it belongs in this unit rather than its own because the two columns are read
together everywhere they are read at all: every game autocomplete unions
`games` and `game_akas` and every title search checks both. The precedent is
already in the code, at `Ajax/GameAndSoftwareController.php:19,23`, which
selects `'game_name as name'` and `'aka_name as name'` side by side.

### The code

The `$fillable` entries at `app/Models/Game.php:17` and
`app/Models/GameAka.php:11`; the controller assignment
keys at `app/Http/Controllers/Admin/Games/GameController.php:80` and `:104`;
the changelog `section_name` reads across the game controllers; the Livewire
columns in `app/Livewire/Admin/Games/`, where `LinkColumn::make('Name')` at
`GamesTable.php:22` already carries no column argument, so what moves is its
`->title()` closure's `$row->game_name` and the `where('game_name', …)` in its
`->searchable()`; the Blade reads; the autocomplete contract — the
nine `data-autocomplete-key="game_name"` attributes name a property of the JSON
payload the endpoint returns, not the form field, so they move to `name`, the
precedent being `Ajax/GameAndSoftwareController.php:19` (`game_name as name`)
and `layouts/nav.blade.php:44` (keyed on `name`); the
`app/Http/Controllers/Ajax/` sites — `GameController.php`'s select list,
`where` and `orderBy` clauses and the PHP sort that reads `$a->game_name`, and
`IndividualController.php:30`'s `pluck('game_name')`; the query-builder columns
in `app/Http/Controllers/GameSearchController.php:49,58`, which are
unqualified and become the qualified `games.name`, because after Units 4 and 5
both `games` and `pub_devs` carry a `name` column; the `E2ESeeder` insert; the
test fixtures. `app/Helpers/AdminStatisticsHelper.php` names neither column —
its game queries go through `DB::table('games')` and read the key, not the
name — so it is a Unit 5 site only. `Game::getIsDeletableAttribute()` and the
route model binding are unaffected — neither names the column.

`aka_name` adds four shapes of its own. The `$fillable` entry at
`app/Models/GameAka.php:11` and the write at
`Admin/Games/GameController.php:263` (`'aka_name' => $request->aka` — the
request key stays), with the two changelog `sub_section_name` reads at `:274`
and `:291`. The two `whereHas('akas')` closures at
`GameSearchController.php:60` and `MenuSetController.php:149`, which stay
unqualified: the correlated subquery's innermost scope is `game_akas` alone, so
a bare `name` there resolves to this column and not to `games.name`. Six
`orderByRaw('instr(aka_name, ?)')` / `orderByRaw('length(aka_name)')` pairs
across `Ajax/GameController.php:41-42`,
`Ajax/GameAndSoftwareController.php:44-45` and
`Admin/Ajax/GameController.php:36-37` — raw SQL strings no builder method wraps,
the shape "The code shape of a prefix rename" warns about. And the collection
key strings: `$game->akas->implode('aka_name', ', ')` at `GameHelper.php:89`
and `$game->akas->sortBy('aka_name')` at `card_edit_aka.blade.php:43`.

Two sites collapse because both halves of an alias move at once.
`Ajax/GameController.php:23` reads `->select('aka_name as game_name', 'game_id')`
today; with the column renamed and the payload key renamed with it, the alias
becomes an identity and the line is just `->select('name', 'game_id')`.
`Admin/Ajax/GameController.php:52`'s `'game_name' => $aka->aka_name` becomes
`'name' => $aka->name` for the same reason. Renaming only one half of either
leaves the autocomplete silently keyed on a property the payload no longer has.

### The migrations

Two, `2026_08_31_101600_game_name_rename` and
`2026_08_31_101700_aka_name_rename`.

### Acceptance

- `rg -n -w game_name` and `rg -n -w aka_name` over the five trees return
  nothing except the historical migrations.
- The game and AKA autocompletes on the game edit form and in the public nav
  still return and fill results — the `data-autocomplete-key` payload contract
  has no test and fails silently.
- `php artisan test` passes.
- `php artisan migrate:rollback --step=2` followed by `php artisan migrate`
  succeeds on the dev MariaDB with its data loaded.

## Unit 5 — the people and company columns

### The columns

Ten columns across three tables, measured 2026-08-31:

| Table | Column | Becomes | Token lines |
|---|---|---|---|
| `individuals` | `ind_name` | `name` | 168 |
| `individuals` | `ind_profile` | `profile` | 26 |
| `individuals` | `ind_imgext` | `imgext` | 17 |
| `individuals` | `ind_email` | `email` | 11 |
| `pub_devs` | `pub_dev_name` | `name` | 112 |
| `pub_devs` | `pub_dev_profile` | `profile` | 11 |
| `pub_devs` | `pub_dev_imgext` | `imgext` | 10 |
| `crews` | `crew_name` | `name` | 92 |
| `crews` | `crew_logo` | `logo` | 13 |
| `crews` | `crew_history` | `history` | 7 |

**Decision: the targets above.** The three `name` columns land on three
different tables, so no collision; a join of two of them qualifies.
`ind_email` becomes `email`, and `users.email` (Unit 11's table) is a different
table, so the pair is not a collision. These are the `ind_profile` /
`pub_dev_profile` prefixes the merge plan deferred. The `imgext` targets are
the schema's majority spelling for image extensions (`game_release_scans`,
`magazine_issues`, `media_scans`, `menu_disk_screenshots`, `screenshots`); the
campaign deliberately leaves the two remaining outliers,
`game_gallery.image_ext` and `users.avatar_ext`.

### The code

The `$fillable` entries at `app/Models/Individual.php:15`,
`app/Models/PubDev.php:15` and `app/Models/Crew.php:15`; the controller
assignment keys in `app/Http/Controllers/Admin/Games/GameIndividualController.php`
and `GameCompanyController.php` (which already read bare `$request->profile`,
`$request->email`, `$request->name`); the avatar and logo file paths, which key
on `ind_imgext` / `pub_dev_imgext` / `crew_logo` and move with the columns; the
`app/Http/Controllers/Ajax/` sites — `IndividualController.php`'s select,
`orderBy`, `where` and the explicit `'ind_name' =>` payload key at `:41`,
`CompanyController.php`'s `select('pub_devs.*')` and its `orderBy`/`where`, and
`CrewController.php`'s select, `orderBy` and `where`; the thirteen
`data-autocomplete-key` attributes (seven `ind_name`, five `pub_dev_name`, one
`crew_name`), which name the JSON payload property and move to `name`; the
already-qualified columns in `app/Helpers/AdminStatisticsHelper.php`
(`pub_devs.pub_dev_name` at `:323` and `:342`) **and the `pluck()` result keys
that read them back** — `topPublishers()` and `topDevelopers()` each end
`$rows->pluck('total', 'pub_dev_name')`, which names the select's output
column, not a table column, and silently yields an unlabelled chart axis if it
is left behind while the select moves; the three raw-SQL blocks in
`app/View/Components/Cards/Tops.php`, which no model mediates — `:28-36` is
`DB::table('pub_devs')`
with `selectRaw('count(game_id) as game_count, pub_dev_name, pub_devs.id')`, a
`where('pub_devs.pub_dev_name', …)`, a two-column `groupBy` and a bare
`orderBy('pub_dev_name')`; `:38-49` repeats the shape for publishers; `:60-67`
repeats it again for `individuals.ind_name`; the unqualified `pub_dev_name`
references at `app/Http/Controllers/GameSearchController.php:71,88`, which
become the qualified `pub_devs.name`; the Livewire columns in
`app/Livewire/Admin/Games/GameIndividualsTable.php`, `GameCompaniesTable.php`
and `CrewsTable.php`; the Blade reads; the factories and seeders; the test
assertions. The `individual_nicks` pivot and its `nick_id` / `individual_id`
keys are foreign keys and do not move.

### The migrations

Ten, one per column, `2026_08_31_101800` through `2026_08_31_102700`, in the
table's row order.

### Acceptance

- `rg -n -w` for each old token over the five trees returns nothing except the
  historical migrations.
- `php artisan test` passes.
- `php artisan migrate:rollback --step=10` followed by `php artisan migrate`
  succeeds on the dev MariaDB with its data loaded.

## Unit 6 — the link columns

### The columns

Ten columns across three tables, measured 2026-08-31:

| Table | Column | Becomes | Token lines |
|---|---|---|---|
| `websites` | `website_name` | `name` | 48 (shared with `website_validates`) |
| `websites` | `website_url` | `url` | 17 |
| `websites` | `website_date` | `date` | 8 |
| `websites` | `website_imgext` | `imgext` | 7 |
| `websites` | `website_count` | `count` | 1 |
| `website_categories` | `website_category_name` | `name` | 42 |
| `website_validates` | `website_name` | `name` | (in the 48) |
| `website_validates` | `website_url` | `url` | (in the 17) |
| `website_validates` | `website_date` | `date` | (in the 8) |
| `website_validates` | `website_description` | `description` | 1 |

**Decision: the targets above.** `websites` already holds an unprefixed
`description`, so `website_validates.website_description` becomes `description`
on its own table with no collision. `website_validates` holds 0 rows and is the
target of no foreign key; it moves for the name alone. `website_count` is a legacy
hit counter nothing reads; the dead-tables campaign examined it and kept it for
the data (95 distinct values, up to 7,405), so this unit renames rather than
drops on that decision — `count` is a weak name for what it holds, but a drop
is a dead-column decision, not a naming one.

### The code

The `$fillable` entries at `app/Models/Website.php:15-21`; the controller
assignment keys in `app/Http/Controllers/LinkController.php` and
`app/Http/Controllers/Admin/Links/`; the qualified
columns in `app/Livewire/Admin/LinksTable.php` — the `select('websites.*')` at
`:52` names the table, not a column, and stays, while the
`websites.website_name`, `websites.website_url` and `orderBy('website_name')`
at `:24-33` move — and in `LinkCategoriesTable.php`;
the Blade reads, including the `old('name', $link->website_name)` and
`old('name', $category->website_category_name)` fallbacks at
`resources/views/admin/links/links/card_edit.blade.php:24` and
`resources/views/admin/links/categories/card_edit.blade.php:28`; the
`WebsiteFactory` and `E2ESeeder` keys; the test assertions. The campaign's only
console-command site is here: `app/Console/Commands/CheckLinks.php` sorts on
`sortBy('website_name')` at `:51`, interpolates `$website->website_name` and
`$website->website_url` into its progress line at `:54`, and fetches
`$website->website_url` at `:58`. `websites.rate_number`, `rate_score` and
`inactive` are unprefixed and stay.

### The migrations

Ten, one per column, `2026_08_31_102800` through `2026_08_31_103700`, in the
table's row order.

### Acceptance

- `rg -n -w` for each old token over the five trees returns nothing except the
  historical migrations.
- `php artisan test` passes.
- `php artisan migrate:rollback --step=10` followed by `php artisan migrate`
  succeeds on the dev MariaDB with its data loaded.

## Unit 7 — the news columns

### The columns

Eight columns across the three news tables, measured 2026-08-31:

| Table | Column | Becomes | Token lines |
|---|---|---|---|
| `news` | `news_headline` | `headline` | 58 |
| `news` | `news_text` | `text` | 20 |
| `news` | `news_date` | `date` | 33 |
| `news_submissions` | `news_headline` | `headline` | (shared) |
| `news_submissions` | `news_text` | `text` | (shared) |
| `news_submissions` | `news_date` | `date` | (shared) |
| `news_images` | `news_image_name` | `name` | 0 |
| `news_images` | `news_image_ext` | `imgext` | 5 |

**Decision: the targets above.** The two tables mirror each other and move
together, so a submission keeps the same column names as the news row it
becomes. The date columns stay `int(11)` unix timestamps; only the names move.
`news_images` is the news row's image record: `news_image_name` is a legacy
filename nothing reads (the dead-tables campaign kept it for the data), and
`news_image_ext` becomes `imgext`, the schema's majority spelling.
`news.news_image_id` is a foreign key and does not move.

### The code

The `$fillable` and `$casts` entries at `app/Models/News.php:17-22`
(`'news_date' => 'datetime:timestamp'` becomes `'date' => …`); the controller
assignment keys at `app/Http/Controllers/Admin/News/NewsController.php:63-66`
and `:89-92` and the public `NewsController`; the changelog `section_name`
reads, which use `$news->news_headline`; the Livewire columns in
`app/Livewire/Admin/NewsTable.php` and `NewsSubmissionsTable.php`; the Blade
reads; the `NewsFactory` and `E2ESeeder` keys; the test assertions.
`news_submissions` adds four of its own: the `$casts` entry
`'news_date' => 'datetime:timestamp'` at `app/Models/NewsSubmission.php:15`, the
three column keys in `database/factories/NewsSubmissionFactory.php:26-30`, and
the assignment keys at `:27-30` plus the changelog `section_name` /
`sub_section_name` reads at `:37,40,56,59` of
`app/Http/Controllers/Admin/News/NewsSubmissionsController.php`.
`news_images` adds the `NewsImage` model's `$fillable` and `file` accessor
(`app/Models/NewsImage.php:12,16`), the `NewsTable` sortable at
`app/Livewire/Admin/NewsTable.php:38`, the `NewsController` write at
`app/Http/Controllers/Admin/News/NewsController.php:171`, and the Blade read at
`resources/views/news/card_list.blade.php:23`. The `orderByDesc('news_date')`
at `app/Helpers/FeedHelper.php:14` moves with `news_date`, as does the
`News::orderByDesc('news_date')` at `app/Http/Controllers/HomeController.php:14`
and the `DB::table('news')->pluck('news_date')` in
`AdminStatisticsHelper::contentByYear()` at `:421`. The
`news_submissions.news_image_id` sentinel the sweep deferred is not a foreign
key — `news_submissions` carries no foreign-key constraints at all, and the
column is an `int(11) NOT NULL DEFAULT 0` — so it keeps its `_id` name and is
not touched.

### The migrations

Eight, one per column, `2026_08_31_103800` through `2026_08_31_104500`, the
three `news` columns, the three `news_submissions` columns and the two
`news_images` columns.

### Acceptance

- `rg -n -w` for each old token over the five trees returns nothing except the
  historical migrations.
- `php artisan test` passes.
- `php artisan migrate:rollback --step=8` followed by `php artisan migrate`
  succeeds on the dev MariaDB with its data loaded.

## Unit 8 — the article columns

### The columns

Four columns, measured 2026-08-31:

| Column | Becomes | Token lines |
|---|---|---|
| `articles.article_title` | `title` | 41 |
| `articles.article_text` | `text` | 10 |
| `articles.article_date` | `date` | 24 |
| `articles.article_intro` | `intro` | 12 |

**Decision: the targets above.** These are the `article_text` prefixes the merge
plan deferred; the merge landed them on `articles` as `article_*` and this unit
strips the stem. The date column stays `int(11)`.

### The code

The `$fillable` and `$casts` entries at `app/Models/Article.php:17-23`
(`'article_date' => 'datetime:timestamp'` becomes `'date' => …`); the controller
assignment keys at `app/Http/Controllers/Admin/Articles/ArticleController.php:69-72`
and `:98-101`; `Article::toFeedItem()`, which reads `$this->article_title`
and becomes `$this->title`; the `orderByDesc('article_date')` at
`app/Helpers/FeedHelper.php:25`; the Livewire columns in
`app/Livewire/Admin/ArticlesTable.php`, including the date formatter that reads
`$row->article_date` and becomes `$row->date`; the Blade reads; the
`ArticleFactory` and `E2ESeeder` keys; the test assertions. The public side
adds three: the two `orderByDesc('article_date')` calls at
`app/Http/Controllers/ArticleController.php:17,36` and the
`$article->article_date->format('Y-m-d')` behind the `datePublished` schema.org
key at `:43`, where the key itself is not a column and stays. So does
`DB::table('articles')->pluck('article_date')` in
`AdminStatisticsHelper::contentByYear()` at `:424`.
`articles.article_type_id` is a foreign key and does not move.

### The migrations

Four, one per column, `2026_08_31_104600` through `2026_08_31_104900`.

### Acceptance

- `rg -n -w` for each old token over the five trees returns nothing except the
  historical migrations.
- `php artisan test` passes, including the `AdminTablesTest` date assertion the
  merge plan added for `ArticlesTable`.
- `php artisan migrate:rollback --step=4` followed by `php artisan migrate`
  succeeds on the dev MariaDB with its data loaded.

## Unit 9 — the interview columns

### The columns

Four columns, measured 2026-08-31:

| Column | Becomes | Token lines |
|---|---|---|
| `interviews.interview_text` | `text` | 13 |
| `interviews.interview_date` | `date` | 23 |
| `interviews.interview_intro` | `intro` | 13 |
| `interviews.interview_chapters` | `chapters` | 10 |

**Decision: the targets above.** The date column stays `int(11)`;
`interview_text` stays `text` type. The `interviews.individual_id` and
`user_id` foreign keys do not move.

### The code

The `$fillable` and `$casts` entries at `app/Models/Interview.php:17-23`
(`'interview_date' => 'datetime:timestamp'` becomes `'date' => …`); the
controller assignment keys in
`app/Http/Controllers/Admin/Interviews/InterviewsController.php`;
`Interview::toFeedItem()`, which reads `$this->individual->ind_name` (Unit 5's
column) and is unaffected by this unit; the `orderByDesc('interview_date')` at
`app/Helpers/FeedHelper.php:21`; the Livewire columns in
`app/Livewire/Admin/InterviewsTable.php`; the Blade reads, including the
`[hotspot=…]` chapter rendering that reads `interview_chapters`; the
`InterviewFactory` and `E2ESeeder` keys; the test assertions. The public side
adds the two `orderByDesc('interview_date')` calls at
`app/Http/Controllers/InterviewController.php:17,26` and the
`$interview->interview_date->format('Y-m-d')` behind the `datePublished` key at
`:33`, and `AdminStatisticsHelper::contentByYear()` plucks
`interviews.interview_date` at `:423`.

### The migrations

Four, one per column, `2026_08_31_105000` through `2026_08_31_105300`.

### Acceptance

- `rg -n -w` for each old token over the five trees returns nothing except the
  historical migrations.
- `php artisan test` passes.
- `php artisan migrate:rollback --step=4` followed by `php artisan migrate`
  succeeds on the dev MariaDB with its data loaded.

## Unit 10 — the review columns

### The columns

Seven columns, measured 2026-08-31:

| Column | Becomes | Token lines |
|---|---|---|
| `reviews.review_text` | `text` | 19 |
| `reviews.review_date` | `date` | 34 |
| `reviews.review_edit` | `edit` | 21 |
| `reviews.review_graphics` | `graphics` | 15 |
| `reviews.review_sound` | `sound` | 9 |
| `reviews.review_gameplay` | `gameplay` | 8 |
| `reviews.review_overall` | `overall` | 9 |

**Decision: the targets above.** The four score columns keep their words and
lose the stem; they stay nullable `int(11)`, the state the merge plan's
decision 2 preserved. The date column stays `int(11)`.

### The code

The `$fillable` and `$casts` entries at `app/Models/Review.php:20-26`
(`'review_date' => 'datetime:timestamp'` becomes `'date' => …`); the controller
assignment keys in `app/Http/Controllers/Admin/Reviews/ReviewsController.php`
and the public `ReviewController`, whose `submit()` writes the four score
columns; the `where('review_edit', …)` and `orderByDesc('review_date')` at
`app/Helpers/FeedHelper.php:16-17`; the `@isset($review->review_graphics)` guard in
`resources/views/reviews/card_review.blade.php`, which becomes
`@isset($review->graphics)` and keeps its comment that one column stands for
four; the Livewire columns in `app/Livewire/Admin/ReviewsTable.php`; the
`ReviewFactory::scored()` state; the test assertions. Two more
`review_date` orderings sit outside the review controllers —
`app/Http/Controllers/SitemapController.php:23` and
`app/View/Components/Cards/Reviews.php:28` — and
`AdminStatisticsHelper::contentByYear()` plucks the column at `:422`. The
`reviews.user_id` foreign key does not move.

### The migrations

Seven, one per column, `2026_08_31_105400` through `2026_08_31_106000`.

### Acceptance

- `rg -n -w` for each old token over the five trees returns nothing except the
  historical migrations.
- `php artisan test` passes, including
  `tests/Feature/Admin/Reviews/ReviewsControllerTest.php` and
  `tests/Feature/Public/ReviewPagesTest.php`.
- `php artisan migrate:rollback --step=7` followed by `php artisan migrate`
  succeeds on the dev MariaDB with its data loaded.

## Unit 11 — the user-profile social columns

### The columns

Four columns, measured 2026-08-31:

| Column | Becomes | Token lines |
|---|---|---|
| `users.user_website` | `website` | 13 |
| `users.user_fb` | `facebook` | 11 |
| `users.user_twitter` | `twitter` | 11 |
| `users.user_af` | `atari_forum` | 11 |

**Decision: the targets above.** These are the profile's social links; the
controllers already read bare `$request->website`, `$request->facebook`,
`$request->twitter`, `$request->af` at
`app/Http/Controllers/Auth/UserController.php:30-33`, and the form fields are
`name="website"`, `name="facebook"`, `name="twitter"`, `name="af"` — so the
request keys and the form fields do not move, only the column side changes.
`facebook` and `atari_forum` are the self-documenting words for what the
columns hold; bare `fb` and `af` would not say. `users.userid` is the login
identifier and is not a prefixed social column; it stays, and is the subject of
a separate decision, recorded under Out of scope.

### The code

The `$fillable` entries at `app/Models/User.php:29`; the controller assignment
keys at `app/Http/Controllers/Auth/UserController.php:30-33`; the Blade reads
at `resources/views/auth/card_profile.blade.php:57,71,85,99` and the profile
display sites (`resources/views/components/cards/partial_comment.blade.php:59-69`);
the `UserFactory` and `E2ESeeder` keys; the test assertions. The `users.user_id`
foreign keys on other tables are a different token and do not move.

### The migrations

Four, one per column, `2026_08_31_106100` through `2026_08_31_106400`.

### Acceptance

- `rg -n -w` for each old token over the five trees returns nothing except the
  historical migrations.
- The named-column census under "Verification" returns no row for this unit's
  four columns.
- `php artisan test` passes.
- `php artisan migrate:rollback --step=4` followed by `php artisan migrate`
  succeeds on the dev MariaDB with its data loaded.

## Verification

Run after every unit, on the dev MariaDB with its data loaded:

```
php artisan test
php artisan migrate:rollback --step=N && php artisan migrate
```

N is the unit's migration count. Run once at the end, against dev:

```sql
SELECT COUNT(*) FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'atarilegend' AND TABLE_TYPE = 'BASE TABLE';
```

It returns 119: the campaign renames columns only and creates or drops none.

The primary-key census, which must return only the two composite pivots:

```sql
SELECT s.TABLE_NAME, GROUP_CONCAT(s.COLUMN_NAME ORDER BY s.SEQ_IN_INDEX) AS pk
FROM   information_schema.STATISTICS s
WHERE  s.TABLE_SCHEMA = DATABASE() AND s.INDEX_NAME = 'PRIMARY'
GROUP BY s.TABLE_NAME
HAVING pk <> 'id'
ORDER BY s.TABLE_NAME;
```

returns `crew_menu_set` (`crew_id,menu_set_id`) and `game_sndh`
(`game_id,sndh_id`) and nothing else.

The named-column census, which asserts that none of the 65 old column names
survives. It returns zero rows when the campaign is complete:

```sql
SELECT TABLE_NAME, COLUMN_NAME
FROM   information_schema.COLUMNS
WHERE  TABLE_SCHEMA = DATABASE()
AND    (TABLE_NAME, COLUMN_NAME) IN (
  ('game_gallery','game_gallery_id'),       ('database_change','database_change_id'),
  ('database_change','database_change_script'),
  ('game_similar','game_similar_cross'),    ('screenshot_game_submitinfo','game_submitinfo_id'),
  ('website_validates','website_category'),
  ('spotlights','spotlight'),               ('trivia_quotes','trivia_quote'),
  ('trivia','trivia_text'),                 ('game_facts','game_fact'),
  ('article_types','article_type'),         ('comments','comment'),
  ('game_submit_infos','submit_text'),
  ('screenshot_article_comments','comment_text'),
  ('screenshot_interview_comments','comment_text'),
  ('screenshot_review_comments','comment_text'),
  ('games','game_name'),                    ('game_akas','aka_name'),
  ('individuals','ind_name'),               ('individuals','ind_profile'),
  ('individuals','ind_imgext'),             ('individuals','ind_email'),
  ('pub_devs','pub_dev_name'),              ('pub_devs','pub_dev_profile'),
  ('pub_devs','pub_dev_imgext'),            ('crews','crew_name'),
  ('crews','crew_logo'),                    ('crews','crew_history'),
  ('websites','website_name'),              ('websites','website_url'),
  ('websites','website_date'),              ('websites','website_imgext'),
  ('websites','website_count'),             ('website_categories','website_category_name'),
  ('website_validates','website_name'),     ('website_validates','website_url'),
  ('website_validates','website_date'),     ('website_validates','website_description'),
  ('news','news_headline'),                 ('news','news_text'),
  ('news','news_date'),                     ('news_submissions','news_headline'),
  ('news_submissions','news_text'),         ('news_submissions','news_date'),
  ('news_images','news_image_name'),        ('news_images','news_image_ext'),
  ('articles','article_title'),             ('articles','article_text'),
  ('articles','article_date'),              ('articles','article_intro'),
  ('interviews','interview_text'),          ('interviews','interview_date'),
  ('interviews','interview_intro'),         ('interviews','interview_chapters'),
  ('reviews','review_text'),                ('reviews','review_date'),
  ('reviews','review_edit'),                ('reviews','review_graphics'),
  ('reviews','review_sound'),               ('reviews','review_gameplay'),
  ('reviews','review_overall'),             ('users','user_website'),
  ('users','user_fb'),                      ('users','user_twitter'),
  ('users','user_af')
);
```

The census names the old columns, so it is the schema-side half of every unit's
gate; the code-side half is each unit's `rg -n -w '<old>'` returning nothing.

The stem census, the first of the two the hardcoded list above is checked
against. It cannot see a column whose stem is shorter than its table's
(`news_submissions.news_headline`), abbreviated (`individuals.ind_name`),
whose table ends in `-es` (`website_validates.website_name`), or whose stem is
not the column's first word (`game_akas.aka_name`), so on its own it is the
check for the front-anchored self-named columns only:

```sql
SELECT TABLE_NAME, COLUMN_NAME
FROM   information_schema.COLUMNS
WHERE  TABLE_SCHEMA = DATABASE()
AND    ( COLUMN_NAME LIKE CONCAT(TABLE_NAME, '\_%')
   OR COLUMN_NAME LIKE CONCAT(TRIM(TRAILING 's' FROM TABLE_NAME), '\_%')
   OR COLUMN_NAME = TABLE_NAME
   OR COLUMN_NAME = TRIM(TRAILING 's' FROM TABLE_NAME) );
```

At the end of the campaign it returns only the eleven foreign-key columns
whose referenced table shares the owning table's stem
(`articles.article_type_id`, `games.game_progress_system_id`,
`games.game_series_id`, `media.media_type_id`, `media_scans.media_scan_type_id`,
`menus.menu_set_id`, `menu_disks.menu_disk_condition_id`,
`menu_disks.menu_disk_dump_id`,
`menu_software.menu_software_content_type_id`, `news.news_image_id`,
`sndhs.sndh_archive_id`) — governed by the foreign-key rule, not this campaign
— and the framework's own `migrations.migration`.

The word census, the second and the real completeness check. It compares every
underscore-delimited word of the column against every word of the table, each
side also tried with a trailing `s` trimmed, and drops the `_id` columns the
foreign-key rule governs:

```sql
SELECT TABLE_NAME, COLUMN_NAME
FROM   information_schema.COLUMNS
WHERE  TABLE_SCHEMA = DATABASE()
AND    COLUMN_NAME <> 'id'
AND    COLUMN_NAME NOT LIKE '%\_id'
AND    ( CONCAT('_', TABLE_NAME, '_')
           LIKE CONCAT('%\_', SUBSTRING_INDEX(COLUMN_NAME, '_',  1), '\_%')
      OR CONCAT('_', TABLE_NAME, '_')
           LIKE CONCAT('%\_', SUBSTRING_INDEX(COLUMN_NAME, '_', -1), '\_%')
      OR REPLACE(CONCAT('_', TABLE_NAME, '_'), 's_', '_')
           LIKE CONCAT('%\_', SUBSTRING_INDEX(COLUMN_NAME, '_',  1), '\_%')
      OR REPLACE(CONCAT('_', TABLE_NAME, '_'), 's_', '_')
           LIKE CONCAT('%\_', SUBSTRING_INDEX(COLUMN_NAME, '_', -1), '\_%') )
ORDER BY TABLE_NAME, COLUMN_NAME;
```

It returned 62 rows on 2026-08-31 and must return exactly these six when the
campaign is complete:

| Row | Why it stays |
|---|---|
| `game_facts.fact` | the table is named for the column; `fact` is the bare content word Unit 3 chose |
| `trivia_quotes.quote` | the same, for Unit 3's `quote` |
| `game_gallery.game_description_gallery` | Out of scope: no model, no code references |
| `game_submit_infos.game_done` | Out of scope: a submission-state flag, not a name echo |
| `magazine_issues.issue` | Out of scope: the issue number, examined and left |
| `migrations.migration` | the framework's own |

The first two are the census over-matching by construction: a table named for
the thing it holds will always share a word with the column that holds it, and
Unit 3's decision is that the bare content word is the right name anyway. The
census still cannot see an abbreviation — the four `individuals.ind_*` columns
never appear in either query, and are in the campaign on the hand application
alone.

`migrate:fresh` on the e2e database, then `E2ESeeder`, is run once at the end:
the historical migrations keep the old column names and run in date order
against the schema of their day, and this campaign's migrations run after them,
so a fresh build must land on the de-prefixed schema.

## Deploying

A `renameColumn` is reversible by its `down()`, so the dump rule is the plural
campaign's: take a `mysqldump` before the first unit and keep it until the last
is deployed, because a rollback of one unit leaves the schema half-renamed and a
dump is the only way back to the pre-campaign state in one step.

Deploy the units one at a time. `.github/workflows/build-and-deploy.yml` runs
`migrate:rollback --step=1` on rollback; a unit is several migrations, so the
deploy's revert command for a unit is `migrate:rollback --step=N` with the unit's
N, and the workflow's `--step=1` reverts only the newest migration of a
multi-migration unit. Push one unit commit per deploy, as the plural campaign
did, so a bad deploy reverses without touching the others. The migration
timestamps are numbered in unit order and column order within a unit, so a batch
rollback runs the units' `down()`s in reverse unit order; the order is not
load-bearing, because no unit's rename depends on another's.

## Out of scope

### The four role-qualified foreign keys

A foreign key that names a role — the nicked individual, the donor, the Atari
side, the parent crew — is named for the role, because the bare referenced-table
name is either taken by a sibling column or would not say which side the key is.
Examined and deliberately left:

| Column | Table | References | Role |
|---|---|---|---|
| `nick_id` | `individual_nicks` | `individuals.id` | the nicked individual, beside `individual_id` |
| `donated_by_individual_id` | `menu_disks` | `individuals.id` | the donor |
| `atari_id` | `game_vs` | `games.id` | the Atari side, beside the external `amiga_id` |
| `parent_id` | `sub_crew` | `crews.id` | the parent crew, beside `crew_id` |

`game_vs.amiga_id` is an external LemonAmiga id, not a foreign key, and its
"orphans" are the expected state; it is not a naming finding.

### `users.userid`

The login identifier and display name, `varchar(255)`, spelled without the
underscore every other `user_` token in the schema carries. It is the
`Auth::username` field (`app/Http/Controllers/Auth/LoginController.php:44`) and
167 lines name it across the five trees, 46 of them in `app`. It is a wrong
name — it reads as a foreign key to `users` while being the username — but it
is the custom authentication column the campaign excludes, and renaming the
login field is a decision about the auth path, not a prefix strip. Recorded so the next audit does not re-derive it.

### The singular entity tables

`game_series`, `sound_hardware`, `trivia`, `media` and `game_vs` are singular
because their models derive the singular name (`Str::pluralStudly` leaves a word
that already ends in `s` or is uncountable alone); they carry no `protected
$table` override and are consistent with their models. `andreas` is a
proper-noun guestbook table, consistent with its `Andreas` model. Renaming any
of them is a table-name decision the plural campaign's "tables keep their words"
rule does not reach, and is not a column finding.

### The remaining self-named columns

The stem census's four findings and the word census's six are folded into the
campaign, bar three. `comments.comment` went to Unit 3,
`news_images.news_image_name` and `news_images.news_image_ext` to Unit 7,
`database_change.database_change_script` to Unit 1;
`game_submit_infos.submit_text` and the three `screenshot_*_comments.comment_text`
went to Unit 3 and `game_akas.aka_name` to Unit 4. Three columns are examined
and left, and they are the whole of what the word census returns at the end
beyond the two content words Unit 3 chose:

**`game_gallery.game_description_gallery`** — a suffix case the stem census
cannot reach. The table has no model and no code references, so renaming it is
a one-migration tidy-up with no code side at all. It is left because it belongs
to a kept-but-dead table the dead-tables campaign already ruled on, and folding
it in would be the campaign's only rename that nothing reads either before or
after.

**`magazine_issues.issue`** — an `int(11)` issue number on a 69-row table.
It is the same shape as `spotlights.spotlight` on the census, but not the same
finding: `spotlight` and `comment` moved because the content word (`text`) says
what the column holds and the table name did not, whereas `issue` already is
the word for what this column holds. The rename that would clear the census is
`issue` → `number`, and that is a decision about whether `$issue->number` reads
better than `$issue->issue`, not a prefix strip. **Decision: left, recorded so
the next audit does not re-derive it.**

**`game_submit_infos.game_done`** — a `char(1)` flag saying which part of a
submission has been actioned. It shares the word `game` with its table because
the table is about games, not because the column echoes the table's name. It is
census noise, not a finding.

`andreas.comment` shares its name with `comments.comment` but is not a
self-named column at all (`comment` is not a word of `andreas`); it is the
proper-noun guestbook's content column and does not move.

### The pivot and join tables

The 33 singular pivot and join tables (`game_release_crew`, `screenshot_game`,
`crew_individual`, …) — the schema's 38 tables whose names do not end in `s`,
less the five that hold entities or records (`database_change`, `game_gallery`,
`media`, `sound_hardware`, `trivia`), or 34 counting `game_vs`, which the `s`
test does not catch — are consistently singular by the plural
campaign's "no model, named by the relation" decision. They are internally
consistent and are not a finding. The two composite pivots keep their composite
primary keys by the schema consistency sweep's standing exception.

### The stale constraint and index names

Unit 2's two foreign-key renames leave their constraint and index names naming
the old column (`game_similar_game_similar_cross_foreign`,
`screenshot_game_submitinfo_game_submitinfo_id_foreign`). The schema consistency
sweep's "Index and constraint names" records the standing decision to leave such
names: nothing at runtime reads either, and the literal form is used if a
`dropForeign` is ever needed.

### The date and enum columns

The `int(11)` unix-timestamp date columns (`news_date` → `date` and the rest)
keep their type; this campaign renames them and does not convert them to
`datetime`. The `enum` columns (`dumps.format`, `games.multiplayer_type`,
`game_releases.license`, …) are untouched.

### The historical migrations

Every migration dated before this campaign names the columns as they were on its
day, and all of them run before this campaign's migrations on a `migrate:fresh`.
They are correct as written and must not be touched; the rule the merge campaign
set — no migration added after the renames may name the old columns — applies to
this campaign's files and to anything that follows them.
