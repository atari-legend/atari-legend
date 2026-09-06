# The entity-name and pivot-order rename

*2026-09-02*

Successor to the [column-name consistency sweep](2026-08-31-column-name-consistency.md),
the [plural table rename](2026-08-29-plural-table-rename.md), the
[schema consistency sweep](2026-08-26-schema-consistency-sweep.md) and the
[foreign-key rename](2026-08-23-foreign-key-rename.md). Those campaigns converged
the primary keys, the foreign-key rule, the singular/plural rule and the column
prefixes. This plan closes what the
[schema drift survey](https://claude.ai/code/artifact/0d7e164b-547a-41fa-9751-fb1ee012bdf8)
listed as category C: the tables and columns whose *names* are still wrong, as
distinct from the integrity defects (category A) and the type drift (category B),
neither of which this plan touches.

It is naming only. No column changes type, no constraint changes rule, no index
is added or dropped, no row is deleted, and no file on disk is moved.

**The delivery unit is one commit per unit, based on `development`, not one pull
request.** Each unit carries exactly one migration, so reversing a deployed unit
is `migrate:rollback --step=1` and reverting the commit removes that one file.

Decisions were settled with nicolas on 2026-09-02 and are recorded under
"The four rules" below. Every figure was measured against the dev MariaDB 10.11
on 2026-09-02 at `development` / 5e1b351b, with the command named beside it.

## The four rules

Everything in the units follows from these. They are stated once here so a
reviewer can check a unit against a rule rather than against taste.

**Rule 1 — a junction table is `<owner>_<attribute>`, both singular.** The owner
is the more primary entity; the attribute is what is attached to it. Games,
releases, articles, interviews and reviews are owners. Screenshots, comments,
genres, controls, languages and the rest of the lookup tables are attributes.
Where both sides are entities, the more primary one leads: `game_review`, not
`review_game`.

Alphabetical order — the rule Laravel itself derives — is explicitly **not** the
rule here, for the reason measured under "How the inventory was obtained": it
cannot name a self-referential pivot, and it cannot name two different pivots on
the same model pair, and this schema has six of those.

**Rule 2 — a table with no model of its own is singular; a table with a model is
plural.** Standing, from the plural campaign. It is why
`game_release_memory_incompatible` is singular and
`game_release_tos_incompatibilities` is plural, which reads like an
inconsistency and is the rule working.

**Rule 3 — a parent table may not be the plural of a pivot name in its own
family.** `game_genres` beside a pivot `game_genre` is unreadable, which is the
whole reason `_cross` was invented. The fix is to rename the parent, not to
suffix the pivot: `genres` beside `game_genre` reads correctly.

**Rule 4 — where the application already agrees on a word, the table and the
model take that word.** The controllers, Livewire tables, routes, views, e2e
specs, storage directories and even the `changelogs.section` strings say
*company*, *link*, *genre* and *submission*. The tables and models are the only
places still saying `pub_dev`, `website`, `game_genre` and `submit_info`. The
schema moves to the application's vocabulary, not the reverse.

## The end state, checked clause by clause

- No table name carries a `_cross` suffix: `SELECT TABLE_NAME FROM
  information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME LIKE
  '%\_cross'` returns nothing.
- No table or column name contains `pub_dev`, `website` (bar `users.website`),
  `submitinfo` or `submit_info`: the census under "Verification" returns zero
  rows.
- Every junction table satisfies Rule 1, or is one of the six exceptions named
  under "The pivots Rule 1 cannot reach".
- Every table with a model is plural, and every table with no model is singular
  except `database_changes` and `game_galleries`, the two record tables Unit 9
  pluralises for holding records rather than a junction.
- The plural campaign's own census still returns the seven models it prints
  today and nothing else. It prints seven, not the four its own write-up
  records: `GameReleaseMemoryEnhanced`, `GameReleaseSystemEnhanced`,
  `GameDeveloper` and `GameIndividual`, plus `ScreenshotArticle`,
  `ScreenshotInterview` and `ScreenshotReview`, whose `AsPivot::getTable()`
  derives a singular table the `pluralStudly` check cannot. It printed seven at
  the plural campaign's own last commit (`a1e2ec54`) as well. Unit 5 renames
  three of the seven, to `ArticleScreenshot`, `InterviewScreenshot` and
  `ReviewScreenshot`; the count stays at seven.
- `RelationshipKeyConventionsTest::DECLINED` still holds 18 entries — three
  renamed, three reworded, none opened or closed. `php artisan test` passes.
- Eleven `belongsToMany` relations stop passing an explicit pivot-table
  argument, because Rule 1 and Laravel's derived name coincide for them.

| Unit | Scope | Tables | Columns | Models |
|---|---|---|---|---|
| 1 — companies | `pub_devs` | 1 | 3 | 1 |
| 2 — links | `websites` and family | 4 | 7 | 3 |
| 3 — genres | `game_genres` | 2 | 1 | 1 |
| 4 — game submissions | `game_submit_infos` | 2 | 1 | 1 |
| 5 — the screenshot pivots | six pivots, three comment tables | 8 | 3 | 6 |
| 6 — the comment pivots | four pivots and `review_game` | 5 | 0 | 0 |
| 7 — nicknames | `individual_nicks` | 1 | 0 | 0 |
| 8 — the incompatibility family | two tables | 2 | 0 | 1 |
| 9 — the singular record tables | `database_change`, `game_gallery` | 2 | 0 | 0 |
| 10 — the menu columns | `menu_disk_contents`, `menu_sets` | 0 | 2 | 0 |
| 11 — the content columns | `reviews`, `trivia_quotes`, `dumps`, `andreas` | 0 | 4 | 0 |
| 12 — the game and location columns | `games`, `game_vs`, `locations` | 0 | 5 | 0 |

**Totals: 27 tables, 26 columns, 13 models.**

The twelve units are independent and can be executed in any order, with one
exception stated in Unit 5: `screenshot_game_submitinfo` belongs to Unit 4, not
to Unit 5, so that the table is renamed once rather than twice.

## How the inventory was obtained

Not by reading the schema. The pivot half was measured by running Laravel's own
derivation against every relation in the application, with a throwaway script
that instantiated each model, called every zero-argument public method,
collected the `BelongsToMany` results, and compared `$relation->getTable()`
against `$model->joiningTable($related, new $related)`.

**The probe must skip static methods.** `Model::unsetConnectionResolver()` is a
public zero-argument method inherited from `Model`, and PHP will happily call it
through an instance. It nulls the global connection resolver, after which every
later relation construction fails with `Call to a member function connection()
on null` and the probe reports 0 pivots rather than 37. Filtering
`ReflectionMethod::IS_PUBLIC` on `! $method->isStatic()` is enough; so is
restricting to methods declared on `App\` models. Either way the probe reports
37 tables, 10 matching and 27 diverging.

`HasRelationships::joiningTable()`
(`vendor/laravel/framework/src/Illuminate/Database/Eloquent/Concerns/HasRelationships.php:750`)
snake-cases each model's class basename, sorts the two alphabetically and joins
them with an underscore. There is no `_cross`, `_pivot`, `_map` or `_xref`
concept anywhere in the framework.

The probe found **37 `belongsToMany` pivots, of which 10 already match Laravel's
derived name and 27 do not.** That number is the reason Rule 1 is a house rule
rather than Laravel's rule.

### What a table rename costs

`Schema::rename()` alone is not enough, and this campaign renames 27 tables.
InnoDB rewrites the referenced table name in every foreign key *pointing at* a
renamed table, so children look after themselves and no child table is ever
touched by hand. What it does not do is rename the table's *own* indexes and
constraints: after `Schema::rename('screenshot_review', 'review_screenshot')`
the table still carries `screenshot_review_review_id_foreign`. That is cosmetic
until a later migration writes `dropForeign(['review_id'])`, where Laravel
derives the name from the *new* table, does not find it, and fails with
SQLSTATE 42000 / 1091.

`Database\Support\TableRenamer` (`database/support/`, in `composer.json`'s
psr-4 map) is the answer and already exists — the plural campaign extracted it
from `2026_08_25_100300_rename_main_tables` and its six migrations use it. Every
migration in this campaign that renames a table uses it too:

```php
use Database\Support\TableRenamer;

private const TABLES = ['screenshot_review' => 'review_screenshot'];

public function up(): void   { TableRenamer::rename(self::TABLES); }
public function down(): void { TableRenamer::reverse(self::TABLES); }
```

Two of its rules govern what each unit below reports:

- **Only a Laravel-derived name is rewritten**, matched as
  `{table}_{columns}_{index|unique|foreign|primary}`. This schema's legacy
  indexes are named for their *column* — `websites` carries one called
  `user_id`, `game_genre_cross` one called `game_cat_id` — and a blind prefix
  swap would name them after neither the column nor a convention. They are left.
  Eleven survive this campaign, listed in the unit that renames their table.
- **A rewritten name over 64 characters is skipped**, that being MariaDB's
  identifier limit.

Across the twelve units it rewrites **40 derived identifiers** and leaves eleven
legacy ones. Units 1 and 9 rewrite none: `pub_devs`, `database_change` and
`game_gallery` carry no index or constraint of their own. One further constraint
is renamed by hand, in Unit 8, because `TableRenamer` cannot reach it — the only
identifier in the campaign moved outside the helper, and the reason is set out
there.

**The rewrite carries the table word, not the column word.** Renaming
`website_category_cross` to `link_category` turns
`website_category_cross_website_category_id_foreign` into
`link_category_website_category_id_foreign`, and the column rename in the same
migration then makes that column `category_id`. The constraint ends up naming
neither. That is the schema consistency sweep's standing decision on constraint
names, applied to tables as it already is to columns, and it is not reopened
here — a later `dropForeign` uses the literal string. It is recorded because the
result reads like a defect and is not one.

### The pivots Rule 1 cannot reach

Six of the 27 cannot be brought to the alphabetical rule by any rename, and
three of those cannot be brought to Rule 1 either. They are listed so the next
audit does not count them as findings.

| Pivot | Laravel derives | Why it is unreachable |
|---|---|---|
| `game_similar` | `game_game` | self-referential, and one table backs two relations |
| `sub_crew` | `crew_crew` | self-referential, two relations |
| `individual_nicks` | `individual_individual` | self-referential, two relations |
| `game_release_memory_incompatible` | `game_release_memory` | three pivots share the GameRelease↔Memory pair |
| `game_release_memory_minimum` | `game_release_memory` | same pair |
| `game_release_system_incompatible` | `game_release_system` | shares the pair with `_enhanced` |

The last three are role-qualified: the role word is the only thing telling them
apart, so it stays. The first three keep the names they have, except
`individual_nicks`, which Unit 7 moves for a different reason — it is plural
where every pivot is singular, and it abbreviates a word the application spells
out.

### The role-qualified pivots that keep their words

`game_developer` and `game_release_distributor` name a role on the same parent
(`companies` after Unit 1) and would both collapse to `company_game` /
`company_game_release` under the alphabetical rule, losing the only thing that
distinguishes them. `game_release_emulator_incompatibility` is the one member of
its family named with a noun rather than an adjective and is corrected in
Unit 8.

### The eleven relations that become derivable

After Units 3, 4, 5 and 6, Rule 1 and Laravel's rule coincide for nine pivots,
and the eleven relations that read them may then drop their explicit
pivot-table argument. Both directions of a pivot converge together, which is
why the count is eleven and not nine:

```
Game::genres()              app/Models/Game.php:117            game_genre
Game::screenshots()         app/Models/Game.php:95             game_screenshot
Game::reviews()             app/Models/Game.php:112            game_review
Review::games()             app/Models/Review.php:36           game_review
Article::screenshots()      app/Models/Article.php:33          article_screenshot
Article::comments()         app/Models/Article.php:47          article_comment
Comment::articles()         app/Models/Comment.php:38          article_comment
Interview::screenshots()    app/Models/Interview.php:38        interview_screenshot
Review::screenshots()       app/Models/Review.php:41           review_screenshot
GameFact::screenshots()     app/Models/GameFact.php:14         game_fact_screenshot
GameSubmission::screenshots() app/Models/GameSubmitInfo.php:21 game_submission_screenshot
```

Three of them carry a `->withPivot()` or `->orderBy()` continuation on the next
line; only the table argument goes, and the chain stays.

`Game::comments()`, `Interview::comments()` and `Review::comments()` do **not**
converge — Laravel would derive `comment_game`, `comment_interview` and
`comment_review`, which Rule 1 rejects — so those three keep the argument, as
does `Comment::games()`, `Comment::interviews()` and `Comment::reviews()` on the
other side.

### The column half

The column half is the survey's C9, C10, C12 and C14 to C16, each verified by
hand against every reference. `grep -rnw` over `app`, `resources`, `routes`,
`config`, `database/factories`, `database/seeders` and `tests`, excluding
`database/migrations`, because a migration naming a column is not a reader.

Three column names produce enough noise that the grep is useless without hand
filtering, and the filtering is recorded in the unit that owns them:
`order` (207 whole-word matches, almost all `orderBy`), `edit` (600, almost all
route names and controller methods) and `count` (552, almost all `->count()`).
Those are `grep -rnw` line counts over the seven trees above, measured the same
way as every other figure in this plan.

## Unit 1 — `pub_devs` becomes `companies`

### The rename

| Was | Becomes |
|---|---|
| `pub_devs` | `companies` |
| `game_developer.pub_dev_id` | `company_id` |
| `game_releases.pub_dev_id` | `company_id` |
| `game_release_distributor.pub_dev_id` | `company_id` |
| `App\Models\PubDev` | `App\Models\Company` |
| `database/factories/PubDevFactory.php` | `CompanyFactory.php` |

`pub_dev` is the last abbreviation these campaigns rename. It is not the
last abbreviation in the schema: `game_akas`, `game_release_akas`, `game_vs` and
the `tos` tables and columns all survive the end state, as accepted words rather
than shortenings of a word the application spells out. The plural campaign named
`publisher_developers` as the target and recorded it as "an open decision for
Units 3 and 4, and this plan does not pre-empt them"; the decision is taken here
and goes the other way, to `companies`, under Rule 4.

The evidence for Rule 4 is that the application has already made the move
everywhere except the model layer:

```
app/Http/Controllers/Admin/Games/GameCompanyController.php
app/Http/Controllers/Ajax/CompanyController.php        (queries PubDev)
app/Livewire/Admin/Games/GameCompaniesTable.php
routes/admin.php:164   Route::resource('companies', GameCompanyController::class)
routes/web.php:128     Route::get('companies.json', ...)->name('companies')
storage/app/public/images/company_logos/               (PubDev::getPathAttribute)
changelogs.section = 'Company'                         (421 rows)
```

`PubDev::getPathAttribute()` already returns `images/company_logos/`. The
storage directory has been called companies since before this campaign.

### What does not move

`game_developer` and `game_release_distributor` keep their names. They are
role-qualified pivots on the same parent and the role word is what tells them
apart — see "The role-qualified pivots that keep their words".

### The migration

One migration, `pub_devs_to_companies`.
`TableRenamer::rename(['pub_devs' => 'companies'])` first, then three
`renameColumn` calls on `game_developer`, `game_releases` and
`game_release_distributor`.

`pub_devs` is a pure foreign-key parent: it carries no index or constraint of
its own, so `TableRenamer` rewrites nothing and behaves exactly as
`Schema::rename` would. It is used anyway, so that every rename in the campaign
reads the same. InnoDB rewrites the three children's referenced table name
itself, so no child table is touched.

The three constraint names — `game_developer_pub_dev_id_foreign`,
`game_release_distributor_pub_dev_id_foreign` and the auto-generated
`game_releases_ibfk_3` — keep naming the old column, as do the three legacy
`pub_dev_id` indexes that back them. That is the schema consistency sweep's
standing decision on constraint names and is not reopened; a future
`dropForeign` uses the literal string.

### The code changes

121 references across 30 files, `grep -rniE "pub_?dev"`. The heavy ones
are `app/View/Components/Cards/Tops.php` (13),
`app/Helpers/AdminStatisticsHelper.php` (13),
`app/Http/Controllers/Admin/Games/GameCompanyController.php` (8) and
`tests/Feature/FactoriesTest.php` (8).

The relation methods keep their names: `Game::developers()`,
`GameRelease::publisher()` and `GameRelease::distributors()` name roles, not the
model, and all three stay.

`GameRelease::publisher()` stays in `RelationshipKeyConventionsTest::DECLINED`.
Its reason changes from "pub_dev_id is right" to "company_id is right"; it still
diverges, because `belongsTo` derives `publisher_id` from the method name.

### Acceptance

- `SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA =
  DATABASE() AND COLUMN_NAME LIKE '%pub\_dev%'` returns 0, and no table is named
  `pub_devs`.
- `php artisan test` passes.
- `/admin/games/companies` lists companies and a company edits and saves.
- A game page renders its publisher and developer credits.
- `tests/e2e/admin/games.spec.js` passes.

## Unit 2 — `websites` becomes `links`

### The rename

| Was | Becomes |
|---|---|
| `websites` | `links` |
| `website_categories` | `categories` |
| `website_category_cross` | `link_category` |
| `website_validates` | `link_submissions` |
| `website_category_cross.website_id` | `link_id` |
| `website_category_cross.website_category_id` | `category_id` |
| `website_validates.website_category_id` | `category_id` |
| `websites.count` | `view_count` |
| `websites.rate_number` | `rating_count` |
| `websites.rate_score` | `rating_total` |
| `App\Models\Website` | `App\Models\Link` |
| `App\Models\WebsiteCategory` | `App\Models\Category` |
| `App\Models\WebsiteValidate` | `App\Models\LinkSubmission` |
| `WebsiteResourcesController` | `LinkResourcesController` |
| route `websites.screenshot` | `links.screenshot` |

Rule 4 again, and the evidence is the same shape as Unit 1:

```
app/Http/Controllers/LinkController.php
app/Http/Controllers/Admin/Links/LinkController.php
app/Http/Controllers/Admin/Links/LinkCategoryController.php
app/Livewire/Admin/LinksTable.php
app/Livewire/Admin/LinkCategoriesTable.php
app/Console/Commands/CheckLinks.php          (artisan links:check)
resources/views/links/
tests/e2e/public/links.spec.js, tests/e2e/admin/links.spec.js
routes/web.php:103    Route::resource('/links', LinkController::class)
routes/admin.php:194  Route::prefix('/links')->name('links.')
routes/admin.php:197  Route::resource('categories', LinkCategoryController::class)
changelogs.section = 'Links' (352 rows), 'Links cat' (4 rows)
```

`routes/admin.php:197` is the one that settles `website_categories` →
`categories` rather than `link_categories`: the admin already registers the
resource as bare `categories`, nested under the `links.` prefix. It is also what
Rule 3 requires — `link_categories` beside a pivot `link_category` is the exact
collision `_cross` was invented to avoid, and `categories` beside `link_category`
reads correctly.

Open question: bare `categories` is the one name in this plan that claims a
generic word, in a schema whose `article_types`, `media_types`,
`magazine_index_types` and `menu_software_content_types` are all categories of
something, and a future "categories of X" table will have to work around it. The
alternative reintroduces the Rule 3 collision the unit exists to remove.
Recorded, decided as above, and to be reopened before Unit 2 runs or not at
all.

`website_validates` → `link_submissions` closes the survey's C6 on this side:
`news_submissions`, `game_submissions` (Unit 4) and `link_submissions` become
one vocabulary for one idea, and a table name stops reading as a verb.

### The three columns

`count`, `rate_number` and `rate_score` are the survey's C14 on this table. All
three are in the dead-column review's "kept as data, no reader" list, so the
rename is a migration with almost no code side: `count` has **zero** references
in the application, and `rate_number` and `rate_score` have one each, both in
`database/factories/WebsiteFactory.php:29-30`.

`count` is the sharpest of the three — a bare aggregate word as a column name,
so `SELECT count FROM websites` does not say what it returns. `view_count`,
`rating_count` and `rating_total` say what each holds.

### The one fatal rename

`app/View/Components/Cards/Link.php` declares `class Link extends Component` in
`App\View\Components\Cards` and today imports `use App\Models\Website;`. After
this unit it needs `App\Models\Link`, and `use App\Models\Link;` in a file
declaring `class Link` is a fatal `Cannot use App\Models\Link as Link because
the name is already in use` — not a test failure, a parse-time death of every
page that renders the component.

The house already has the pattern, twice: `Cards/Interview.php` does
`use App\Models\Interview as ModelsInterview;` and `Cards/MenuDisk.php` does
`use App\Models\MenuDisk as ModelMenuDisk;`. This file takes the same shape.

It is the only collision this campaign creates. No `Category`, `Company`,
`Genre`, `GameSubmission`, `LinkSubmission`, `ArticleScreenshot`,
`InterviewScreenshot` or `ReviewScreenshot` class exists anywhere in `app/`
outside the models this plan renames, and no model in `app/Models` carries an
explicit `$table` — so every model rename derives its new table cleanly, and the
model rename and the table rename have to land in the same commit. The
one-commit-per-unit rule is what guarantees that.

### What must not move

**`users.website` is a different column and stays.** It is a person's own URL on
their profile. `grep -rnw website` over the seven trees returns 114 lowercase
whole-word matches, and they split six ways, all of which need hand verification
before a single edit:

| Class | Count | Disposition |
|---|---|---|
| `users.website` — the profile column | 45 | unchanged |
| `$website` locals holding the model, outside `CheckLinks.php` | 36 | renamed to `$link` |
| the English word, in prose | 15 | unchanged |
| the model reached by another name — route name, e2e fixture key, factory label | 9 | renamed |
| mixed, in `CheckLinks.php` | 8 | by hand |
| a song title in `resources/sndh/songs-sndh2026lf.json` | 1 | unchanged |

The counts are lines, not occurrences: `$website` is 43 lines and 52
occurrences repo-wide, seven of those lines being the `CheckLinks.php` ones
counted on their own row. Excluding `tests/` drops the total to 71, which is
why the tree list matters — the same seven trees the column half uses.

The prose sites are `resources/views/about/card_history.blade.php` (8),
`about/card_intro.blade.php`, `about/card_credits.blade.php`,
`about/card_andreas.blade.php`, `home/card_open.blade.php`,
`games/card_search_results_export.blade.php` and
`links/card_submit.blade.php` (2, one of them a form placeholder). Prose in a UI
string is not a reference to a column — the same rule the dead-column review used on
"upgrade your karma stats ;-)".

### The public URL changes

`routes/web.php:118` is `/websites/{website}/screenshot.webp`, named
`websites.screenshot`, and becomes `/links/{link}/screenshot.webp` named
`links.screenshot`. It is referenced from
`resources/views/components/cards/link.blade.php:9`,
`resources/views/links/card_links.blade.php:31` and three assertions in
`tests/Feature/Public/ResourceControllersTest.php`.

**This is the only public URL this campaign changes.** It serves a per-link
screenshot image and is linked only from the two views above, so nothing
external can be holding the old path except a browser cache. It is not in the
sitemap: `grep -rn websites.screenshot resources/views/sitemap` returns nothing.
`SitemapController` *does* name links — it imports `WebsiteCategory` at line 9,
queries it at line 25 and passes it to the view as `websiteCategories` at line
30 — and those three lines are a Unit 2 touch point like any other. What it does
not do is reference the screenshot route. It is called out because no other unit in
this plan touches a URL at all.

### The storage directory does not move

`Website::getPathAttribute()` returns `images/website_images/`, and
`Link::getPathAttribute()` will keep returning it. Files on disk are a separate
inventory from tables and columns — the rule the dead-table review set — and
moving 215 images on dev and production is a deploy step this plan does not
carry. The resulting mismatch is the same one `PubDev` → `images/company_logos/`
has had in the other direction for years.

### The migration

One migration, `websites_to_links`. One `TableRenamer::rename()` call with all
four tables in it, then seven `renameColumn` calls.

`TableRenamer` rewrites five derived names here:

```
websites_user_id_foreign                                     -> links_user_id_foreign
website_validates_user_id_foreign                            -> link_submissions_user_id_foreign
website_category_cross_website_id_foreign                    -> link_category_website_id_foreign
website_category_cross_website_category_id_foreign           -> link_category_website_category_id_foreign
website_category_cross_website_id_website_category_id_unique  -> link_category_website_id_website_category_id_unique
```

and leaves one: `websites`' legacy index `user_id`, named for its column.
`website_categories` carries nothing of its own — it is a pure parent, like
`pub_devs`.

`link_submissions.website_category_id` → `category_id` carries no foreign key
(it is one of the survey's A6 findings) and renames like any other column;
adding the constraint is category A and out of scope here. The composite unique
on `link_category` covers both columns this unit renames, and the rename is a
`RENAME COLUMN` under it rather than a drop and re-add.

### Acceptance

- No table or column name contains `website` except `users.website`.
- `php artisan test` passes, in particular
  `tests/Feature/Public/ResourceControllersTest.php`,
  `tests/Feature/Public/ProfileTest.php` and
  `tests/Feature/Public/AuthTest.php` — the last two cover `users.website` and
  are the regression gate on the split above.
- `/links` renders with category filters and per-link screenshots.
- `/admin/links` and `/admin/links/categories` list and edit.
- `php artisan links:check --dry-run` runs to completion.
- `tests/e2e/public/links.spec.js` and `tests/e2e/admin/links.spec.js` pass.

## Unit 3 — `game_genres` becomes `genres`

### The rename

| Was | Becomes |
|---|---|
| `game_genres` | `genres` |
| `game_genre_cross` | `game_genre` |
| `game_genre_cross.game_genre_id` | `genre_id` |
| `App\Models\GameGenre` | `App\Models\Genre` |
| `database/factories/GameGenreFactory.php` | `GenreFactory.php` |

This is Rule 3 in its pure form. `game_genre_cross` exists because a pivot
called `game_genre` would read as the singular of its own parent
`game_genres`. Renaming the parent removes the collision, and the pivot then
lands on the name Laravel derives as well, so `Game::genres()` drops its
explicit table argument:

```php
// before
return $this->belongsToMany(GameGenre::class, 'game_genre_cross');
// after
return $this->belongsToMany(Genre::class);
```

`app/Http/Controllers/Ajax/GenreController.php` already exists and already
queries `GameGenre`; `routes/web.php:130` already registers `genres.json`.

### The code changes

Nine references to `game_genre_cross` and four to `game_genre_id`, all listed:

```
app/Helpers/StatisticsHelper.php:26
app/Helpers/AdminStatisticsHelper.php:161, 303, 304
app/View/Components/Cards/Tops.php:52
app/Models/Game.php:117
database/seeders/E2ESeeder.php:367
tests/Feature/Helpers/StatisticsHelperTest.php:105
tests/Feature/Admin/Games/GameControllerTest.php:325
```

Four of those are raw `DB::table()` joins that name both the table and the
column and have to be edited together.

The stale index `game_cat_id` on `game_genre_cross.game_genre_id` — which the
schema consistency sweep recorded as naming "a column gone for years" — is left
exactly as it is. This unit renames the column under it and does not touch the
index, which is the standing decision.

### The migration

One migration, `game_genres_to_genres`. `TableRenamer::rename()` with both
tables, then one `renameColumn` for `game_genre_cross.game_genre_id` →
`genre_id`.

Two derived names are rewritten, both on the pivot:

```
game_genre_cross_game_id_foreign        -> game_genre_game_id_foreign
game_genre_cross_game_genre_id_foreign  -> game_genre_game_genre_id_foreign
```

`game_genres` is a pure parent and carries nothing of its own. Two legacy
indexes on the pivot are left: `game_id`, and `game_cat_id` — the one the schema
consistency sweep recorded as naming a column gone for years. It sits on
`game_genre_id`, the column this unit renames to `genre_id`, so after this unit
the index name is two renames behind the column. Correcting it is the sweep's
open item, not this plan's.


### Acceptance

- `genres` and `game_genre` exist; `game_genres` and `game_genre_cross` do not.
- `php artisan test` passes, in particular
  `tests/Feature/Helpers/StatisticsHelperTest.php`.
- A game page renders its genres; `/games/search` filters by genre.
- `/admin/statistics` renders the genre coverage row and the top-genres table.

## Unit 4 — `game_submit_infos` becomes `game_submissions`

### The rename

| Was | Becomes |
|---|---|
| `game_submit_infos` | `game_submissions` |
| `screenshot_game_submitinfo` | `game_submission_screenshot` |
| `screenshot_game_submitinfo.game_submit_info_id` | `game_submission_id` |
| `App\Models\GameSubmitInfo` | `App\Models\GameSubmission` |
| `Game::infoSubmissions()` | `Game::submissions()` |
| `User::infoSubmissions()` | `User::submissions()` |
| route `games.submitInfo` | `games.submit` |
| `GameController::submitInfo()` | `GameController::submit()` |
| `resources/views/games/card_submit_info.blade.php` | `card_submit.blade.php` |

Rule 4 once more, and this table is the clearest case of the three: the
controller, the Livewire table and the admin view directory are *already* named
submission, and only the table and model still say `submit_info`.

```
app/Http/Controllers/Admin/Games/GameSubmissionController.php
app/Livewire/Admin/Games/GameSubmissionsTable.php
resources/views/admin/games/submissions/
routes/admin.php:155  Route::resource('submissions', GameSubmissionController::class)
```

The pivot is the survey's C3. `screenshot_game_submitinfo` was left stale by the
column-name campaign, which renamed the column inside it to
`game_submit_info_id` and did not rename the table, so the table currently says
`submitinfo` and its own column says `submit_info`. Under Rule 1 the pivot is
`<owner>_<attribute>` = `game_submission_screenshot`, which is also what Laravel
derives, so `GameSubmission::screenshots()` drops its table argument.

**This is the one ordering constraint in the plan.** Unit 5 renames the other
five screenshot pivots and deliberately excludes this one, so that the table is
renamed once rather than renamed to `game_submit_info_screenshot` by Unit 5 and
again by Unit 4. If Unit 5 lands first it must still leave this table alone.

### The route change

`routes/web.php:57` is
`Route::post('/games/{game:slug}/submitInfo', ...)->name('games.submitInfo')`
and becomes `/games/{game:slug}/submit` named `games.submit`. It is a POST-only
route behind auth, referenced from `resources/views/games/card_submit_info.blade.php`
and five test sites — `tests/Feature/Admin/Other/RemainingSectionsTest.php:349`
and `:380`, `tests/Feature/Public/GamePageTest.php:264`, `:287` and `:305`;
nothing links to it with a GET and it is not in the sitemap.

### The code changes

70 references outside `database/migrations`, `grep -rniE "submit_?info"`. The
heavy files are
`tests/Feature/Admin/Games/GameSubmissionTest.php` (15),
`tests/Feature/Public/GamePageTest.php` (7) and
`app/Http/Controllers/Admin/Games/GameSubmissionController.php` (7).

`app/Models/User.php:130` carries a docblock naming `game_submitinfo` among the
`ON DELETE RESTRICT` relations that block a user deletion, and
`tests/Feature/Admin/Other/RemainingSectionsTest.php:337` and
`tests/Feature/Admin/Games/GameSubmissionTest.php:21` carry the same old name in
prose. All three are updated with the rename.

### The migration

One migration, `game_submit_infos_to_game_submissions`.
`TableRenamer::rename()` with both tables, then one `renameColumn` for
`game_submission_screenshot.game_submit_info_id` → `game_submission_id`.

Four derived names are rewritten:

```
game_submit_infos_game_id_foreign                          -> game_submissions_game_id_foreign
game_submit_infos_user_id_foreign                          -> game_submissions_user_id_foreign
screenshot_game_submitinfo_game_submitinfo_id_foreign      -> game_submission_screenshot_game_submitinfo_id_foreign
screenshot_game_submitinfo_screenshot_id_foreign           -> game_submission_screenshot_screenshot_id_foreign
```

and one legacy index is left, `game_submit_infos`' `user_id`.

The pivot is a child of the table renamed beside it. Order inside the map does
not matter: whichever is renamed first, InnoDB rewrites the child's referenced
name, and `TableRenamer` reads the referenced table out of
`information_schema` at the moment it processes each table.

`game_submission_screenshot_game_submitinfo_id_foreign` is the sharpest example
of the campaign-wide rule above: the rewrite carries the table word and the
column rename then moves the column to `game_submission_id`, so the constraint
names the new table and the old column. Left, per the standing decision.


### Acceptance

- `game_submissions` and `game_submission_screenshot` exist; nothing in the
  schema matches `%submitinfo%` or `%submit\_info%`.
- `php artisan test` passes, in particular
  `tests/Feature/Admin/Games/GameSubmissionTest.php`.
- The "submit information" form on a game page posts and stores.
- `/admin/games/submissions` lists, shows and deletes a submission, including
  deleting one of its screenshots.

## Unit 5 — the screenshot pivots take owner-first

### The rename

Rule 1 applied to the six pivots that attach a screenshot to something. Five are
here; the sixth is Unit 4's.

| Was | Becomes |
|---|---|
| `screenshot_game` | `game_screenshot` |
| `screenshot_game_fact` | `game_fact_screenshot` |
| `screenshot_article` | `article_screenshot` |
| `screenshot_interview` | `interview_screenshot` |
| `screenshot_review` | `review_screenshot` |
| `screenshot_article_comments` | `article_screenshot_comments` |
| `screenshot_interview_comments` | `interview_screenshot_comments` |
| `screenshot_review_comments` | `review_screenshot_comments` |
| `screenshot_article_comments.screenshot_article_id` | `article_screenshot_id` |
| `screenshot_interview_comments.screenshot_interview_id` | `interview_screenshot_id` |
| `screenshot_review_comments.screenshot_review_id` | `review_screenshot_id` |

Six models move with them, for two different reasons. Three of the pivots have
`Pivot` subclasses whose table name is *derived from the class name* —
`AsPivot::getTable()` singularises `class_basename($this)` — so the class has to
move or the derivation breaks. The three `*Comment` models are ordinary models
whose table this unit renames, and they move by the plain Eloquent derivation:

| Was | Becomes |
|---|---|
| `ScreenshotArticle` | `ArticleScreenshot` |
| `ScreenshotInterview` | `InterviewScreenshot` |
| `ScreenshotReview` | `ReviewScreenshot` |
| `ScreenshotArticleComment` | `ArticleScreenshotComment` |
| `ScreenshotInterviewComment` | `InterviewScreenshotComment` |
| `ScreenshotReviewComment` | `ReviewScreenshotComment` |

The three comment tables are not pivots — they have models and their own text
column — so Rule 2 keeps them plural. Their foreign key follows the standing
foreign-key rule, singularised parent plus `_id`, which is why
`screenshot_article_id` becomes `article_screenshot_id` rather than staying put.

### The three DECLINED entries

`RelationshipKeyConventionsTest::DECLINED` holds three entries under
PIVOT SUBCLASS whose labels are the class names being renamed:

```
'ScreenshotArticle::comment()'    ->  'ArticleScreenshot::comment()'
'ScreenshotInterview::comment()'  ->  'InterviewScreenshot::comment()'
'ScreenshotReview::comment()'     ->  'ReviewScreenshot::comment()'
```

The reason is unchanged — `AsPivot` overrides `getForeignKey()`, which is null
on a fresh instance, so there is no default to fall back to — and the count
stays at 18. `Article::screenshots()`, `Interview::screenshots()` and
`Review::screenshots()` *do* drop their explicit pivot-table argument, which is
a different parameter on a different relation and is not what the test
measures.

### The code changes

Small for the volume of tables: `screenshot_game` (9),
`screenshot_review` (6), `screenshot_article` (3), `screenshot_interview` (3),
`screenshot_game_fact` (2), and 39 references to the six class names, of which
`ScreenshotReviewComment` alone is 12.

`database/seeders/E2ESeeder.php:21` names `screenshot_game` in a docblock listing
the pivots the seeder fills by hand and is updated with it.

### The migration

One migration, `screenshot_pivots_to_owner_first`. `TableRenamer::rename()` with
all eight tables, then three `renameColumn` calls on the comment tables.

Thirteen derived names are rewritten, the most of any unit: two for each of the
five pivots, one for each of the three comment tables.

```
screenshot_article_article_id_foreign                       -> article_screenshot_article_id_foreign
screenshot_article_screenshot_id_foreign                    -> article_screenshot_screenshot_id_foreign
screenshot_article_comments_screenshot_article_id_foreign   -> article_screenshot_comments_screenshot_article_id_foreign
```

and the same shape for interview, review, game and game fact. One legacy index
is left — `screenshot_game`'s `game_id`, which sits beside a constraint of the
same column that *is* rewritten, because the constraint was named by Laravel and
the index was not.

This unit renames three parent/child pairs in one migration — each
`screenshot_*` pivot is the parent of its `screenshot_*_comments` table. As in
Unit 4, order inside the map does not matter. The longest name it produces is
`interview_screenshot_comments_screenshot_interview_id_foreign` at 61
characters — the table word rewritten, the column word left — which is inside
MariaDB's limit, so nothing is skipped.


### Acceptance

- Five `screenshot_*` pivots and three `screenshot_*_comments` tables are gone;
  their owner-first counterparts exist.
- `php artisan test` passes.
- A game page, an article, an interview and a review each render their
  screenshots with captions.
- `/admin/articles`, `/admin/interviews` and `/admin/reviews` each add, caption
  and delete an image.
- `php artisan db:seed --class=E2ESeeder` completes and
  `tests/e2e/admin/content.spec.js` passes.

## Unit 6 — the comment pivots and `review_game`

### The rename

| Was | Becomes |
|---|---|
| `article_user_comments` | `article_comment` |
| `game_user_comments` | `game_comment` |
| `interview_user_comments` | `interview_comment` |
| `review_user_comments` | `review_comment` |
| `review_game` | `game_review` |

The four comment pivots are the survey's C1. They are plural where thirty
sibling pivots are singular, and they carry the word `user` while their foreign
keys go to `articles`/`games`/`interviews`/`reviews` and `comments` — never to
`users`. Rule 1 gives `<owner>_comment` for all four.

`review_game` is the survey's C5. Under Rule 1 the more primary entity leads,
and `games` leads `reviews`; the table currently contradicts its own sibling,
because `screenshot_review` puts reviews on the owning side and `review_game`
puts them on the attribute side. After Unit 5 and this unit, reviews are
consistently the owner of their screenshots and comments and the attribute of a
game.

### What converges and what does not

`Article::comments()` and `Game::reviews()` land on Laravel's derived name and
drop their table argument. `Game::comments()`, `Interview::comments()` and
`Review::comments()` do not — Laravel derives `comment_game`,
`comment_interview` and `comment_review`, which Rule 1 rejects — so those three
keep it. This is the clearest place in the plan where the house rule and the
framework rule disagree, and it is recorded so a later reader does not "fix" it.

### The code changes

21 lines and 24 occurrences over the seven trees — 19 lines with `tests/`
excluded. Two of them are raw joins that name the table more than once, and they
are the ones needing care, because a qualified table name inside a join string
is the shape the plural campaign recorded as the one its own greps missed:

```php
// app/View/Components/Cards/LatestComments.php:40
->join('game_user_comments', 'comments.id', '=', 'game_user_comments.comment_id');

// app/Livewire/Admin/ReviewsTable.php:67-68 — three mentions across two lines
->leftJoin('review_game', 'review_game.review_id', '=', 'reviews.id')
->leftJoin('games', 'review_game.game_id', '=', 'games.id');
```

`ReviewsTable::builder()` also reads `reviews.edit`, which Unit 11 renames; the
two units touch the same four lines and whichever runs second re-reads them.

`app/Http/Controllers/Admin/Games/GameController.php:149` carries a comment
explaining the cascade on `game_user_comments` and is updated with it, as is the
`E2ESeeder.php:21` docblock.

### The migration

One migration, `comment_pivots_to_owner_first`. `TableRenamer::rename()` with
all five tables and no `renameColumn` at all — this unit renames no column.

Ten derived names are rewritten, two per table, and no legacy name is left:

```
article_user_comments_article_id_foreign    -> article_comment_article_id_foreign
article_user_comments_comment_id_foreign    -> article_comment_comment_id_foreign
review_game_game_id_foreign                 -> game_review_game_id_foreign
review_game_review_id_foreign               -> game_review_review_id_foreign
```

and the same shape for the game, interview and review comment pivots. It is the
cleanest rename in the campaign: five tables, ten constraints, nothing left
behind and no column touched.

`app/Livewire/Admin/ReviewsTable.php` is also a Unit 11 touch point, four lines
above the `review_game` join. Whichever unit runs second re-reads that method.


### Acceptance

- The four `*_user_comments` tables and `review_game` are gone.
- `php artisan test` passes, in particular
  `tests/Feature/Admin/Games/GameControllerTest.php`, which asserts the comment
  pivot empties when a game is deleted.
- The home page "latest comments" card renders.
- A game page, an article, an interview and a review each render and accept a
  comment.
- A review page lists the games it covers, and a game page lists its reviews.

## Unit 7 — `individual_nicks` becomes `individual_nickname`

`individual_nicks` is plural where every pivot is singular, and it abbreviates a
word the application spells out in full: the relation is
`Individual::nicknames()` and the admin labels say Nicknames.

It is self-referential, so Rule 1 does not apply — there is no owner and no
attribute, both sides are `individuals` — and it stays on the list of pivots the
alphabetical rule cannot reach. What moves is the plural and the abbreviation.

Six references: `app/Helpers/AdminStatisticsHelper.php:123`,
`app/Models/Individual.php:35` and `:44`, the two
`RelationshipKeyConventionsTest::DECLINED` entries whose reason strings name the
table (`:49` and `:50`), and `tests/e2e/admin-write/games-reference.spec.js:28`,
a comment naming the table in prose. Both `DECLINED` entries stay; only the
table name inside the reason changes.

The column `nick_id` does **not** move. It is one of the four role-qualified
foreign keys the column-name campaign examined and deliberately left, because
`individual_id` is already taken by the other half of the pivot.

### The migration

One migration, `individual_nicks_to_individual_nickname`.
`TableRenamer::rename(['individual_nicks' => 'individual_nickname'])` and
nothing else.

Two derived names are rewritten and none is left:

```
individual_nicks_individual_id_foreign  -> individual_nickname_individual_id_foreign
individual_nicks_nick_id_foreign        -> individual_nickname_nick_id_foreign
```

Both point at `individuals`, because the pivot is self-referential. This unit
renames no column, so both constraints are correct in both halves afterwards —
the only rename in the campaign of which that is true, and the reason it is the
one unit whose migration leaves nothing behind at all.


### Acceptance

- `individual_nickname` exists, `individual_nicks` does not.
- `php artisan test` passes with
  `test_no_relation_diverges_from_the_convention_without_a_reason` at 18
  entries.
- An individual page renders its nicknames and the individuals behind them.
- `/admin/statistics` renders the Nicknames count.

## Unit 8 — the incompatibility family

### The rename

| Was | Becomes |
|---|---|
| `game_release_emulator_incompatibility` | `game_release_emulator_incompatible` |
| `game_release_tos_version_incompatibilities` | `game_release_tos_incompatibilities` |
| `GameReleaseTosVersionIncompatibility` | `GameReleaseTosIncompatibility` |

The survey called this family three spellings of one idea. Two of the three are
the rules working rather than an inconsistency, and only two changes are needed.

`game_release_emulator_incompatibility` is a pure `belongsToMany` pivot with no
model, sitting beside `game_release_memory_incompatible` and
`game_release_system_incompatible`, which are also pure pivots. It is the only
one named with a noun. The adjective form wins because it is the majority, it
matches the sibling `_enhanced` and `_minimum` tables, and it matches the
relation method that reads it, `GameRelease::emulatorIncompatibles()`.

`game_release_tos_version_incompatibilities` keeps its plural, because Rule 2
says a table with a model is plural and this one has `GameReleaseTosVersionIncompatibility`
plus a `language_id` column of its own. What it loses is `version`, which names
nothing: the parent table is `tos` and the column is `tos_id`. The relation
`GameRelease::tosIncompatibles()` already omits it.

**The `_incompatible` / `_incompatibilities` split that remains is deliberate**
and is Rule 2 rather than drift. It is recorded here so the next audit reads it
as a rule.

### The code changes

Twelve: one relation declaration in `app/Models/GameRelease.php:134`, and eleven
references to `GameReleaseTosVersionIncompatibility` across the model, the
release controller and the release tests. The table
`game_release_tos_version_incompatibilities` itself has **zero** literal
references — it is reached only through the model.

### The migration

One migration, `incompatibility_family_rename`. `TableRenamer::rename()` with
both tables and no `renameColumn`.

Four derived names are rewritten by `TableRenamer`, and a fifth by hand — see
"The stranded TOS constraint" below, which is the one identifier in the whole
campaign the helper cannot reach:

```
game_release_emulator_incompatibility_emulator_id_foreign      -> game_release_emulator_incompatible_emulator_id_foreign
game_release_emulator_incompatibility_game_release_id_foreign  -> game_release_emulator_incompatible_game_release_id_foreign
game_release_tos_version_incompatibilities_language_id_foreign -> game_release_tos_incompatibilities_language_id_foreign
game_release_tos_version_incompatibilities_tos_id_foreign      -> game_release_tos_incompatibilities_tos_id_foreign
```

Five legacy column-named indexes are left — `emulator_id`, `game_release_id`,
`language_id`, `tos_id` and a second `game_release_id`.

### The stranded TOS constraint, and why this unit closes it

`game_release_tos_version_incompatibilities` carries a third foreign key that
`TableRenamer` will not touch:

```
game_release_tos_version_incompatibilities_language_id_foreign   (62 chars)
game_release_tos_version_incompatibilities_tos_id_foreign        (57 chars)
game_release_tos_version_incompatibility_game_release_id_foreign (64 chars)  <- singular stem
```

The third names a table that has not existed since August. The history is worth
following, because it is the only place in four campaigns where MariaDB's
identifier limit changed an outcome:

1. The table was created in 2020 as `game_release_tos_version_incompatibility`,
   singular, and its foreign keys took Laravel-derived names from it.
2. The plural campaign renamed the table to `..._incompatibilities`.
   `TableRenamer` rewrote the `language_id` and `tos_id` constraints, whose new
   names came to 62 and 57 characters. The `game_release_id` one would have come
   to **66**, over MariaDB's 64-character limit, so the rewrite was skipped and
   the constraint kept its singular name — which is itself exactly 64
   characters, sitting on the ceiling.
3. That was genuinely harmless, and the plural campaign recorded why: a later
   `dropForeign(['game_release_id'])` would derive the same 66-character name,
   which cannot exist under any name, so no `dropForeign` on that column could
   work whatever anybody did. Unreachable either way, nothing lost.

**Unit 8 changes the arithmetic.** Dropping `version` takes the table to 34
characters, so the name Laravel derives becomes
`game_release_tos_incompatibilities_game_release_id_foreign` — **58
characters, legal for the first time.** From this unit onward a
`dropForeign(['game_release_id'])` derives a name that *could* exist and simply
does not, failing with 1091. The impossible mismatch becomes a fixable one, and
a fixable mismatch left in place is an exception rather than a limitation.

`TableRenamer` cannot close it on its own: its rewrite rule matches only names
beginning with the table's *current* name, and this one begins with the singular
stem of a table two renames dead. So the migration closes it by hand, with the
same drop-and-re-add `TableRenamer` performs forty times elsewhere, reading the
rules rather than assuming them — `ON UPDATE RESTRICT ON DELETE CASCADE`,
referencing `game_releases (id)`:

```php
DB::statement('ALTER TABLE `game_release_tos_incompatibilities`
    DROP FOREIGN KEY `game_release_tos_version_incompatibility_game_release_id_foreign`');
DB::statement('ALTER TABLE `game_release_tos_incompatibilities`
    ADD CONSTRAINT `game_release_tos_incompatibilities_game_release_id_foreign`
    FOREIGN KEY (`game_release_id`) REFERENCES `game_releases` (`id`)
    ON DELETE CASCADE ON UPDATE RESTRICT');
```

The `down()` reverses it, restoring the 64-character singular name, because a
rollback has to land on the schema the previous unit left.

After this, all three of the table's foreign keys are named after the table they
sit on, and the table is the only one in the campaign whose constraint names end
up fully correct in both halves — table word and column word — because Unit 8
renames no column either.


### Acceptance

- Both tables exist under their new names.
- All three of `game_release_tos_incompatibilities`' foreign keys are named
  after it: `SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME =
  'game_release_tos_incompatibilities' AND REFERENCED_TABLE_NAME IS NOT NULL`
  returns three rows and every one begins with the table name. No row contains
  `version`.
- The `game_release_id` constraint still enforces its rule after the re-add:
  deleting a game release cascades to its TOS incompatibilities, which
  `tests/Feature/Admin/Games/GameControllerTest.php` covers through the release
  cascade.
- `php artisan test` passes, in particular the release-configuration tests.
- `php artisan migrate:rollback --step=1 && php artisan migrate` round-trips,
  which is the gate on the hand-written `down()` restoring the 64-character
  name.
- A release page renders its emulator and TOS incompatibilities.
- `/admin/games/{game}/releases/{release}` saves both.

## Unit 9 — the two singular record tables

`database_change` → `database_changes` and `game_gallery` → `game_galleries`.

Neither is a pivot: they hold 267 and 118 rows of real records. The plural
campaign listed both among "the five that hold entities or records" and left
them, and the dead-table review had already decided to keep both without a
reader. Keeping a table is not a reason to leave it singular.

Both have **zero** references outside `database/migrations` — no model, no
relation, no query — so this unit is one migration and no code change at all. It
is the cheapest unit in the plan and the only one that cannot break anything.

`game_gallery.game_description_gallery` and `game_gallery.image_ext` are *not*
renamed here. The column-name campaign examined
`game_description_gallery` and left it with a recorded reason, and `image_ext`
is category B (type and spelling drift across nine `imgext` columns), which this
plan does not touch. Renaming the table does not reopen either.

### The migration

One migration, `singular_record_tables_pluralised`.
`TableRenamer::rename(['database_change' => 'database_changes', 'game_gallery'
=> 'game_galleries'])` and nothing else.

Neither table carries an index, a constraint, or a foreign key pointing at it,
so `TableRenamer` rewrites nothing and does exactly what `Schema::rename` would.
It is used for consistency with the other renames. Two `RENAME TABLE`
statements is the whole unit.


### Acceptance

- `database_changes` and `game_galleries` exist.
- `SELECT COUNT(*)` on each returns 267 and 118.
- `php artisan test` passes — no test names either table, so the suite is a
  regression gate rather than a proof.

## Unit 10 — the menu columns

| Was | Becomes | References |
|---|---|---|
| `menu_disk_contents.order` | `position` | 20 |
| `menu_sets.menus_sort` | `sort_direction` | 17 |

`order` is the survey's C9. It is fully reserved in MySQL and MariaDB, so every
raw statement naming it needs backticks. Eloquent quotes identifiers, and
`grep -rn "DB::raw\|selectRaw\|whereRaw\|orderByRaw"` filtered to this table
returns nothing, so **there is no live bug**. The name is a hazard for the next
raw statement rather than a current defect, and `position` costs nothing to
adopt.

The whole-word grep for `order` returns 207 lines and almost all are `orderBy`,
`orderByDesc` and `orderByRaw`. The 20 real ones are in
`app/Livewire/Admin/MenuImport.php` (6), `app/Helpers/MenuImportParser.php` (2),
`app/Http/Controllers/Admin/Menus/MenuDisksContentController.php` (4),
`app/Models/MenuDiskContent.php` (1) and seven Blade `sortBy('order')` calls:
`components/cards/menu.blade.php:29`, `games/card_menus.blade.php:44`,
`menus/epub/disk.blade.php:19`, `menus/partial_menudisk.blade.php:66` and `:73`,
`admin/menus/disks/card_list.blade.php:29` and
`admin/menus/disks/card_content.blade.php:30`.

**The import sheet's column key is not renamed.** `content_order` in
`app/Helpers/MenuImportTemplate.php:41` and `:168` is a spreadsheet header key,
not a database column, and renaming it would invalidate every sheet an editor
already has. `MenuImportParser` maps `$cells['content_order']` to the model
attribute, and that mapping is the only line that changes.

`menus_sort` is the survey's C14: a column carrying its own table's plural stem,
on a table that is about menu sets. Its 17th reference is prose —
`tests/e2e/README.md:407` names the column in a note — and is updated with it. `sort_direction` says what the `asc`/`desc`
enum holds without naming a table. The enum itself is untouched — types are
category B.

### The migration

One migration, `menu_columns_rename`. Two `renameColumn` calls and no
`TableRenamer` — this unit renames no table.

Neither column carries an index or a foreign key, so both are a plain
`RENAME COLUMN`. `order` is fully reserved in MariaDB, so the `down()` puts a
reserved word back; Laravel quotes identifiers in `renameColumn`, which is why
the reversal works and why the column survived this long.


### Acceptance

- `menu_disk_contents.position` and `menu_sets.sort_direction` exist.
- `php artisan test` passes.
- A menu set page lists its menus in the set's configured direction, and
  reversing the direction in `/admin/menus/sets` reverses it.
- A menu disk lists its contents in `position` order.
- The menu import wizard parses a sheet and stores contents in the right order —
  `tests/e2e/admin/menus.spec.js`.

## Unit 11 — the content columns

| Was | Becomes | References |
|---|---|---|
| `reviews.edit` | `submission` | 20 |
| `trivia_quotes.quote` | `text` | 12 |
| `dumps.info` | `notes` | 6 |
| `andreas.user_name` | `name` | 1 |

`reviews.edit` is the survey's C14 and the most misleading name in the schema. It
is not an edit flag: it is the "this is a user submission awaiting approval"
flag, read as `Review::REVIEW_PUBLISHED` / `REVIEW_UNPUBLISHED`. The form field
is already `name="submission"` and the label already reads "Submission"
(`resources/views/admin/reviews/reviews/card_edit.blade.php:92-97`), so the
rename aligns the column with the field and the label it already has.

It is a separate flag from `reviews.draft`, which hides a published review from
the site; the two coexist and both stay.

The whole-word grep for `edit` returns 600 lines, almost all `Route::` names and
`edit()` controller methods. The 20 real ones are listed in full:

```
app/Helpers/FeedHelper.php:16
app/View/Components/Cards/Screenstar.php:29
app/View/Components/Cards/Reviews.php:27
app/Http/Controllers/ReviewController.php:26, 100, 180
app/Http/Controllers/GameController.php:65
app/Http/Controllers/SitemapController.php:22
app/Http/Controllers/Admin/Reviews/ReviewsController.php:62, 101
app/Models/Review.php:21
resources/views/admin/reviews/reviews/card_edit.blade.php:95
app/Livewire/Admin/ReviewsTable.php:66
database/factories/ReviewFactory.php:26, 45
tests/Feature/Admin/Reviews/ReviewsControllerTest.php:79, 107
tests/Feature/Public/GamePageTest.php:74
tests/Feature/Public/ReviewPagesTest.php:190
tests/e2e/public-write/reviews.spec.js:145
```

`app/Livewire/Admin/ReviewsTable.php:66` is the one no `grep -w edit` finds: it
is `->where('reviews.edit', '=', $this->submissions)`, the column qualified by
its table inside a builder string. It is the shape the plural campaign recorded
as the one shape its own commands did not find, and the same file is a Unit 6
touch point two lines below. `tests/e2e/public-write/reviews.spec.js:145` names
the column in a comment and is updated with it, per the standard Unit 4 sets.

`trivia_quotes.quote` is a self-named column the word census returned and the
column-name campaign did not examine. Its sibling `trivia` already calls the
same thing `text`, and the admin form field is already `name="text"`
(`resources/views/admin/others/quotes/card_list.blade.php:15`), so this is the
same shape as the `spotlights.spotlight` → `text` and `comments.comment` →
`text` moves that campaign already made.

`dumps.info` becomes `notes`, matching `game_releases.notes`,
`menu_disks.notes`, `game_release_scans.notes` and the two protection pivots'
`notes`. Care is needed because `'info'` is also the request field on the
unrelated game-submission form (`route('games.submitInfo')`), which writes
`game_submissions.text` and does not move. That form accounts for
`app/Http/Controllers/GameController.php:194`,
`resources/views/games/card_submit_info.blade.php:24`, five PHPUnit sites
(`GamePageTest.php:264`, `:288`, `:305` and `RemainingSectionsTest.php:349`,
`:380`) and two Playwright ones
(`tests/e2e/public-write/games.spec.js:105` and `:157`) — none of which are
`dumps.info`.
The six that are: `ReleaseMediasDumpsController.php:125`,
`database/factories/DumpFactory.php:29`,
`resources/views/games/releases/card_media.blade.php:57`,
`resources/views/admin/games/games/releases/medias/card_media.blade.php:188` and
`tests/Feature/Admin/Games/Releases/ReleaseMediaTest.php:274` and `:277`.

`andreas.user_name` is a typed-in guestbook name, not a reference to `users`. One
reference, `resources/views/about/card_andreas.blade.php:64`.

### The migration

One migration, `content_columns_rename`. Four `renameColumn` calls across four
tables and no `TableRenamer`.

None of the four columns carries an index or a foreign key, so all four are a
plain `RENAME COLUMN`. The unit's risk is entirely in the code half, not the
schema half: `reviews.edit` has 20 readers and one of them hides inside a
builder string.


### Acceptance

- All four columns exist under their new names.
- `php artisan test` passes, in particular the review submission tests.
- Submitting a review from `/reviews/submit` stores it unapproved and it does
  not appear on `/reviews`; approving it in `/admin/reviews/submissions` makes
  it appear.
- The home carousel renders a trivia quote and `/admin/others/quotes` edits one.
- A release page renders a dump's notes and the admin dump form saves them.
- `/about` renders the Andreas guestbook with names and dates.

## Unit 12 — the game and location columns

| Was | Becomes | References |
|---|---|---|
| `games.number_players_on_same_machine` | `players_same_machine` | 8 |
| `games.number_players_multiple_machines` | `players_multiple_machines` | 8 |
| `game_vs.amiga_id` | `lemonamiga_id` | 17 |
| `locations.country_iso2` | `iso2` | 9 |
| `locations.country_iso3` | `iso3` | 1 |

The two `games` columns are the survey's C15: one measurement split two ways,
spelled asymmetrically — "on same" against "multiple", singular against plural.
Dropping the redundant `number_` (the value *is* a number) and keeping the two
halves parallel fixes both at once.

`game_vs.amiga_id` is the survey's C10. It is a LemonAmiga identifier — the admin
label says so literally, `resources/views/admin/games/games/card_edit_vs.blade.php:11`
reads "LemonAmiga ID" — and it sits directly beside `atari_id`, which *is* a real
foreign key into `games`. The two read as a matched pair and only one is.
`lemonamiga_id` beside `lemon64_slug` makes the table's two external identifiers
a real pair and breaks the false one.

`atari_id` does not move. It is one of the four role-qualified foreign keys the
column-name campaign examined and deliberately left.

`locations.country_iso2` and `country_iso3` are the naming half of the survey's
C12. The prefix is a lie on this table: `locations` holds 8 continents and 246
countries distinguished by a `type` enum, and the continent row for Europe
carries `country_iso2 = 'eu'`. Dropping the prefix makes the column true for
both kinds of row.

**The structural half of C12 is out of scope** — see below.

### The migration

One migration, `game_and_location_columns_rename`. Five `renameColumn` calls
across three tables and no `TableRenamer`.

Two of the five sit under an index, and both index names go stale:

- `game_vs.amiga_id` → `lemonamiga_id` is the third column of
  `game_vs_atari_id_lemon64_slug_amiga_id_index`. That is a Laravel-*derived*
  name, unlike the legacy column-named indexes elsewhere in this campaign, so a
  later `dropIndex(['atari_id', 'lemon64_slug', 'lemonamiga_id'])` derives
  `game_vs_atari_id_lemon64_slug_lemonamiga_id_index` and fails with 1091. The
  column-name campaign's standing decision covers it — index and constraint
  names are not reopened — and it is called out because this is the only derived
  *index* name the campaign strands, as against the constraint names it strands
  everywhere.
- `locations.country_iso2` → `iso2` sits under `continent_code`, a legacy name
  that already named neither its table nor its column and is left.

`games.number_players_on_same_machine` and
`games.number_players_multiple_machines` carry no index. `locations.country_iso3`
carries none either.


### Acceptance

- All five columns exist under their new names.
- `php artisan test` passes.
- A game page renders its player counts, and `/admin/games/{game}` saves them.
- A game page renders its LemonAmiga and Lemon64 links, and the admin VS panel
  adds and removes a pairing.
- Release and magazine listings render country flags, which read `iso2`
  through four Blade files.

## Verification

Run after every unit:

```
php artisan test
php artisan migrate:rollback --step=1 && php artisan migrate
```

Run once at the end, against dev:

```sql
-- no _cross suffix survives
SELECT TABLE_NAME FROM information_schema.TABLES
WHERE  TABLE_SCHEMA = DATABASE() AND TABLE_NAME LIKE '%\_cross';

-- no retired vocabulary survives, in a table or a column name
SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS
WHERE  TABLE_SCHEMA = DATABASE()
AND    (CONCAT(TABLE_NAME, '.', COLUMN_NAME) REGEXP 'pub_dev|submitinfo|submit_info'
   OR   (COLUMN_NAME LIKE '%website%' AND NOT (TABLE_NAME = 'users' AND COLUMN_NAME = 'website'))
   OR   TABLE_NAME LIKE '%website%');

-- the table count is unchanged: this plan renames, it does not add or drop
SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE();
```

The first two return zero rows. The third returns 119, the same figure the
dead-table review left.

The retired vocabulary survives in *identifier* names, and that is expected
rather than a miss. The same scan over index and constraint names —

```sql
SELECT DISTINCT TABLE_NAME, INDEX_NAME FROM information_schema.STATISTICS
WHERE  TABLE_SCHEMA = DATABASE()
AND    INDEX_NAME REGEXP 'pub_dev|website|submitinfo|submit_info'
UNION
SELECT DISTINCT TABLE_NAME, CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
WHERE  TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL
AND    CONSTRAINT_NAME REGEXP 'pub_dev|website|submitinfo|submit_info';
```

— returns 14 rows today and **9 afterwards**, not zero: five naming `pub_dev`
on `game_developer`, `game_releases` and `game_release_distributor`, and four
that `TableRenamer` rewrote by their table word while their column word stayed
(`link_category_website_id_foreign`,
`link_category_website_category_id_foreign`,
`link_category_website_id_website_category_id_unique` and
`game_submission_screenshot_game_submitinfo_id_foreign`). All nine are the
standing decision on constraint names, and the number is stated so a later audit
reads 9 as the end state rather than as unfinished work.

The one exception this campaign does *not* leave standing is Unit 8's stranded
TOS constraint, because that unit makes its correct name legal for the first
time. `SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE
TABLE_SCHEMA = DATABASE() AND CONSTRAINT_NAME LIKE '%tos_version%'` returns one
row today and none at the end.

Re-run the pivot probe from "How the inventory was obtained" — with the
static-method exclusion that section records, or it reports 0 — and confirm it
reports 19 of 37 matching Laravel's derived name, up from 10, with the 18
divergences being the six unreachable ones plus the twelve role-qualified and
owner-first ones this plan keeps on purpose.

Then re-run the plural campaign's own census and confirm it returns the same
seven models it returns today, with three of them renamed:
`GameReleaseMemoryEnhanced`, `GameReleaseSystemEnhanced`, `GameDeveloper`,
`GameIndividual`, `ArticleScreenshot`, `InterviewScreenshot` and
`ReviewScreenshot`. Seven, not the four the plural campaign's write-up records —
the three `Pivot` subclasses derive a singular table through
`AsPivot::getTable()`, which that check reads as a divergence. Nothing new
beyond those seven.

## Deploying

No unit destroys a row and no unit drops a table, so a `mysqldump` is not the
precondition it was for the dead-table campaign. A rollback is real here in a way
it was not there: `migrate:rollback --step=1` restores the previous names
exactly.

Deploy the units one at a time. `.github/workflows/build-and-deploy.yml` runs
`migrate --step=1` on rollback, and a unit is one migration.

Two units need a beat of care on a live site:

- **Unit 2** changes a public URL, `/websites/{website}/screenshot.webp`. A
  browser holding the old path gets a 404 on one image until it reloads the
  page. No redirect is added; adding one would outlive its usefulness.
- **Unit 4** changes a POST route name. Any tab left open on a game page before
  the deploy will post to a route that no longer exists. It is behind auth and
  the window is one deploy long.

Nothing else in the plan is visible from outside the application.

## Out of scope

### Categories A and B

The survey's integrity findings (A1 to A11) and type drift (B1 to B10) are
deliberately excluded, on nicolas's instruction of 2026-09-02: names first.

Three of them sit close enough to this plan's edits to be worth naming, so a
reader does not think they were missed:

- **A6** — Unit 2 renames `website_validates.website_category_id` to
  `category_id` and does not give it the foreign key it lacks.
- **A7** — Units 5 and 6 rename pivots whose key columns are nullable and hold
  no nulls. The nullability is untouched.
- **B5** — Unit 9 renames `game_gallery` and leaves its `image_ext` column
  spelled differently from the nine `imgext` columns elsewhere.

### `game_vs.id` at ordinal position 4 (C11)

The only primary key in the schema that is not its table's first column. It is a
column *position*, not a name, and moving it means rebuilding the table rather
than renaming anything. Recorded so the next audit does not re-derive it.

### `changelogs.action` (C13)

The column holds six values where `Changelog` defines three constants, because
CPANEL wrote `Add`, `Edit` and `Delete shot` into 164 rows. That is a data
normalisation with a `UPDATE` behind it and a question about whether the column
should be an enum — both category B. The column name `action` is correct.

### The `locations` hierarchy (C12)

Unit 12 takes the naming half. The structural half — a country points at its
continent through `continent_code char(2)` rather than a `parent_id`, so the
self-reference carries no foreign key and cannot — is a schema change, not a
rename, and the codes are uppercase while two rows store `country_iso2`
lowercase. Both belong with category A.

`continent_code` itself is not renamed. It names the parent's code, not this
row's, so the prefix is doing work that `iso2` and `iso3` were not.

### `game_releases.status` (C9)

`status` is not a reserved word in MySQL or MariaDB and needs no quoting. It is
also the right word for an enum of `Unfinished` / `Development` / `Unreleased`.
Examined with `order` and left.

### `dumps.track_picture` (C14)

Flagged by the survey as vague. It is a boolean saying the dump has a
track-layout image, and the obvious improvement — `has_track_picture` — would
introduce a `has_` prefix that no other boolean in the schema uses
(`draft`, `inactive`, `hd_installable`). Adopting a boolean-prefix convention is
a decision about all nine boolean columns, which is category B. Left.

### `users.userid` and the auth columns

Excluded by the column-name campaign and still excluded. `userid` is a wrong
name whose rename is a decision about the auth path.

### The storage directories

`images/website_images/` keeps its name after Unit 2, for the reason stated
there. Files on disk are a separate inventory from tables and columns, and no
unit here moves a file.

### The stale constraint and index names

Units 1, 3, 5 and 12 rename columns under constraints and indexes that name the
old column. The schema consistency sweep's standing decision leaves such names:
nothing at runtime reads either, and the literal form is used if a `dropForeign`
or `dropIndex` is ever needed. This plan adds roughly a dozen more and does not
reopen the decision.

### The historical migrations

Every migration dated before this campaign names the tables and columns as they
were on its day, and all of them run before this campaign's migrations on a
`migrate:fresh`. They are correct as written and must not be touched. The
standing rule — no migration added after a rename may name the old name —
applies to this campaign's twelve files and to anything that follows them.

`tests/Feature/FixMenuSoftwareChangelogSectionTest.php` is the pattern to follow
if a migration in this campaign ever needs to be re-run out of order against the
finished schema: it renames the table back to the name its migration knows,
runs, and renames it forward again.
