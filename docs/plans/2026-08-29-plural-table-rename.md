# Pluralising the table names

*2026-08-29*

Successor to `2026-08-24-main-text-table-merge.md`, which pluralised the four
`_main` tables and deleted four `protected $table` overrides, and to
`2026-08-28-dead-tables-and-columns.md`, which dropped thirteen tables and left
the schema at 119. This plan pluralises the remaining forty-six singular or
legacy-named tables, and renames eight model classes, so that the `protected
$table` overrides in the model classes become redundant and can be deleted. It
is written to be read alongside
the three previous campaigns and does not repeat what they establish about
deploy ordering, `RENAME TABLE` on MariaDB, or the strictness guards in
`AppServiceProvider`.

**Executed 2026-08-30**, all eight units, in plan order, one commit each. Four
things departed from the text and each is recorded in place below: the two open
decisions the plan declined to settle were settled the rule-consistent way
(`trainer_options` and `pub_devs`, not `trainers` and `publisher_developers`),
which leaves nothing in Out of scope; Unit 7's premise was wrong about how a
`Pivot` subclass derives its table and the unit lost its migration and gained
two override deletions; and one test that re-runs a historical migration out of
order had to move. The end state is better than the plan's: **fifty-four of the
fifty-six overrides are deleted, not fifty-two**, and forty-three tables moved,
not forty-six. The schema is at 119 tables on both lineages, and the full
PHPUnit suite passes at 1010.

### The two open decisions, settled

Units 3 and 4 each named a target that drops or replaces a word, and the plan
deliberately left both to the reader. Both were settled with nicolas on
2026-08-30, and both went the way Unit 2's rule points:

| Table | Plan proposed | Executed | Model |
|---|---|---|---|
| `trainer_option` | `trainers` | `trainer_options` | `Trainer` → `TrainerOption` |
| `pub_dev` | `publisher_developers` | `pub_devs` | `PublisherDeveloper` → `PubDev` |

The consequence is that **"The three foreign keys that stop matching the rule"
under Out of scope is down to one**. `trainer_option_id` and the three
`pub_dev_id` columns keep matching, because their parents kept their words;
only `game_submitinfo_id` on `screenshot_game_submitinfo` is left, and it is
left for the reason Unit 2 gives. Six `TABLE, NOT MODEL` entries in
`RelationshipKeyConventionsTest::DECLINED` were deleted rather than reworded,
and five relations shed key arguments the audit now derives:
`GameRelease::trainers()`, `Game::developers()`, `GameRelease::distributors()`,
`PubDev::games()` and `PubDev::releases()`.

One of Unit 4's five did not close, and the plan was wrong to expect it to.
`GameRelease::publisher()` is a `belongsTo`, and a `belongsTo` derives its key
from the **method** name, not the related class — so the convention is
`publisher_id` whatever the model is called. Its entry stays, with that as the
reason.

### Unit 7 had the mechanism backwards

The unit renamed `screenshot_article`, `screenshot_interview` and
`screenshot_review` to their plurals in order to delete three overrides. It
does the opposite. All three models extend `Pivot`, and **a `Pivot` subclass
does not derive its table with `pluralStudly`**: `AsPivot::getTable()` is
`Str::snake(Str::singular(class_basename($this)))`
(`vendor/laravel/framework/src/Illuminate/Database/Eloquent/Relations/Concerns/AsPivot.php:167`).
`ScreenshotArticle` derives `screenshot_article`, so renaming the table to the
plural makes the override *permanent*. Four tests failed on `no such table:
screenshot_article` and the migration was withdrawn.

The corrected unit renames nothing and carries no migration. All five pivot
tables already hold exactly the name their class derives, so **all five
overrides are redundant and all five are deleted** — including
`GameDeveloper` and `GameIndividual`, which "The two pivots that stay" and the
end-state paragraph both expected to keep theirs forever. That section and Out
of scope's "The two kept pivot overrides" are superseded: the reasoning in them
about `joiningTable()` deriving `game_individual` was the tell, since it is the
same singular convention seen from the relation's side.

Two consequences elsewhere in this document. **The end state is fifty-four of
fifty-six**, and the only survivors are `GameReleaseMemoryEnhanced` and
`GameReleaseSystemEnhanced`. And **the derivation check under Verification is
wrong as written** — it compares every model against `pluralStudly`, which no
`Pivot` subclass has ever used, so it reports the five pivots as findings. The
check has to branch:

```
$derived = $m instanceof Illuminate\Database\Eloquent\Relations\Pivot
    ? Str::snake(Str::singular($base))
    : Str::snake(Str::pluralStudly($base));
```

Run that way it prints exactly the two kept models and nothing else.

### One test runs a historical migration out of order

`FixMenuSoftwareChangelogSectionTest` requires
`2026_08_10_120000_fix_menu_software_changelog_section.php` by path and calls
`up()` on it, so Unit 8's `change_log` → `changelogs` broke it. The rule that
historical migrations are not touched still holds and the migration is right as
written: on a real `migrate` it runs months before the rename, against the
schema of its day. The test is what moved — it renames the table back for the
duration of the call and restores it in a `finally`. It is the only place in
the campaign where a historical migration is executed outside its ordering, and
worth knowing about before the next rename campaign.

### The rename helper was extracted

The plan has each of seven migrations repeat
`2026_08_25_100300_rename_main_tables`'s private `rename()`. That is eighty
lines seven times, so it became `Database\Support\TableRenamer`
(`database/support/`, registered in `composer.json`'s psr-4 map). It adds two
rules the original did not need, both found by running it:

- **only a Laravel-derived name is rewritten**, matched as
  `{table}_{columns}_{index|unique|foreign|primary}`. A blind prefix swap is
  safe for `article_main` and not for `game`, which carries legacy indexes
  named for their *column* — `game_progress_system_id`, `game_series_id`,
  `port_id`. Those are left, exactly as the merge campaign left its four, and
  MariaDB renames its own `*_ibfk_*` constraints with the table anyway;
- **a rewritten name over 64 characters is skipped**, that being MariaDB's
  identifier limit. It happens once, on
  `game_release_tos_version_incompatibility_game_release_id_foreign`, whose
  pluralised form is 66. Nothing reachable is lost: Laravel would derive that
  same 66-character name for a later `dropForeign()`, so no `dropForeign()` on
  that column can work either way.

### Smaller notes

- **Forty-three tables moved, not forty-six**, Unit 7's three having stayed.
  The 119-table count is unchanged, as the plan says it must be, and the
  foreign-key count held at 142 through every unit.
- **The seven migrations the plan predicted are six**, for the same reason;
  `ls database/migrations | tail -6` is the check, not `tail -7`. They are
  numbered `2026_08_30_1000NN` in unit order, Unit 1 carrying none.
- A reference shape the plan's own commands do not find: a table name qualifying
  a column *inside* raw SQL, where there is no leading quote to anchor on.
  `Tops.php:53`'s `selectRaw('count(game_id) as game_count, game_genre.name,
  game_genre.id')` is the one that got through, and it surfaced as three failing
  tests rather than as a grep hit. The same file's `selectRaw` for companies had
  the identical shape and was caught only because `pub_dev` was swept with a
  bare `pub_dev\.` pattern as well. A
  `(selectRaw|DB::raw|whereRaw|orderByRaw|havingRaw|groupByRaw)` sweep for a
  bare `table.` finds both, and belongs in "How the inventory was obtained".
- `migrate:fresh` plus `E2ESeeder` on the e2e database lands on the pluralised
  schema at 119 tables with no old name present, as required.

**The delivery unit is one commit per unit, based on `development`, not one
pull request.** Every unit except the first carries one migration, so reversing
a deployed unit is `migrate:rollback --step=1`, and reverting the commit removes
that one migration file.

**The end state:** fifty-two of the fifty-six `protected $table` overrides in
`app/Models` are deleted, because the forty-six tables they point at are
renamed to the plural snake_case name Eloquent derives from the model class —
and, where the table name is the one the schema is already built around and the
class name is the half that diverges, because the class is renamed to derive
the table. The four that remain — `GameReleaseMemoryEnhanced`,
`GameReleaseSystemEnhanced`, `GameDeveloper` and `GameIndividual` — are named
in Units 2 and 7: the first two because no class name derives a usable version
of their table, the last two because their overrides are inert and their tables
are the pivot names the relations already carry.

**Tables keep their words; classes move.** Unit 2 states the rule and the
precedent behind it. A table name is normalised for case and plural — no word
added, none dropped — and a model class whose name does not derive that table
is renamed until it does. `ReleaseAka` on `game_release_aka` becomes
`GameReleaseAka` on `game_release_akas`, not `ReleaseAka` on `release_akas`.
Units 3 and 4 still name two targets that drop or replace a word
(`trainer_option` → `trainers`, `pub_dev` → `publisher_developers`); both are
flagged in their units and in Out of scope as open decisions. Unit 6's three
`*_comments` tables are a deliberate exception, argued there.

| Unit | Tables renamed | Overrides deleted | Reference lines |
|---|---|---|---|
| 1 — the redundant overrides | 0 | 5 | 0 |
| 2 — games and releases | 10 | 11 | 85 + 134 |
| 3 — the game reference tables | 16 | 16 | 31 |
| 4 — companies and individuals | 4 | 4 | 48 |
| 5 — the website tables | 3 | 3 | 18 |
| 6 — media, screenshots and comments | 8 | 8 | 12 |
| 7 — the three screenshot pivots | 3 | 3 | 12 |
| 8 — `change_log` and `news_submission` | 2 | 2 | 27 |
| **Total** | **46** | **52** | **231 + 134** |

The reference-lines column counts the longhand references each unit must
rewrite, measured 2026-08-30 with the commands in "How the inventory was
obtained", and the second figure in Unit 2's cell counts the class-reference
lines its eight model renames rewrite, which the other units do not have. Two
lines — `app/Helpers/AdminStatisticsHelper.php:322` and
`app/View/Components/Cards/Tops.php:39` — name a table from both Unit 2 and
Unit 4, and are counted once in the total. The lines that name the four kept
tables are not in any unit's count, because those tables do not move.

**Dependency order.** The units are independent of each other. `RENAME TABLE`
rewrites the referenced name in a foreign key, so a child's constraint follows
its parent's rename in either order — the merge campaign verified this on
`article_main` → `articles`. The cross-unit foreign keys inside this campaign
(`game.port_id` → `port`, `game_release.pub_dev_id` → `pub_dev`, and the three
comment tables' keys to the screenshot pivots) rewrite in either order for the
same reason.

## How the inventory was obtained

Not by reading the models. A script booted the framework's string helpers and,
for every file in `app/Models`, compared the table the model declares against
the table Eloquent would derive:

```
php -r 'require "vendor/autoload.php"; use Illuminate\Support\Str;
foreach (glob("app/Models/*.php") as $f) {
  $class = basename($f, ".php");
  $derived = Str::snake(Str::pluralStudly($class));
  // ...read `protected $table` from the file and compare...
}'
```

`Model::getTable()` is `Str::snake(Str::pluralStudly(class_basename($this)))`
(`vendor/laravel/framework/src/Illuminate/Database/Eloquent/Model.php:1892`),
which is the rule the twenty-seven models that already carry no `protected
$table` demonstrate — `User` → `users`, `Menu` → `menus`, `SndhArchive` →
`sndh_archives`. The comparison, run 2026-08-30, classifies the fifty-six
models that do carry the override into three groups:

- **five are already redundant** — the declared table is character-for-character
  the derived one, so the line can be deleted with no schema change;
- **forty-seven declare a table that differs from the derived one** — forty-five
  of them are renamed in this campaign, and two (`GameDeveloper`,
  `GameIndividual`) are kept for the reason in Unit 7;
- **four derive a name that is not usable** — the pluraliser mangles an
  abbreviation or a word that already ends in a plural-looking suffix.

The last group is where the class-rename rule pays: the comparison was run
again on the class names the campaign moves to, and two of the four
(`TOS` → `Tos`, `ReleaseTOSIncompatibility` →
`GameReleaseTosVersionIncompatibility`) derive a usable table once the class is
named for the table rather than the other way round. Only
`ReleaseMemoryEnhanced` and `ReleaseSystemEnhanced` survive it, because no
class name pluralises `enhanced` into anything a schema should hold. Unit 2
carries the detail.

The schema facts came from `information_schema` on the dev MariaDB 10.11,
measured 2026-08-30:

```sql
SELECT COUNT(*) FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'atarilegend' AND TABLE_TYPE = 'BASE TABLE';
-- 119

SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'atarilegend' AND REFERENCED_TABLE_NAME IS NOT NULL;
-- 142

SELECT COUNT(*) FROM information_schema.VIEWS WHERE TABLE_SCHEMA = 'atarilegend';
SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = 'atarilegend';
SELECT COUNT(*) FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = 'atarilegend';
SELECT COUNT(*) FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = 'atarilegend' AND EXTRA LIKE '%generated%';
-- 0, 0, 0, 0: nothing outside the foreign-key graph depends on a table name

SELECT COUNT(DISTINCT REFERENCED_TABLE_NAME) FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'atarilegend' AND REFERENCED_TABLE_NAME IS NOT NULL
  AND REFERENCED_TABLE_NAME IN ( /* the forty-six renamed tables */ );
-- 33: the rest move for the override alone

SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'atarilegend'
  AND TABLE_NAME IN ( /* the forty-six target names */ );
-- 0: no target name is already taken, so no rename is blocked
```

The reference lines were counted with two commands, per table, over `app`,
`resources`, `tests`, `database/factories` and `database/seeders`, excluding
`database/migrations`:

```
rg -n -- "(DB::table|Schema::table|->from|->join|->leftJoin|->rightJoin|->crossJoin)\('T'\)|(exists|unique):T\b|Rule::(unique|exists)\('T'\)|assertDatabase(Has|Missing|Count)\('T'|insert\('T',|['\"]T\.[a-zA-Z_*]|(selectRaw|DB::raw)\(['\"][^'\"]*T\." app resources tests database/factories database/seeders
```

```
rg -n -- "['\"]T['\"]" app resources tests database/factories database/seeders
```

The first names the shapes a table reference takes: a query-builder table
argument, a validation rule, a `Rule::` call, a database assertion, the
seeder's `insert()`, a qualified column, and a qualified column inside a
raw-SQL string. The second is the bare-name sweep; a hit is a table reference
only when the name is consumed as a table — the values of
`GameConfigurationController::CONFIG_TYPES_TABLES`
(`app/Http/Controllers/Admin/Games/GameConfigurationController.php:18`), the
arguments to `AdminStatisticsHelper::groupByColumn()` and `::countWithText()`,
and the pivot-table argument of a `belongsToMany`. Every other bare hit is a
route parameter, a view key or name, a screenshot folder name, a form or
request field, a relation name, a BBCode tag name, or a JS fixture property,
and stays.

A `T.column` hit needs one more test, because some are relation paths, not
qualified columns. It is a table reference when the string is passed to a query
builder that builds SQL — a `select`, `where`, `orderBy` or `groupBy` argument,
a `join` argument, a `selectRaw` or `DB::raw` string, or the closure of a
`whereHas` — and it is a relation path when it is passed to a collection
method. Nine hits are not references at all and survive the campaign; they are
the only hits the first command returns when it is done:

| Line | Hit | Why it stays |
|---|---|---|
| `app/Livewire/Admin/MagazineIndex.php:122` | `sortBy(['page', 'game.game_name'])` | relation path |
| `resources/views/magazines/card_issues.blade.php:74` | `sortBy(['page', 'game.game_name'])` | relation path |
| `resources/views/components/cards/latest-magazine-issues.blade.php:37` | `sortBy('game.game_name')` | relation path |
| `tests/Feature/Admin/Games/Releases/ReleaseSystemTest.php:187` | `firstWhere('system.name', 'STE')` | relation path |
| `tests/Feature/Admin/Games/Releases/ReleaseSystemTest.php:194` | `pluck('system.name')` | relation path |
| `tests/Feature/Admin/Games/Releases/ReleaseSystemTest.php:363` | `firstWhere('memory.name', '1 MB')` | relation path |
| `tests/Feature/Admin/Games/Releases/ReleaseSystemTest.php:371` | `pluck('memory.name')` | relation path |
| `tests/Feature/Admin/Menus/MenuDisksTest.php:49` | `'dump.zip'` | a file name |
| `tests/e2e/admin-write/others.spec.js:86` | `'spotlight.png'` | a file name |

Measured 2026-08-30, the forty-six moving tables are named in 231 reference
lines. The eight class renames in Unit 2 are counted separately, with the
command in that unit, because a class reference is not a table reference and
the two commands above do not find it. A migration naming a table is not a reader, which is why the historical
migrations are excluded and must not be touched.

## Unit 1 — the five redundant overrides

### The overrides

Five models declare a `protected $table` that is exactly what Eloquent derives,
so the line is redundant. Measured 2026-08-30 with the script above:

| Model | Declared table | Derived table |
|---|---|---|
| `Comment` | `comments` | `comments` |
| `GameVs` | `game_vs` | `game_vs` |
| `Individual` | `individuals` | `individuals` |
| `Media` | `media` | `media` |
| `SoundHardware` | `sound_hardware` | `sound_hardware` |

`GameVs`, `Media` and `SoundHardware` look surprising only because the pluraliser
leaves a word alone when it already ends in `s` or when the stem does not match a
plural rule: `Str::plural('GameVs')` is `GameVs`, `Str::plural('Media')` is
`Media`, `Str::plural('SoundHardware')` is `SoundHardware`. The declared table
and the derived one agree in all five, which is the whole of the finding.

### The change

**Delete the five lines; rename nothing.** There is no migration, no schema
change and no production risk. The five lines are
`app/Models/Comment.php:18`, `app/Models/GameVs.php:9`,
`app/Models/Individual.php:13`, `app/Models/Media.php:12` and
`app/Models/SoundHardware.php:9`.

The commit also corrects `CLAUDE.md:41`, which names three tables that no longer
exist — `review_main`, `interview_main` and `article_main` are `reviews`,
`interviews` and `articles`. The line names no table this campaign moves, so it
is fixed here, in the first commit.

This unit establishes the shape of the other seven: a model file edited, the
suite green, and a grep that proves the override is gone.

### Acceptance

- `grep -rn "protected \$table" app/Models/Comment.php app/Models/GameVs.php
  app/Models/Individual.php app/Models/Media.php app/Models/SoundHardware.php`
  returns nothing.
- `php artisan test` passes.
- No migration file is added; `git show --stat` names the five model files and
  `CLAUDE.md`.

## Unit 2 — games and releases

### The rule this unit follows

**A table name keeps its words; a model class is renamed to derive it.** Where
the table name and the model class name disagree, the table is the one the
schema, the foreign-key columns and the pivots are already named for, so the
table keeps every word it has — case and plural are normalised, nothing is
added and nothing is dropped — and the model class moves to whatever name
derives it. `ReleaseAka` on `game_release_aka` becomes `GameReleaseAka` on
`game_release_akas`, not `ReleaseAka` on `release_akas`.

That is not a new rule for this repo. The foreign-key campaign already renamed
`Release` to `GameRelease` for exactly this reason, and
`RelationshipKeyConventionsTest`'s docblock records it — "Renaming the model
would close these, and did for Release -> GameRelease where it was load
bearing; the rest are optional tidy-ups"
(`tests/Feature/RelationshipKeyConventionsTest.php:35`). This unit takes the
optional tidy-ups.

It buys three things a table rename alone does not:

- the three foreign keys that would have stopped matching the campaign's rule
  (*foreign key = singularised referenced-table name + `_id`*) never stop
  matching it, because the referenced table keeps its words — `game_genre_id`
  still singularises `game_genres`, and `game_progress_system_id` still
  singularises `game_progress_systems`. The partial reversal of the
  foreign-key campaign that a `game_genre` → `genres` rename would have forced
  does not happen;
- two of the four overrides that were going to be kept forever are closed
  instead, because the model class was the unusable half, not the table:
  `TOS` → `Tos` derives `tos` with no schema change at all, and
  `ReleaseTOSIncompatibility` → `GameReleaseTosVersionIncompatibility` derives
  the plural of the table it already has;
- one `belongsToMany` stops diverging and sheds two arguments.

### The tables

Ten tables move. The mapping, measured 2026-08-30:

| Model today | Model becomes | Table today | Table becomes |
|---|---|---|---|
| `Game` | — | `game` | `games` |
| `GameAka` | — | `game_aka` | `game_akas` |
| `GameFact` | — | `game_fact` | `game_facts` |
| `GameRelease` | — | `game_release` | `game_releases` |
| `GameSubmitInfo` | — | `game_submitinfo` | `game_submit_infos` |
| `ReleaseAka` | `GameReleaseAka` | `game_release_aka` | `game_release_akas` |
| `ReleaseScan` | `GameReleaseScan` | `game_release_scan` | `game_release_scans` |
| `Genre` | `GameGenre` | `game_genre` | `game_genres` |
| `ProgressSystem` | `GameProgressSystem` | `game_progress_system` | `game_progress_systems` |
| `ReleaseTOSIncompatibility` | `GameReleaseTosVersionIncompatibility` | `game_release_tos_version_incompatibility` | `game_release_tos_version_incompatibilities` |

`game_submitinfo` → `game_submit_infos` is the one target that is not the old
name with a plural suffix, and it is still the same words: `submitinfo` was
never snake-cased, and `Str::snake('GameSubmitInfo')` splits it. The
alternative that would have preserved the string exactly — table
`game_submitinfos`, model `GameSubmitinfo` — buys a matching
`game_submitinfo_id` foreign key at the price of a class name with a lowercase
`i` in the middle of a word, and is declined. The consequence is the single
foreign key recorded below.

`game` and `game_release` are the two heaviest foreign-key parents in the
schema: together they are the target of forty-one of the schema's one hundred
and forty-two declared foreign keys, measured 2026-08-30 with

```sql
SELECT REFERENCED_TABLE_NAME, COUNT(*) FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'atarilegend' AND REFERENCED_TABLE_NAME IN ('game','game_release')
GROUP BY REFERENCED_TABLE_NAME;
```

`RENAME TABLE` rewrites every one of those referenced names, so no child table
is touched.

### The model classes

Eight classes are renamed. Six of them make an override deletable; two are
renamed for consistency alone and keep their override. Measured 2026-08-30 —
the line counts are class references (`use App\Models\X`, `X::`, `X $`,
`@extends`), not table references, and they include the model file itself and
its factory:

| Class today | Becomes | Derives | Lines |
|---|---|---|---|
| `ReleaseAka` | `GameReleaseAka` | `game_release_akas` | 10 |
| `ReleaseScan` | `GameReleaseScan` | `game_release_scans` | 43 |
| `Genre` | `GameGenre` | `game_genres` | 36 |
| `ProgressSystem` | `GameProgressSystem` | `game_progress_systems` | 3 |
| `TOS` | `Tos` | `tos` — unchanged | 10 |
| `ReleaseTOSIncompatibility` | `GameReleaseTosVersionIncompatibility` | `game_release_tos_version_incompatibilities` | 10 |
| `ReleaseMemoryEnhanced` | `GameReleaseMemoryEnhanced` | nothing usable; override stays | 11 |
| `ReleaseSystemEnhanced` | `GameReleaseSystemEnhanced` | nothing usable; override stays | 11 |

`Str::snake(Str::pluralStudly('Tos'))` is `tos`, so the `TOS` → `Tos` rename
deletes `app/Models/TOS.php:12` and touches no table: the pluraliser leaves
`Tos` alone and the snake-caser has one word to split, where `TOS` was split
letter by letter into `t_o_s`. Three files are renamed alongside their class —
`app/Models/*.php` for all eight, plus `database/factories/GenreFactory.php` →
`GameGenreFactory.php`, `ReleaseScanFactory.php` → `GameReleaseScanFactory.php`
and `TOSFactory.php` → `TosFactory.php`, because `HasFactory` resolves the
factory from the model's class name and no model in this repo overrides
`newFactory()`.

The last two are the judgement call in this unit: renaming them deletes no
override, because `Str::snake(Str::pluralStudly('GameReleaseMemoryEnhanced'))`
is `game_release_memory_enhanceds` either way. They are renamed so that the
release family reads consistently — `GameReleaseAka`, `GameReleaseScan`,
`GameReleaseTosVersionIncompatibility`, `GameReleaseMemoryEnhanced`,
`GameReleaseSystemEnhanced` — rather than leaving two `Release`-prefixed
classes behind as the only survivors of the old naming. Twenty-two lines, and
no schema change. Drop these two rows if the unit needs to be smaller; nothing
else in the plan depends on them.

### The two overrides that stay

**`ReleaseMemoryEnhanced` and `ReleaseSystemEnhanced` — `GameReleaseMemoryEnhanced`
and `GameReleaseSystemEnhanced` after this unit — keep their `protected $table`,
and their tables are not renamed.** No class name derives their table, because
the last word is a past participle and the pluraliser suffixes it:

| Model | Table today | Eloquent would derive |
|---|---|---|
| `GameReleaseMemoryEnhanced` | `game_release_memory_enhanced` | `game_release_memory_enhanceds` |
| `GameReleaseSystemEnhanced` | `game_release_system_enhanced` | `game_release_system_enhanceds` |

`game_release_memory_enhanceds` is not a name a schema should carry, and the
only way out is renaming the concept — `game_release_memory_enhancements` with
a `GameReleaseMemoryEnhancement` model, which drops a word from the table name
and is outside the rule this unit follows. The two lines stay at
`app/Models/GameReleaseMemoryEnhanced.php:9` and
`app/Models/GameReleaseSystemEnhanced.php:9`, and the end state's
"fifty-two of fifty-six" counts them.

### The one foreign key that stops matching the rule

The foreign-key campaign's standing rule is *foreign key = singularised
referenced-table name + `_id`*. Under the tables-keep-their-words rule, exactly
one column stops matching it, and **this unit renames the table and leaves the
column; the rename is recorded in Out of scope rather than executed here.**

| Column | Table | Named for | Becomes inconsistent with |
|---|---|---|---|
| `game_submitinfo_id` | `screenshot_game_submitinfo` | `game_submitinfo` | `game_submit_infos` → `game_submit_info_id` |

The pivot table `screenshot_game_submitinfo` is named for the old table too,
and it has no model, so it is not in this campaign's scope either — see "The
pivot tables with no model". Renaming the column and the pivot together is one
follow-up, priced in Out of scope.

The two columns an earlier draft of this plan had to break —
`game_progress_system_id` on `game`, and `game_genre_id` on `game_genre_cross`,
both of them deliberate moves of the foreign-key campaign — keep matching the
rule, because their parents keep their words. This unit no longer reverses any
part of that campaign.

### The migration

One migration, `pluralise_game_tables`. For each of the ten tables it runs
`Schema::rename($old, $new)`, and then renames the table's own foreign-key
constraints and named indexes from the old table prefix to the new one, reading
the names out of `information_schema` rather than hard-coding them — the
pattern in
`database/migrations/2026_08_23_200500_individual_foreign_keys.php`. The merge
campaign established why the second half is needed: `RENAME TABLE` rewrites the
referenced name in the foreign keys that point *at* the renamed table, but the
renamed table keeps its own constraint and index names, and those are named for
the old table. That is cosmetic until a later migration writes
`dropForeign(['column'])` on the renamed table, at which point Laravel derives
the new prefix, does not find it, and fails with SQLSTATE 42000 / 1091.

`tos` is not in the migration. The `TOS` → `Tos` rename is a class rename with
no schema change, which is the whole of why it was worth doing.

The `information_schema` half is skipped on SQLite, where `php artisan test`
builds the schema in-memory and the view does not exist; the guard is on
`!== 'sqlite'`, never on `=== 'mysql'`, because the driver name is `mariadb` —
the same guard as the cited pattern.

`down()` reverses both halves: the constraint and index names back first, then
the ten `Schema::rename($new, $old)`.

### The code

- The eight classes are renamed, each with its file, and the three factories
  above are renamed with theirs. The 134 class-reference lines counted in "The
  model classes" are rewritten.
- Eleven `protected $table` lines are deleted, from `app/Models/Game.php:13`,
  `GameAka.php:9`, `GameFact.php:9`, `GameRelease.php:29`,
  `GameSubmitInfo.php:12`, `TOS.php:12`, and the five renamed files
  `GameReleaseAka.php`, `GameReleaseScan.php`, `GameGenre.php`,
  `GameProgressSystem.php` and `GameReleaseTosVersionIncompatibility.php`.
- `Game::genres()` sheds two arguments. It reads
  `belongsToMany(Genre::class, 'game_genre_cross', 'game_id', 'game_genre_id')`
  at `app/Models/Game.php:119`, and becomes
  `belongsToMany(GameGenre::class, 'game_genre_cross')`: `RelationshipKeyAudit`
  derives a `belongsToMany`'s keys from the two class names
  (`app/Helpers/RelationshipKeyAudit.php:61`), so once the related model is
  `GameGenre` the derived pair is `game_id|game_genre_id` — what the arguments
  already say. **The pivot-table argument stays**; the derived pivot name is
  alphabetical and is not `game_genre_cross`. Leaving the two key arguments in
  place fails `test_no_relation_passes_a_key_argument_it_would_derive_anyway`,
  so this edit is not optional.
- The 85 table-reference lines are rewritten to the new names. They are the
  `DB::table()` counts in `AdminStatisticsHelper` and `StatisticsHelper`, the
  joins and qualified selects in the game controllers and Livewire tables, the
  `exists:game,id` rule in `Admin/Reviews/ReviewsController`, the
  `Rule::unique('game')` in `Admin/Games/GameController`, the inserts in
  `E2ESeeder`, and the test fixtures. The commands in "How the inventory was
  obtained" are the list; re-run them per table rather than working from a list
  in this document, because a list in a document becomes outdated and the
  commands do not.
- `CLAUDE.md:37` is updated to the three new names it carries.
- `RelationshipKeyConventionsTest::DECLINED` changes in three ways, and all
  three are forced by the test's own assertions:
  - `'Game::genres()' => 'table game_genre, model Genre'` is **deleted**. The
    relation stops diverging the moment `Genre` becomes `GameGenre`, and the
    test fails on a declared exception that no longer diverges.
  - `'ReleaseAka::release()'` is **relabelled** `'GameReleaseAka::release()'`;
    the label is `class_basename($model) . '::' . $method->name . '()'`
    (`app/Helpers/RelationshipKeyAudit.php:65`), so a class rename moves it.
    The reason text — "gameRelease() would close it; ->release is 77 lines" —
    is unchanged, because a `belongsTo` derives its key from the method name.
  - `'GameSubmitInfo::screenshots()'` **keeps its entry**, with the reason
    updated from "table game_submitinfo, model GameSubmitInfo" to "column
    game_submitinfo_id is named for game_submitinfo, now game_submit_infos".
    It is the one relation this unit leaves divergent, and it is the code half
    of the foreign key recorded above.

  The five `TABLE, NOT MODEL` entries for `pub_dev` and the one for
  `trainer_option` are untouched here; Units 3 and 4 own them.

### Acceptance

- `SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA =
  'atarilegend' AND TABLE_NAME IN ('game','game_aka','game_fact','game_release',
  'game_release_aka','game_release_scan','game_submitinfo','game_genre',
  'game_progress_system','game_release_tos_version_incompatibility')` returns 0,
  and the same query over the ten new names returns 10.
- `ls app/Models | grep -E '^(ReleaseAka|ReleaseScan|Genre|ProgressSystem|TOS|ReleaseTOSIncompatibility|ReleaseMemoryEnhanced|ReleaseSystemEnhanced)\.php$'`
  returns nothing, and `rg -l 'App\\Models\\(ReleaseAka|ReleaseScan|Genre|ProgressSystem|TOS|ReleaseTOSIncompatibility|ReleaseMemoryEnhanced|ReleaseSystemEnhanced)\b'`
  over `app`, `resources`, `tests`, `database` and `routes` returns nothing.
- `grep -rn "protected \$table"` over the eleven files above returns nothing,
  and over `GameReleaseMemoryEnhanced.php` and `GameReleaseSystemEnhanced.php`
  returns their two lines.
- The reference command in "How the inventory was obtained" returns nothing for
  the ten old table names, except the three `game.game_name` relation paths
  listed there.
- `php artisan al:audit-relationship-keys --pivots` reports `game_genre_cross`
  for `Game::genres()`, as it did before the arguments were shed. Snapshot it
  before and after; the campaign that wrote this command records that deleting
  a key argument one position too far turns `game_release_crew` into
  `crew_release` with nothing failing until the relation is exercised.
- `php artisan test` passes, including both assertions of
  `RelationshipKeyConventionsTest`.
- `php artisan migrate:rollback --step=1` followed by `php artisan migrate`
  succeeds on the dev MariaDB with its data loaded.

### If the unit is too large

It is 85 table-reference lines, 134 class-reference lines, ten renames and
eleven files at once. It splits cleanly in two along the schema boundary, at
the cost of one extra commit:

1. **the tables** — the migration, the ten renames, the 85 table-reference
   lines, and the eleven `protected $table` lines *updated to the new table
   names* rather than deleted;
2. **the classes** — the eight class renames, the three factory renames, the
   134 class-reference lines, the `Game::genres()` arguments, the `DECLINED`
   edits, and the eleven `protected $table` lines deleted.

The second commit carries no migration, so it has nothing to roll back, and the
first is a schema change whose `down()` is exercised in isolation. Take the
split if review or deploy pressure warrants it; the acceptance list above is
the union of the two.

## Unit 3 — the game reference tables

### The tables

Sixteen lookup tables move. The mapping, measured 2026-08-30:

| Model | Today | Becomes |
|---|---|---|
| `Control` | `control` | `controls` |
| `CopyProtection` | `copy_protection` | `copy_protections` |
| `DiskProtection` | `disk_protection` | `disk_protections` |
| `Emulator` | `emulator` | `emulators` |
| `Engine` | `engine` | `engines` |
| `Enhancement` | `enhancement` | `enhancements` |
| `Language` | `language` | `languages` |
| `Location` | `location` | `locations` |
| `Memory` | `memory` | `memories` |
| `Port` | `port` | `ports` |
| `Resolution` | `resolution` | `resolutions` |
| `System` | `system` | `systems` |
| `Trainer` | `trainer_option` | `trainers` |
| `ArticleType` | `article_type` | `article_types` |
| `NewsImage` | `news_image` | `news_images` |
| `ProgrammingLanguage` | `programming_language` | `programming_languages` |

All sixteen are foreign-key parents — `control`, `copy_protection`,
`disk_protection`, `emulator`, `engine`, `enhancement`, `language`, `location`,
`memory`, `port`, `resolution`, `system`, `trainer_option`, `article_type`,
`news_image` and `programming_language` are each the target of at least one
constraint, measured 2026-08-30 — so `RENAME TABLE` rewrites the children's
referenced names and no child is touched. `Str::plural('Memory')` is `Memories`,
which is why `memory` becomes `memories` rather than `memorys`.

**`trainer_option` → `trainers` is the one row here that breaks Unit 2's rule**,
because it drops a word: the table's words say `trainer_options` and the model
is what would move, to `TrainerOption`. That would also close the foreign key
below and the `GameRelease::trainers()` entry in
`RelationshipKeyConventionsTest`. The row is left as written and the choice is
recorded in Out of scope, so that the unit is a decision the reader makes
rather than one this plan makes for them.

### The one foreign key that stops matching the rule

`game_release_trainer_option.trainer_option_id` names `trainer_option`; when the
parent becomes `trainers`, the rule now gives `trainer_id`. **The column stays, and
the rename is recorded in Out of scope**, for the reason stated in Unit 2.

### The migration and the code

One migration, `pluralise_game_reference_tables`, with the same two halves as
Unit 2's: the sixteen `Schema::rename()` calls, then the information_schema
rename of each table's own constraints and indexes, and a `down()` that
reverses both. The sixteen `protected $table` lines are deleted from the models
named in the table, and the 31 reference lines are rewritten with the commands
in "How the inventory was obtained" — the seven `exists:` rules in the five
`ReleaseSystem*Controller` files, the twelve `CONFIG_TYPES_TABLES` values in
`GameConfigurationController`, the `DB::table()` calls in
`GameConfigurationTest`, and the seeder's inserts. `GameRelease::trainers()`
keeps its explicit `'trainer_option_id'` argument: the column does not move in
this campaign, and the argument is what the `DECLINED` list already records.

`RelationshipKeyConventionsTest::DECLINED` carries one reason string this unit
moves: `GameRelease::trainers()` says "table `trainer_option`" and becomes
"table `trainers`" (`tests/Feature/RelationshipKeyConventionsTest.php:64`). The
entry itself does not move, for the reason stated in Unit 2.

### Acceptance

- The old-name count query over the sixteen names returns 0; the new-name query
  returns 16.
- `grep -rn "protected \$table"` over the sixteen model files returns nothing.
- The reference command in "How the inventory was obtained" returns nothing for
  the sixteen old names, except the two `system.name` and two `memory.name`
  relation paths listed there.
- `php artisan test` passes; `migrate:rollback --step=1` and `migrate` succeed.

## Unit 4 — companies and individuals

### The tables

Four tables move. The mapping, measured 2026-08-30:

| Model | Today | Becomes |
|---|---|---|
| `DeveloperRole` | `developer_role` | `developer_roles` |
| `IndividualRole` | `individual_role` | `individual_roles` |
| `PublisherDeveloper` | `pub_dev` | `publisher_developers` |
| `Crew` | `crew` | `crews` |

`pub_dev` and `crew` are foreign-key parents — `pub_dev` is the target of three
constraints (`game_developer`, `game_release`, `game_release_distributor`) and
`crew` of five (`crew_individual`, `crew_menu_set`, `game_release_crew`,
`sub_crew` twice), measured 2026-08-30 — so `RENAME TABLE` rewrites the
children's referenced names.

### The three foreign keys that stop matching the rule

`pub_dev_id` appears on three tables — `game_developer`, `game_release` and
`game_release_distributor` — and names `pub_dev`. When the parent becomes
`publisher_developers`, the rule now gives `publisher_developer_id`. **The three
columns stay, and the rename is recorded in Out of scope.** This is the largest
of the deferred renames at three tables, and it is the one that most directly
reverses the foreign-key campaign's `dev_pub_id` → `pub_dev_id` move.

`pub_dev` → `publisher_developers` breaks Unit 2's rule, because it replaces
the table's words rather than normalising them. The alternative that keeps them
— `pub_devs`, with the `PublisherDeveloper` model renamed to `PubDev` — closes
all three columns and all five `TABLE, NOT MODEL` entries for `pub_dev`, at the
price of carrying an abbreviation into the class names. Unlike Unit 2's cases,
this one trades an abbreviation for the expansion rather than a word order, so
the rule does not settle it; the choice is recorded in Out of scope.

### The migration and the code

One migration, `pluralise_company_tables`, with the same two halves. The four
`protected $table` lines are deleted from `DeveloperRole.php:9`,
`IndividualRole.php:9`, `PublisherDeveloper.php:13` and `Crew.php:13`, and the
48 reference lines are rewritten — the `Rule::unique('pub_dev', ...)` calls in
`GameCompanyController`, the `pub_dev.id` qualified columns in
`GameSearchController`, `Tops` and `AdminStatisticsHelper`, the
`select('pub_dev.*')` in `GameCompaniesTable` and `Ajax/CompanyController`, the
`developer_role` and `individual_role` `DB::table()` calls in the test fixtures
and the two `select()` calls in `GameCreditsController`, the
`select('crew.*')` in `CrewsTable`, and the seeder's insert.
`PublisherDeveloper::games()` passes the pivot table
`'game_developer'` explicitly, and that argument stays: the pivot is kept, per
Unit 7.

`CLAUDE.md:42` is updated to the two new names it carries, and its stale
`genre` corrected to `game_genre` in the same edit.

`RelationshipKeyConventionsTest::DECLINED` carries five reason strings this
unit moves — `Game::developers()`, `GameRelease::publisher()`,
`GameRelease::distributors()`, `PublisherDeveloper::games()` and
`PublisherDeveloper::releases()`, all "table `pub_dev`, model
`PublisherDeveloper`" (`tests/Feature/RelationshipKeyConventionsTest.php:58-62`)
— and each becomes "table `publisher_developers`, model `PublisherDeveloper`".
The entries themselves do not move, for the reason stated in Unit 2.

### Acceptance

- The old-name count query over the four names returns 0; the new-name query
  returns 4.
- `grep -rn "protected \$table"` over the four model files returns nothing.
- The reference command in "How the inventory was obtained" returns nothing for
  the four old names.
- `php artisan test` passes; `migrate:rollback --step=1` and `migrate` succeed.

## Unit 5 — the website tables

### The tables

Three tables move. The mapping, measured 2026-08-30:

| Model | Today | Becomes |
|---|---|---|
| `Website` | `website` | `websites` |
| `WebsiteCategory` | `website_category` | `website_categories` |
| `WebsiteValidate` | `website_validate` | `website_validates` |

`website` and `website_category` are foreign-key parents — `website_category_cross`
carries one constraint to each, measured 2026-08-30 — so `RENAME TABLE`
rewrites the cross table's referenced names. `website_validate` holds zero rows
and is the target of no foreign key; it moves for the override alone.

### The migration and the code

One migration, `pluralise_website_tables`, with the same two halves. The three
`protected $table` lines are deleted from `Website.php:13`,
`WebsiteCategory.php:12` and `WebsiteValidate.php:9`, and the 18 reference lines
are rewritten — the `DB::table()` counts in `StatisticsHelper` and
`AdminStatisticsHelper`, the `website.website_*` qualified columns and
`select('website.*')` in `LinksTable` and `LinkController`, the
`select('website_category.*')` in `LinkCategoriesTable` and `LinkController`,
the `unique:website_category` and `exists:website_category` rules in the two
link controllers, and the seeder's insert.

### Acceptance

- The old-name count query over the three names returns 0; the new-name query
  returns 3.
- `grep -rn "protected \$table"` over the three model files returns nothing.
- The reference command in "How the inventory was obtained" returns nothing for
  the three old names.
- `php artisan test` passes; `migrate:rollback --step=1` and `migrate` succeed.

## Unit 6 — media, screenshots and comments

### The tables

Eight tables move. The mapping, measured 2026-08-30:

| Model | Today | Becomes |
|---|---|---|
| `MediaScan` | `media_scan` | `media_scans` |
| `MediaScanType` | `media_scan_type` | `media_scan_types` |
| `MediaType` | `media_type` | `media_types` |
| `Dump` | `dump` | `dumps` |
| `Spotlight` | `spotlight` | `spotlights` |
| `ScreenshotArticleComment` | `article_comments` | `screenshot_article_comments` |
| `ScreenshotInterviewComment` | `interview_comments` | `screenshot_interview_comments` |
| `ScreenshotReviewComment` | `review_comments` | `screenshot_review_comments` |

`media_scan_type` and `media_type` are foreign-key parents — `media_scan` points
at both, measured 2026-08-30 — so `RENAME TABLE` rewrites the child's referenced
names. `dump` is the target of no constraint but carries two of its own, to
`media` and `users`, and those survive the rename. The three comment tables are
the interesting case: their current names (`article_comments`,
`interview_comments`, `review_comments`) describe the *content* they comment on,
while their models (`ScreenshotArticleComment` and the rest) describe the
*screenshot* they comment on. The rename makes the table agree with the model,
which is the campaign's rule; the content-side name is what the model was always
saying it was not. Each comment table also carries a `screenshot_*_id` key to
the pivot table Unit 7 renames, and `RENAME TABLE` rewrites those referenced
names in either order.

These three are the one place in the campaign where the model is deliberately
treated as more right than the table, which is the reverse of Unit 2's rule.
The rule's alternative — keep `article_comments` and rename the model to
`ArticleComment` — is wrong here: the model is a comment on a
`screenshot_article` row, not on an article, and `ArticleComment` would state
something false. Recorded in Out of scope alongside the other two, as a
deliberate exception rather than an oversight.

### The migration and the code

One migration, `pluralise_media_tables`, with the same two halves. The eight
`protected $table` lines are deleted from `MediaScan.php:13`,
`MediaScanType.php:14`, `MediaType.php:12`, `Dump.php:17`, `Spotlight.php:12`,
`ScreenshotArticleComment.php:9`, `ScreenshotInterviewComment.php:9` and
`ScreenshotReviewComment.php:9`, and the 12 reference lines are rewritten — the
`DB::table()` counts in `AdminStatisticsHelper`, the
`groupByColumn('dump', 'format')` call, the `select('spotlight.*')` in
`SpotlightsTable`, and the seeder's three inserts. The three comment tables are
reached in the code through their models, not by name, so the seeder's inserts
are their only longhand references.

`CLAUDE.md:38` is updated to the three new names it carries — `dump`,
`media_scan` and `game_release_scan` — and its stale `screenshot_main`
corrected to `screenshots` in the same edit; `CLAUDE.md:158` is updated to the
two new comment-table names.

### Acceptance

- The old-name count query over the eight names returns 0; the new-name query
  returns 8.
- `grep -rn "protected \$table"` over the eight model files returns nothing.
- The reference command in "How the inventory was obtained" returns nothing for
  the eight old names, except the `'dump.zip'` and `'spotlight.png'` file names
  listed there.
- `php artisan test` passes; `migrate:rollback --step=1` and `migrate` succeed.

## Unit 7 — the three screenshot pivots

### The tables

Three tables move, and they are the only unit in this campaign where the rename
touches a relation's argument as well as a model's override. The mapping,
measured 2026-08-30:

| Model | Today | Becomes | Relation that names it |
|---|---|---|---|
| `ScreenshotArticle` | `screenshot_article` | `screenshot_articles` | `Article::screenshots()` |
| `ScreenshotInterview` | `screenshot_interview` | `screenshot_interviews` | `Interview::screenshots()` |
| `ScreenshotReview` | `screenshot_review` | `screenshot_reviews` | `Review::screenshots()` |

All three models extend `Pivot`, and all three relations already pass the pivot
table as an explicit second argument — `Article.php:33`, `Interview.php:38` and
`Review.php:41` — so the rename is a one-word change per argument. The
`->withPivot('id')` and `->using()` modifiers that follow each of them name a
column and a model class, not the table, and are untouched. Each table is the
target of one foreign key — the `screenshot_*_id` key on the comment table Unit
6 renames — and `RENAME TABLE` rewrites those referenced names.

### The two pivots that stay

**`game_developer` and `game_individual` are not renamed, and `GameDeveloper`
and `GameIndividual` keep their `protected $table` lines.** The three Screenshot*
models are used standalone — `ScreenshotArticle::findOrFail` at
`app/Http/Controllers/Admin/Articles/ArticleController.php:196` and
`ScreenshotInterview::findOrFail` at
`app/Http/Controllers/Admin/Interviews/InterviewsController.php:191` — so their
overrides are load-bearing and the rename deletes them. The other two are never
used standalone: the four relations that use them pass the pivot table as an
explicit argument, and the relation injects that table into the pivot model it
builds (`vendor/laravel/framework/src/Illuminate/Database/Eloquent/Relations/
Concerns/InteractsWithPivotTable.php:523` passes `$this->table` to the pivot),
so their overrides are already inert. Keeping the tables also keeps the schema
on the convention Eloquent itself uses for pivot names: `game_individual` is
exactly the name `HasRelationships::joiningTable()` derives for `Game` and
`Individual` — the alphabetical snake_case join of the class basenames
(`vendor/laravel/framework/src/Illuminate/Database/Eloquent/Concerns/
HasRelationships.php:750`) — and `RelationshipKeyConventionsTest`'s own message
says the derived pivot name "is right for `game_individual`"
(`tests/Feature/RelationshipKeyConventionsTest.php:119`). Renaming it would move
the table off that convention, and the explicit argument the relations already
carry is what keeps the code and the schema in agreement. `game_developer` is
not the derived name for `Game` and `PublisherDeveloper` — that would be
`game_publisher_developer` — and `game_developers` is not either, so renaming
it buys nothing.

### The migration and the code

One migration, `pluralise_screenshot_pivot_tables`, with the same two halves.
The three `protected $table` lines are deleted from `ScreenshotArticle.php:9`,
`ScreenshotInterview.php:9` and `ScreenshotReview.php:9`. The three explicit
pivot arguments are updated — `Article.php:33`, `Interview.php:38` and
`Review.php:41` — and the 12 reference lines are rewritten: the seeder's three
inserts, the three `DB::table()` counts in `AdminStatisticsHelper`, the two
`DB::table()` calls in the review controllers, and the two test assertions.

`al:audit-relationship-keys --pivots` is the gate that differs from the other
units. The foreign-key campaign used it to prove a no-op by diffing the resolved
pivot table of all fifty-one `belongsToMany` relations before and after; here
the diff is *expected* to be non-empty, and it must name exactly the three
renamed pivots and nothing else. Snapshot before, snapshot after, and the diff
is three lines:

```
< Article::screenshots()                       screenshot_article
> Article::screenshots()                       screenshot_articles
```

### Acceptance

- The old-name count query over the three names returns 0; the new-name query
  returns 3.
- `grep -rn "protected \$table"` over `ScreenshotArticle.php`,
  `ScreenshotInterview.php` and `ScreenshotReview.php` returns nothing, and the
  same grep over `GameDeveloper.php` and `GameIndividual.php` returns their two
  lines.
- The pivot snapshot diff names exactly the three renamed pivots.
- `php artisan test` passes, including `RelationshipKeyConventionsTest`;
  `migrate:rollback --step=1` and `migrate` succeed.

## Unit 8 — `change_log` and `news_submission`

### The tables

Two tables move. The mapping, measured 2026-08-30:

| Model | Today | Becomes |
|---|---|---|
| `Changelog` | `change_log` | `changelogs` |
| `NewsSubmission` | `news_submission` | `news_submissions` |

Neither is the target of a foreign key, measured 2026-08-30, so each rename
touches only its own table. `change_log` is the largest of the forty-six —
61,796 rows, measured 2026-08-30 — and `RENAME TABLE` is a metadata operation,
so the size is not a risk. `Str::plural('Changelog')` is `Changelogs`, which is
why the target is `changelogs` rather than `change_logs`.

### The migration and the code

One migration, `pluralise_changelog_and_news_submission`, with the same two
halves. The two `protected $table` lines are deleted from `Changelog.php:16`
and `NewsSubmission.php:12`, and the 27 reference lines are rewritten — the
`change_log` references are the `DB::table()` calls and the qualified columns
in `ChangelogTable`, `ChangelogController`, `GameSearchController` and
`AdminStatisticsHelper`, plus the `FixMenuSoftwareChangelogSectionTest`
fixtures, and the `news_submission` references are the count in
`AdminStatisticsHelper` and the `select('news_submission.*')` in
`NewsSubmissionsTable`.

### Acceptance

- The old-name count query over the two names returns 0; the new-name query
  returns 2.
- `grep -rn "protected \$table" app/Models/Changelog.php
  app/Models/NewsSubmission.php` returns nothing.
- The reference command in "How the inventory was obtained" returns nothing for
  the two old names.
- `php artisan test` passes; `migrate:rollback --step=1` and `migrate` succeed.

## Verification

Run after every unit, on the dev MariaDB with its data loaded:

```
php artisan test
php artisan migrate:rollback --step=1 && php artisan migrate
```

Run once at the end, against dev:

```sql
SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'atarilegend';
```

It returns 119: the campaign renames forty-six tables and creates or drops
none, so the count is unchanged from the 119 the dead-tables campaign measured
on 2026-08-29. The gate that proves the end state is not the count but the
derivation check — for every model in `app/Models`, the table Eloquent derives
is the table the model uses:

```
php -r 'require "vendor/autoload.php";
$app = require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
foreach (glob("app/Models/*.php") as $f) {
  $class = "App\\Models\\" . basename($f, ".php");
  $m = new $class;
  if ($m->getTable() !== Illuminate\Support\Str::snake(Illuminate\Support\Str::pluralStudly(basename($f, ".php"))))
    echo basename($f, ".php"), " -> ", $m->getTable(), "\n";
}'
```

After the campaign it prints exactly the four kept models —
`GameReleaseMemoryEnhanced`, `GameReleaseSystemEnhanced`, `GameDeveloper` and
`GameIndividual` — and nothing else. That is the end state, checked. Run it
against the renamed class names: the check derives from the file name, so a
class this campaign renames is a different row afterwards.

`migrate:fresh` on the e2e database, then `E2ESeeder`, is run once at the end as
well: the historical migrations keep the old names and run in date order against
the schema of their day, and the seven new migrations run after them, so a fresh
build must land on the pluralised schema.

## Deploying

A `RENAME TABLE` is reversible by its `down()`, unlike the `DROP TABLE` the
dead-tables campaign deployed, so the dump-before-deploy rule is relaxed to:
take a `mysqldump` before the first unit and keep it until the last is deployed,
because a rollback of one unit leaves the schema half-pluralised and a dump is
the only way back to the pre-campaign state in one step.

Deploy the units one at a time. `.github/workflows/build-and-deploy.yml` runs
`migrate:rollback --step=1` on rollback, and a unit is one migration, so a bad
deploy of one unit reverses without touching the others. The seven migration
timestamps are numbered in unit order, so a batch rollback runs the units'
`down()`s in reverse unit order; the order is not load-bearing, because
`RENAME TABLE` rewrites the referenced name in a foreign key, which is why the
units are independent in the first place. Number them deliberately and verify
with `ls database/migrations | tail -7`.

## Out of scope

### The three foreign keys that stop matching the rule

Examined and deliberately left, for the reason stated in Unit 2: the campaign's
goal is the overrides, and a column rename is a separate campaign with its own
blast radius. The three, measured 2026-08-30:

| Column | Tables | Named for | The rule now gives |
|---|---|---|---|
| `pub_dev_id` | `game_developer`, `game_release`, `game_release_distributor` | `pub_dev` | `publisher_developer_id` |
| `trainer_option_id` | `game_release_trainer_option` | `trainer_option` | `trainer_id` |
| `game_submitinfo_id` | `screenshot_game_submitinfo` | `game_submitinfo` | `game_submit_info_id` |

`game_progress_system_id` on `game` and `game_genre_id` on `game_genre_cross`
were on this list in an earlier draft and are not on it now: their parents keep
their words (`game_progress_systems`, `game_genres`), so the columns keep
matching the rule and this campaign no longer reverses part of the foreign-key
campaign. `pub_dev_id` is still the target of that campaign's own
`dev_pub_id` → `pub_dev_id` move, and closing it means renaming the column
after Unit 4 renames the table.

The first two rows exist because Units 4 and 3 rename a table to a name that
drops or replaces a word, which is what Unit 2's rule forbids. Applying that
rule to them instead — `pub_dev` → `pub_devs` with a `PubDev` model, and
`trainer_option` → `trainer_options` with a `TrainerOption` model — would close
both columns and all six `TABLE, NOT MODEL` entries in
`RelationshipKeyConventionsTest`, at the price of an abbreviated class name in
the first case. **Both are open decisions for Units 3 and 4, and this plan does
not pre-empt them**; Unit 2's rule is stated where a reader of those units will
find it. Unit 6's three `*_comments` tables look like the same question and are
not: the model there is a comment on a screenshot pivot, so the model name is
the true one and the table is what moves. That is argued in Unit 6 and is not
reopened here.

### The two kept overrides

`ReleaseMemoryEnhanced` and `ReleaseSystemEnhanced` —
`GameReleaseMemoryEnhanced` and `GameReleaseSystemEnhanced` after Unit 2 — are
examined and deliberately kept, for the reason in Unit 2: the last word is a
past participle, the pluraliser suffixes it, and the only escape is renaming
the concept rather than the class. The next audit should not re-derive them as
a finding.

### The two kept pivot overrides

`GameDeveloper` and `GameIndividual` are examined and deliberately kept, for
the reason in Unit 7: the overrides are inert because the relations pass the
pivot table explicitly, `game_individual` is the pivot name Eloquent derives
for `Game` and `Individual`, and `game_developer` has no derived name to move
towards. The next audit should not re-derive them as a finding.

### The pivot tables with no model

Thirty-two `belongsToMany` pivots have no model of their own — `crew_individual`,
`game_release_crew`, `individual_nicks`, `game_genre_cross`, `screenshot_game`,
`game_user_comments` and the rest, the full list in the `--pivots` snapshot.
They are named by the relation that uses them, not by a `protected $table`, so
they have no override to remove and are not in scope. Renaming them would change
a relation's argument without any gain the campaign sets out to get.

### The historical migrations

Every migration dated before this campaign names the tables as they were on its
day, and all of them run before the seven new migrations on a `migrate:fresh`.
They are correct as written and must not be touched; the rule the merge campaign
set — no migration added after the renames may name the old tables — applies to
the seven new files and to anything that follows them.
