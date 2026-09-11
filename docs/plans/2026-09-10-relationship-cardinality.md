# Relationship cardinality

*2026-09-10*

The campaign so far has corrected names, keys, indexes and types without
changing what any relation means. This plan changes four relations whose
declared cardinality is wider than the data or the application ever uses: five
many-to-many pivots that carry a one-to-many relation, one pair of tables that
is one-to-one with the foreign key on the parent, and three side-tables that are
strictly one-to-one with the pivot rows they reference.

Every figure below was measured on 2026-09-10 against the dev MariaDB 10.11
database, and each query is written out so it can be re-run.

End state, clause by clause:

- A review names its game with a column: `reviews.game_id` is `int(11)` NOT
  NULL with a foreign key to `games.id`, and `game_review` does not exist.
  Checked by the Unit 1 acceptance queries.
- No screenshot caption lives in a table of its own: `article_screenshot`,
  `interview_screenshot` and `review_screenshot` each carry a `description`
  column, and `article_screenshot_comments`,
  `interview_screenshot_comments` and `review_screenshot_comments` do not
  exist. Checked by the Unit 2 acceptance queries and by
  `grep -rn "ScreenshotComment" app resources tests database`, which returns
  nothing; on 2026-09-10 it returns 24 lines.
- A menu disk dump names its disk: `menu_disk_dumps.menu_disk_id` is
  `bigint(20) unsigned` NOT NULL with a unique index and a cascading foreign
  key, and `menu_disks.menu_disk_dump_id` does not exist. Checked by the Unit 3
  acceptance queries.
- A comment reaches its owner through a real foreign key: `game_comments`,
  `article_comments`, `interview_comments` and `review_comments` each carry the
  comment's text beside a cascading key to what it is on, and `comments`,
  `game_comment`, `article_comment`, `interview_comment` and `review_comment`
  do not exist. Checked by the Unit 4 acceptance queries, including the
  cascade check that deletes a game and watches its comments go.
- Each section moderates its own comments: `artisan route:list --name=comments`
  names `admin.games.comments.*`, `admin.articles.comments.*`,
  `admin.interviews.comments.*` and `admin.reviews.comments.*`, and no route
  name matches `admin.users.comments.*`. Checked by the Unit 4 acceptance.
- No code unwraps a single-element collection to reach one of these four
  owners:
  `grep -rnE -e '->games(->first\(\)|\[0\])' app resources tests database --include=*.php --include=*.blade.php`
  returns nothing once Units 1 and 4 ship. On 2026-09-10 it returns 42 lines,
  every one of them in a file this plan edits.
  `grep -rnE -e '->(articles|interviews|reviews)(->first\(\)|\[0\])' app resources tests database --include=*.php --include=*.blade.php`
  returns one line, `resources/views/games/card_gameinfo.blade.php:77`, which
  reads `Individual::interviews()` — a `hasMany` this plan does not touch. On
  2026-09-10 it returns 7.
- `grep -rc belongsToMany app/Models/*.php | awk -F: '{s+=$2} END {print s}'`
  returns 41; on 2026-09-10 it returns 51. This plan removes ten declarations:
  `Review::games()`, `Game::reviews()`, the four on `Comment`, and `comments()`
  on `Game`, `Article`, `Interview` and `Review` — the last four becoming
  `hasMany`.

**The delivery unit is one commit per unit, based on `development`, not one
pull request.**

| Unit | Tables | Migrations | Rollback |
|---|---|---|---|
| 1 | `reviews`, `game_review` | `2026_09_10_100000_review_game_to_column.php` | `--step=1` |
| 2 | `article_screenshot`, `interview_screenshot`, `review_screenshot` and their three comment tables | `2026_09_10_100100_merge_article_screenshot_comments.php`, `2026_09_10_100200_merge_interview_screenshot_comments.php`, `2026_09_10_100300_merge_review_screenshot_comments.php` | `--step=3` |
| 3 | `menu_disks`, `menu_disk_dumps` | `2026_09_10_100350_delete_orphaned_menu_disk_dumps.php`, `2026_09_10_100400_menu_disk_dump_id_to_child.php` | `--step=2` |
| 4 | `comments` and the four comment pivots | `2026_09_10_100450_delete_ownerless_comments.php`, `2026_09_10_100500_comments_to_four_tables.php` | `--step=2` |

Reverting a unit's commit removes all of that unit's migration files at once,
so Unit 2's revert removes three and Units 3 and 4 two each. The cleanup
migrations delete rows their `down()` cannot restore, so rolling either unit back
returns the schema, not those rows.

Units ship in order 1, 2, 3, 4. Unit 1 must precede Unit 2 because both edit
`resources/views/reviews/card_review.blade.php:22-23`, and must precede Unit 4
because `ReviewComment::getTargetAttribute()` reads `$this->review->game->name`,
which is the relation Unit 1 creates — `app/Models/Comment.php:84` reaches
through both in one expression today. Unit 3 is independent of the other three.

## Unit 1 — the review's game

### The relation

`game_review` holds 127 rows for 127 reviews. No review has two games and no
review has none:

```sql
SELECT (SELECT COUNT(*) FROM reviews)                                           AS reviews,
       (SELECT COUNT(*) FROM game_review)                                       AS pivot_rows,
       (SELECT COUNT(DISTINCT review_id) FROM game_review)                      AS distinct_reviews,
       (SELECT COUNT(DISTINCT game_id) FROM game_review)                        AS distinct_games,
       (SELECT MAX(c) FROM (SELECT COUNT(*) c FROM game_review GROUP BY review_id) x) AS max_games_per_review,
       (SELECT COUNT(*) FROM reviews r LEFT JOIN game_review gr ON gr.review_id = r.id
         WHERE gr.id IS NULL)                                                   AS reviews_without_a_game;
```

| reviews | pivot_rows | distinct_reviews | distinct_games | max_games_per_review | reviews_without_a_game |
|---|---|---|---|---|---|
| 127 | 127 | 127 | 126 | 1 | 0 |

One game carries two reviews (`game_id` 425), which is the many side and stays.

The application cannot express more than one game per review:

- `Admin/Reviews/ReviewsController.php:70-71` reads a single `$request->game`
  and calls `$game->reviews()->save($review)`.
- `Admin/Reviews/ReviewsController::update()` never touches the link, and
  `resources/views/admin/reviews/reviews/card_edit.blade.php:6` prints
  `$review->games[0]->name` as static text — the game input at lines 23-31
  renders only on create.
- `ReviewController.php:111` does the same for the public submission form.
- Every read unwraps the collection. The 42 lines the end-state grep returns
  are all of them, including `Screenstar.php:35` and
  `components/cards/screenstar.blade.php:7`, where `$screenstar` is a `Review`.

`game_review` has no unique index on `review_id`, so a second row changes what
`->games[0]` returns with nothing failing, and duplicates every review in
`Livewire/Admin/ReviewsTable.php:63-64`, which joins the pivot to sort on the
game name.

**The column is `reviews.game_id`, NOT NULL, `int(11)` to match `games.id`.**
Nullable would reintroduce the case the fourteen `->games[0]` reads, over
thirteen lines in four files, already assume away.

### The migration

`2026_09_10_100000_review_game_to_column.php`, following the shape of
`2026_08_25_100000_merge_article_text.php`: assert, add nullable, backfill,
tighten, drop.

- `up()` counts `SELECT review_id FROM game_review GROUP BY review_id HAVING
  COUNT(*) > 1` and throws if it is not 0; counts reviews with no pivot row and
  throws if it is not 0.
- Adds `game_id` `int(11)` nullable after `id`, backfills from `game_review`,
  re-counts `reviews WHERE game_id IS NULL` and throws unless 0, then
  `->nullable(false)->change()`, adds the foreign key to `games.id` with
  `cascadeOnDelete` (matching `game_review_game_id_foreign` today), and drops
  `game_review` last.
- `down()` recreates `game_review` with the DDL it has on 2026-09-10 — surrogate
  `integer('id', true)`, both foreign keys `cascadeOnDelete` — reinserts from
  `reviews` with `insertUsing`, and drops the column. Nothing references
  `game_review.id`, so the projection is lossless.

### The code

- `app/Models/Review.php:34-37`: `games()` becomes
  `game()` → `belongsTo(Game::class)`. Add `game_id` to `$fillable`.
- `app/Models/Game.php:110-113`: `reviews()` becomes `hasMany(Review::class)`.
- `ReviewController.php:110-111` swaps its two saves, so that
  `$game->reviews()->save($review)` runs before
  `$request->user()->reviews()->save($review)`. Both save the same unsaved
  review and the first save is the insert, so with the user first the insert
  carries no `game_id` and the NOT NULL column rejects it.

  ```
  SQLSTATE[23000]: Integrity constraint violation: 19 NOT NULL constraint failed: reviews.game_id
  ```

  `Admin/Reviews/ReviewsController.php:71,74` already saves through the game
  first and does not change.
- Thirty-nine of the 42 unwrap sites become `->game`. The other three unwrap
  `Comment::games()` rather than `Review::games()` —
  `app/Models/Comment.php:78,98` and
  `resources/views/components/cards/partial_comment.blade.php:32` — and Unit 4
  takes them, which is why the end-state grep only empties once both units
  ship. The thirty-nine are in `app/Models/Review.php:68`,
  `app/Models/Comment.php:84`, `app/Http/Controllers/CommentController.php:66`,
  `app/Http/Controllers/ReviewController.php:58,62,167,170`,
  `app/Http/Controllers/Admin/Reviews/ReviewsController.php:35,80,83,138,141,153`,
  `app/View/Components/Cards/Screenstar.php:35`, and the blade files
  `reviews/{card_author,card_review,partial_list,show}.blade.php`,
  `components/cards/{reviews,screenstar}.blade.php`,
  `admin/users/users/card_activity.blade.php`,
  `admin/reviews/reviews/{edit,card_edit}.blade.php`.
- `app/View/Components/Cards/Reviews.php:28` and `Screenstar.php:29`: the eager
  loads become `with(['user', 'game'])` and `with(['user', 'game.releases'])`.
- `app/Livewire/Admin/ReviewsTable.php:63-64`: the two `leftJoin`s become one,
  `->leftJoin('games', 'reviews.game_id', '=', 'games.id')`.
- `app/Helpers/StatisticsHelper.php:27` and
  `app/Helpers/AdminStatisticsHelper.php:166`: `DB::table('game_review')
  ->distinct('game_id')->count()` becomes `DB::table('reviews')
  ->distinct('game_id')->count()`. That compiles to
  `select count(distinct \`game_id\`) as aggregate`, verified on 2026-09-10, and
  returns 126 both before and after.
- `database/factories/ReviewFactory.php`: `definition()` gains
  `'game_id' => GameFactory::new()`, and `forGame()` (lines 48-53) becomes
  `state(fn () => ['game_id' => $gameId ?? GameFactory::new()])` instead of an
  `afterCreating` attach. Ten `Review::factory()` call sites in `tests/` and
  `database/` do not name a game and get one from the definition.
- `database/seeders/E2ESeeder.php:416-419`: the `game_review` insert folds into
  the `reviews` insert above it as `'game_id' => self::GAME_ID`; the table list
  in the docblock at line 21 drops `game_review`.
- `tests/Feature/FactoriesTest.php:251`,
  `tests/Feature/Public/ReviewPagesTest.php:46,72,128` and
  `tests/Feature/Admin/Reviews/ReviewsControllerTest.php:77` assert through
  `->games->first()->name` and become `->game->name`.
- `tests/Feature/Admin/Games/GameControllerTest.php:272` reaches its review with
  `$game->reviews()->attach(...)`, and `HasMany` defines no `attach()`. It names
  `game_id` on the factory instead, the way the `releases` case at line 255
  already does.

`database/migrations/2025_12_30_113644_review_constraints.php:137` names
`review_game_review_id_foreign`. It is a historical migration against a table
name two renames old and is not edited.

### Acceptance

- `SELECT COUNT(*) FROM reviews r JOIN <pre-migration game_review> gr ON gr.review_id = r.id WHERE r.game_id != gr.game_id` returns 0, run against the pre-migration dump before it is discarded.
- `SELECT COUNT(*) FROM reviews WHERE game_id IS NULL` returns 0, and `SHOW CREATE TABLE reviews` names one foreign key to `games`.
- `SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'game_review'` returns 0.
- `DB::table('reviews')->distinct('game_id')->count()` returns 126, the same figure the admin Statistics page's "With a review" row showed before the unit.
- `./vendor/bin/sail artisan test` passes.
- `php artisan migrate:fresh` and `php artisan db:seed --class=E2ESeeder` complete against the e2e database, and `tests/e2e/public/reviews.spec.js` and `tests/e2e/admin/content.spec.js` pass. The seeder writes through `DB::table()->updateOrInsert()`, which no model reaches, so it is the gate that catches a `game_review` insert left behind.
- Creating a review in the admin UI stores the chosen game, and the admin Reviews table still sorts on the game name.
- Submitting a review through the public form at `/reviews/submit` stores the chosen game. This is the ordering gate: with the two saves the other way round the insert fails with the NOT NULL error above, and `tests/e2e/public-write/reviews.spec.js` covers the same path in the browser.
- `grep -rn "reviews()->attach" app tests database` returns nothing: `hasMany` has no `attach()`, so a caller that kept it would throw `BadMethodCallException` rather than fail a comparison.

## Unit 2 — the screenshot captions

### The tables

Each of the three caption tables is `(id, parent_id, text)` and is exactly
one-to-one with the pivot row it points at. No pivot row lacks a caption row
and no pivot row has two:

```sql
SELECT 'article' AS owner,
       (SELECT COUNT(*) FROM article_screenshot)                                      AS pivot_rows,
       (SELECT COUNT(*) FROM article_screenshot_comments)                             AS caption_rows,
       (SELECT COUNT(DISTINCT article_screenshot_id) FROM article_screenshot_comments) AS distinct_parents,
       (SELECT COUNT(*) FROM article_screenshot p
         LEFT JOIN article_screenshot_comments c ON c.article_screenshot_id = p.id
         WHERE c.id IS NULL)                                                          AS pivots_without_a_caption;
-- and the same for interview_ and review_.
```

| owner | pivot_rows | caption_rows | distinct_parents | pivots_without_a_caption |
|---|---|---|---|---|
| article | 78 | 78 | 78 | 0 |
| interview | 1168 | 1168 | 1168 | 0 |
| review | 798 | 798 | 798 | 0 |

`text` is `mediumtext NOT NULL` in all three, and the foreign key cascades on
delete.

The application already treats the caption as a field of the pivot. Every
reader is `$screenshot->pivot->comment->text` or `?->text`
(`articles/card_article.blade.php:22-26`,
`interviews/card_interview.blade.php:32-36`,
`reviews/card_review.blade.php:22-25`,
`admin/{articles,interviews}/.../card_images.blade.php:38`,
`admin/reviews/reviews/card_edit.blade.php:181`), and every writer creates the
pivot row and the caption row together in the same block
(`Admin/Articles/ArticleController.php:191-208`,
`Admin/Interviews/InterviewsController.php:186-203`,
`Admin/Reviews/ReviewsController.php:109-132`, `ReviewController.php:117-134`).
Each of those four blocks is a three- or four-way branch over whether the
caption row exists and whether the submitted value is empty — the branch a
column on the pivot does not need.

Two readers already differ on whether a caption can be absent:
`reviews/card_review.blade.php:25` reads `$screenshot->pivot->comment->text`
unguarded while lines 22-23 use `?? ''`, and
`database/seeders/E2ESeeder.php:445-449` records that an interview screenshot
seeded without its caption row returns a 500 rather than an empty caption.

**The column is `description`, nullable.** `description` is what the admin
textareas are already named (`card_images.blade.php:38`,
`name="description-{id}"`) and what CLAUDE.md calls it; `text` on a pivot row
says nothing about which of the two joined rows it belongs to. Nullable, because
`Admin/Reviews/ReviewsController.php:117-119` deletes the pivot row when the
caption is cleared and `ReviewController.php:122` only inserts a pivot row when
there is a caption to go with it — a pivot row with no caption is reachable
through `$review->screenshots()->attach()` and is not an error.

### The migrations

Three migrations, one per owner, identical in shape and following
`2026_08_25_100000_merge_article_text.php`:

- `up()` throws if `SELECT <parent>_id FROM <caption table> GROUP BY <parent>_id
  HAVING COUNT(*) > 1` is not empty, records `COUNT(*)` of the caption table,
  adds `description` `mediumText` nullable to the pivot, backfills with the
  correlated subquery, throws unless `COUNT(*) WHERE description IS NOT NULL`
  equals the recorded count, then drops the caption table.
- `down()` recreates the caption table with its 2026-09-10 DDL — `integer('id',
  true)`, `integer('<parent>_id')`, `mediumText('text')` NOT NULL, the foreign
  key `cascadeOnDelete` — reinserts every pivot row whose `description` is not
  null, and drops the column. Nothing references the caption tables' `id`, so
  the projection is lossless. Rows written after the migration with a null
  `description` come back as no caption row, which is the shape `down()` is
  restoring to.

### The code

- Delete `app/Models/ArticleScreenshotComment.php`,
  `InterviewScreenshotComment.php`, `ReviewScreenshotComment.php`.
- `app/Models/ArticleScreenshot.php:11-14`, `InterviewScreenshot.php:11-14`,
  `ReviewScreenshot.php:11-14`: delete `comment()`. The `Pivot` subclasses stay
  — `Article::screenshots()`, `Interview::screenshots()` and
  `Review::screenshots()` name them with `->using()`.
- `Article.php:31-38`, `Interview.php:36-43`, `Review.php:39-46`:
  `->withPivot('id')` becomes `->withPivot('id', 'description')`.
- Readers: `$screenshot->pivot->comment->text`, `->comment?->text` and
  `->comment->text ?? ''` all become `$screenshot->pivot->description`. Three
  guards name the relation too, and all three take `description` in place of
  `comment`: `articles/card_article.blade.php:22-23` carries
  `@isset($screenshot->pivot->comment)` inline in the `title` and `alt`
  attributes, line 25 of the same file guards the caption with
  `@if (isset($screenshot->pivot->comment))`, and
  `interviews/card_interview.blade.php:35` is an `@isset`. Left alone they
  evaluate false once `comment()` is gone, which empties the title and alt text
  with nothing failing. `reviews/card_review.blade.php:25` gains the guard the
  other two have.
- `Admin/Articles/ArticleController.php:191-208` and
  `Admin/Interviews/InterviewsController.php:186-203`: the three-way branch
  over `$comment` and `$value` collapses to
  `$pivot->update(['description' => $value ?: null])` on the pivot the
  `findOrFail()` above it already loaded.
- `Admin/Reviews/ReviewsController.php:109-132`: the four-branch block collapses
  to `$review->screenshots()->syncWithoutDetaching([$screenshotId =>
  ['description' => $value]])` for a non-null value and
  `$review->screenshots()->detach($screenshotId)` for a null one; the
  `ReviewScreenshotComment` import at line 10 goes.
- `ReviewController.php:123-131`: the `DB::table('review_screenshot')
  ->insertGetId()` plus `new ReviewScreenshotComment` pair becomes
  `$review->screenshots()->attach($gameScreenshot, ['description' =>
  $screenshotComment])`; the import at line 12 goes.
- `Review.php:54-57` and `Interview.php:53-56`: `getScreenshotComment()` returns
  the pivot-bearing screenshot and keeps its name and body;
  `admin/reviews/reviews/card_edit.blade.php:181` reads
  `?->pivot?->description` off it.
- `database/seeders/E2ESeeder.php:401-408,451-458`: the two
  `*_screenshot_comments` inserts fold into the pivot inserts above them as
  `'description' => self::ARTICLE_SCREENSHOT_CAPTION` and
  `self::INTERVIEW_SCREENSHOT_CAPTION`; the comment at lines 445-449 keeps its
  point with the column named instead of the table.
- `tests/Feature/Admin/Reviews/ReviewsControllerTest.php:8,196-220` and
  `tests/Feature/Public/ReviewPagesTest.php:9,216-230` assert through
  `ReviewScreenshotComment::sole()->text` and become assertions on
  `DB::table('review_screenshot')` rows and their `description`.

### Acceptance

- `SELECT COUNT(*) FROM <pivot> p JOIN <pre-migration caption table> c ON c.<parent>_id = p.id WHERE p.description != c.text` returns 0 for all three, run against the pre-migration dump before it is discarded.
- `SELECT COUNT(*) FROM article_screenshot WHERE description IS NULL` returns 0, and likewise for the other two — every row measured above had a caption.
- `SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name LIKE '%screenshot_comments'` returns nothing.
- `grep -rn "ScreenshotComment" app resources tests database` returns nothing.
- `php artisan migrate:rollback --step=3` then `php artisan migrate` restores all 2044 caption rows with their text unchanged.
- `./vendor/bin/sail artisan test` passes.
- `php artisan db:seed --class=E2ESeeder` completes, and `tests/e2e/public/{articles,interviews,reviews}.spec.js` and `tests/e2e/admin/content.spec.js` pass.
- In the admin UI: setting, changing and clearing a caption on an article, an interview and a review screenshot each round-trip, and clearing a review caption still removes the screenshot from the review.

## Unit 3 — the menu disk dump

### The relation

`menu_disks.menu_disk_dump_id` is a one-to-one link declared from the parent.
`MenuDisk::menuDiskDump()` is a `belongsTo` (`app/Models/MenuDisk.php:31-34`)
and `MenuDiskDump::menuDisk()` is a `hasOne`
(`app/Models/MenuDiskDump.php:16-19`) — neither side is a to-many. There is no
unique index on the column, so the shape is not enforced:

```sql
SELECT (SELECT COUNT(*) FROM menu_disks WHERE menu_disk_dump_id IS NOT NULL)          AS refs,
       (SELECT COUNT(DISTINCT menu_disk_dump_id) FROM menu_disks
         WHERE menu_disk_dump_id IS NOT NULL)                                         AS distinct_dumps,
       (SELECT COUNT(*) FROM menu_disk_dumps)                                         AS dumps,
       (SELECT COUNT(*) FROM menu_disks md LEFT JOIN menu_disk_dumps d ON d.id = md.menu_disk_dump_id
         WHERE md.menu_disk_dump_id IS NOT NULL AND d.id IS NULL)                     AS dangling_refs,
       (SELECT COUNT(*) FROM menu_disk_dumps d LEFT JOIN menu_disks md ON md.menu_disk_dump_id = d.id
         WHERE md.id IS NULL)                                                         AS unreferenced_dumps;
```

| refs | distinct_dumps | dumps | dangling_refs | unreferenced_dumps |
|---|---|---|---|---|
| 3825 | 3825 | 3827 | 0 | 2 |

The two unreferenced dumps are `id` 592 and 3052, both MSA, both created
2021-04-11. `Admin/Menus/MenuDisksController::destroy()` (lines 292-321) deletes
a disk and its contents and never deletes the disk's dump, which is how a dump
outlives its disk. `CheckMenuDumps.php:47` carries `$dump->menuDisk
?->download_basename ?? 'Unknown'` for the same reason, and
`MenuDisksController::destroyDump()` reads `$dump->menuDisk->id` in its own
guard condition at line 272, which is a fatal error on either of those two rows.

The games side of the same relation is already a child-side foreign key:
`dumps.media_id`, 217 dumps across 215 media, two of them a second format on one
medium.

**The foreign key moves to the child as `menu_disk_dumps.menu_disk_id`, not
merged into `menu_disks`.** The dump ZIPs are named
`zips/menus/{menu_disk_dump_id}.zip`
(`MenuDisksController.php:262`, `components/cards/menu.blade.php:70`,
`menus/partial_menudisk.blade.php:121`, `games/card_menus.blade.php:101`,
`admin/menus/disks/card_dump.blade.php:15`,
`components/cards/latest-menus.blade.php:51`, `CheckMenuDumps.php:43`), so a
merge renames 3825 files on the production filesystem; inverting leaves every
dump id, and therefore every ZIP name, unchanged.

**`menu_disk_id` is NOT NULL with a unique index.** Unique keeps the one-to-one
the two relations already declare; a plain index would be the `dumps.media_id`
shape, which no reader in `app/` or `resources/` is written for — all thirty-odd
of them read `$disk->menuDiskDump` as a single object.

**A migration deletes the unreferenced dumps, by predicate rather than by id.**
`menu_disks` is the only way in to a dump, so a dump no disk references is
unreachable: no page renders it, no download links to it, and
`menus:check-dumps` can only label it `Unknown`. Deleting rows nothing can reach
is what makes the column NOT NULL, and `down()` restoring them would have no
value — `2026_09_10_100400`'s own `down()` does not recreate them either. The
predicate, not the two ids measured here, is what the migration deletes on, so
it holds on any database and needs no re-measurement before a deploy.

### The migration

`2026_09_10_100400_menu_disk_dump_id_to_child.php`:

- `up()` counts unreferenced dumps with the query above and throws if it is not
  0:

  ```
  RuntimeException: 2 menu_disk_dumps rows have no menu disk; delete them before migrating.
  ```

  `2026_09_10_100350_delete_orphaned_menu_disk_dumps.php` runs first and empties
  that count, so the guard is the check that it did rather than a step a person
  has to take. The cleanup selects on the same predicate, prints each row it is
  about to delete — id, format, upload date and the head of its sha512 — so the
  deploy log records what went, then deletes those ids. Its `down()` does
  nothing.
- `up()` also throws if any dump is referenced by more than one disk.
- Adds `menu_disk_id` `unsignedBigInteger` nullable, backfills from
  `menu_disks`, throws unless every dump row is filled, then
  `->nullable(false)->change()`, adds a unique index and the foreign key to
  `menu_disks.id` with `cascadeOnDelete`, and drops
  `menu_disks_menu_disk_dump_id_foreign` and the column last.
- `down()` re-adds `menu_disks.menu_disk_dump_id` nullable with its foreign key,
  backfills from `menu_disk_dumps`, then drops the unique index, the foreign key
  and the column. Disks with no dump come back NULL, which is what they are
  today.

`cascadeOnDelete` is the behaviour change this unit ships: deleting a disk
deletes its dump row. It does not delete the ZIP, so
`MenuDisksController::destroy()` gains the
`Storage::disk('public')->delete('zips/menus/' . $dump->id . '.zip')` that
`destroyDump()` already has at line 273.

### The code

The relation keeps its name on both sides, so the 33 blade readers of
`$disk->menuDiskDump` and the three eager loads
(`GameController.php:126`, `MenuSetController.php:41`,
`LatestMenus.php:33`) are untouched.

- `app/Models/MenuDisk.php:31-34`: `menuDiskDump()` becomes
  `hasOne(MenuDiskDump::class)`.
- `app/Models/MenuDiskDump.php:16-19`: `menuDisk()` becomes
  `belongsTo(MenuDisk::class)`; add `menu_disk_id` to `$fillable`.
- `Admin/Menus/MenuDisksController.php:241-247`: `MenuDiskDump::create([...])`
  followed by `$disk->menuDiskDump()->associate($dump); $disk->save();` becomes
  `$disk->menuDiskDump()->create([...])`.
- `MenuDisksController.php:272-276`: `$dump->menuDisk->menuDiskDump()
  ->dissociate(); $dump->menuDisk->save();` goes — `$dump->delete()` is now
  enough. The guard at line 272 becomes `$dump->menu_disk_id === $disk->getKey()`,
  which is also what stops the fatal error on a dump with no disk.
- `MenuDisksController::destroy()` deletes the dump's ZIP before
  `$disk->delete()` cascades the row away.
- `app/Helpers/AdminStatisticsHelper.php:184`:
  `DB::table('menu_disks')->whereNotNull('menu_disk_dump_id')->count()` becomes
  `DB::table('menu_disk_dumps')->count()`, which returns 3825 once the two
  orphans are gone.
- `app/View/Components/Cards/MenuDisk.php:34` keeps `->has('menuDiskDump')`;
  `has()` works the same on a `hasOne`.
- `tests/Feature/Admin/Menus/MenuDisksTest.php:111,183` replace
  `$disk->menuDiskDump()->associate($dump)` with creating the dump against the
  disk; lines 139, 196, 238, 258 and 284 assert on
  `$disk->fresh()->menu_disk_dump_id` and become assertions on
  `$disk->fresh()->menuDiskDump`.
  `test_a_dump_is_not_removed_through_a_different_disk` (line 268) is the test
  that covers the `destroyDump()` guard.
- `tests/Feature/Console/MaintenanceCommandsTest.php:115` sets
  `'menu_disk_dump_id' => $dump->getKey()` on a disk and becomes
  `'menu_disk_id' => $disk->getKey()` on the dump.

`database/seeders/E2ESeeder.php` seeds no dump, so no e2e spec covers this path;
`tests/Feature/Admin/Menus/MenuDisksTest.php` is the whole gate.

### Acceptance

- `SELECT COUNT(*) FROM menu_disk_dumps` returns 3825, the cleanup migration having deleted the 2 rows with no disk and named them in the migrate output.
- `SELECT COUNT(*) FROM menu_disk_dumps d JOIN <pre-migration menu_disks> md ON md.menu_disk_dump_id = d.id WHERE d.menu_disk_id != md.id` returns 0, run against the pre-migration dump before it is discarded.
- `SELECT COUNT(*) FROM menu_disk_dumps WHERE menu_disk_id IS NULL` returns 0, and `SHOW CREATE TABLE menu_disk_dumps` names a unique index on `menu_disk_id` and a cascading foreign key to `menu_disks`.
- `SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'menu_disks' AND column_name = 'menu_disk_dump_id'` returns 0.
- `php artisan migrate:rollback --step=1` then `php artisan migrate` puts all 3825 links back on the same disks.
- `artisan menus:check-dumps` reports 3825 dumps checked and no row labelled `Unknown`.
- `./vendor/bin/sail artisan test` passes, including the eight dump tests in `tests/Feature/Admin/Menus/MenuDisksTest.php`.
- In the admin UI: uploading a dump to a disk that has none, replacing one, and removing one all work, and deleting a disk that has a dump removes both the row and its ZIP.

## Unit 4 — the four comment tables

### The relation

A comment belongs to exactly one of a game, an article, an interview or a
review. Across all four pivots, no comment appears twice:

```sql
SELECT owners, COUNT(*) AS comments FROM (
  SELECT c.id,
         (c.id IN (SELECT comment_id FROM game_comment))
       + (c.id IN (SELECT comment_id FROM article_comment))
       + (c.id IN (SELECT comment_id FROM interview_comment))
       + (c.id IN (SELECT comment_id FROM review_comment)) AS owners
  FROM comments c) t
GROUP BY owners ORDER BY owners;
```

| owners | comments |
|---|---|
| 0 | 2 |
| 1 | 983 |

983 = 936 `game_comment` + 26 `review_comment` + 18 `interview_comment` + 3
`article_comment`. The two with no owner are `id` 41 and 43, both by `user_id`
3 on 2004-06-12, near-duplicates of each other, both beginning "My first
experiences with a computer pinbal game was in 1994".

`app/Models/Comment.php:29,35,41,47` carry `// FIXME: Should be N:1` on all
four declarations. `getTypeAttribute()` (lines 55-68) issues up to four queries
to find out what a comment is on, and throws `Unknown comment type` on the two
ownerless rows. `getTargetAttribute()` and `getTargetIdAttribute()` (lines
74-108) then switch on the result and unwrap the collection again.

The split costs a cascade that the database would otherwise do.
`Admin/Games/GameController.php:143-161` deletes a game inside a transaction
and sweeps its comments by hand, with the reason written out at lines 144-155:
`game_comment` cascades but the `comments` row it points at does not, and a
comment that belongs to nothing throws out of `Comment::getType()`, which
"would take out the admin comments table".

**Each owner gets its own comment table, and `comments` is dropped.**
`game_comments`, `article_comments`, `interview_comments`, `review_comments`,
each `(id, <owner>_id, user_id, text, created_at, updated_at)`. Every comment
then reaches its owner through a real foreign key that cascades, which is what
removes the hand-sweep above, and the table a comment is in is what says which
section it is on.

**The names are plural.** These are entity tables after the split, not
pivots, so they follow `article_screenshot_comments` and
`2026-08-29-plural-table-rename.md` rather than the singular pivot convention
`2026_09_06_100600_comment_pivots_to_owner_first.php` gave them.

**`user_id` keeps the shape `comments` has: `int(11)` NOT NULL DEFAULT 0, an
index, and no foreign key.** `2026_08_28_120000_user_side_foreign_keys.php`
ruled that a user can be deleted and what they wrote stays, and that NOT NULL
columns therefore take no constraint at all rather than a column change —
`Comment::user()` is a `belongsTo` that returns null for a dangling id, and
`Helper::user()` answers "Former user". Four new tables do not reopen that.

**A migration deletes the ownerless comments, by predicate rather than by id.**
A comment reaches what it is on through one of the four pivots, so a row in none
of them is unreachable: no page has ever rendered it, and `getTypeAttribute()`
throws `Unknown comment type` on it rather than displaying it. `comments` is
dropped here, so such a row has no table to go to, and `down()` restoring a row
nothing could reach would have no value. The predicate, not the two ids measured
here, is what the migration deletes on, so it holds on any database and needs no
re-measurement before a deploy.

### The admin screens

One admin screen becomes four. `app/Livewire/Admin/CommentsTable.php` is a
single paginated datatable over every comment — Date sortable, Content
searchable with `where text like`, a Type/target column, an author filter
built from `User::has('comments')` — and `DataTableComponent::builder()`
returns one Eloquent `Builder`, which four tables cannot produce.

- `app/Livewire/Admin/CommentsTable.php` becomes an abstract base holding
  `configure()`, `columns()` and `filters()`, with `GameCommentsTable`,
  `ArticleCommentsTable`, `InterviewCommentsTable` and `ReviewCommentsTable`
  naming the model, its table, the `User` relation the author filter counts,
  and the edit route. The table name is qualified in three places today, not
  one: the `users` join in the User sort (line 29), the Date sort (line 37) and
  the author filter's `comments.user_id` (line 86).
- The Type column (lines 39-44) loses `Str::ucfirst($row->type)` and keeps
  `$row->target` alone, the heading now saying which section it is. The Type
  `SelectFilter` (lines 75-83) goes entirely — it filters `$query->has()` over
  the four relation names, and a table that holds one type has nothing to
  filter. The author filter stays, with `User::has('comments')` becoming
  `User::has($this->userRelation)` over the four relations `User` now
  declares.
- `app/Http/Controllers/Admin/User/CommentController.php` becomes an abstract
  base with the same four subclasses; `COMMENT_CHANGELOG_SECTIONS` (lines
  16-19) is replaced by a `SECTION` constant on each. `destroy()` loses the
  read-before-delete dance at lines 67-73 — the section is the class, and the
  target is reachable through a foreign key that is still there while the row
  is.

**A comment screen belongs to the section it moderates, not to Users.** A
comment's section is the table it is in, so Games, Articles, Interviews and
Reviews each carry their own screen and Users carries none. The nav accordion
reads route names — `@showroute('admin.games.*')` is what opens the Games group
and `@activeroute` what marks the link inside it
(`app/Providers/AppServiceProvider.php:44-54`) — so the route name is what
decides which group a screen appears under, and the route is declared in that
section's group.

- `routes/admin.php:182`: the one resource under `/users` goes, and the `/games`,
  `/reviews`, `/interviews` and `/articles` groups in the same file each gain
  `Route::resource('comments', <Section>CommentController::class)
  ->except(['create', 'store', 'show'])`. The route names are
  `admin.{games,articles,interviews,reviews}.comments.{index,edit,update,destroy}`
  and the URLs `/admin/{section}/comments`. A resource named `comments` derives
  the `{comment}` parameter on its own, so no `->parameters()` override is
  needed.
- The four subclasses live in the section folders the routes reaching them live
  in: `app/Http/Controllers/Admin/{Games,Articles,Interviews,Reviews}/`. The
  abstract base belongs to no one section and sits directly in
  `app/Http/Controllers/Admin/CommentController.php`, beside `HomeController`.
  `Admin/User/` keeps `UserController`, which is all that is a Users screen.
- `resources/views/admin/comments/{index,card_list,edit,card_edit,datatable_actions}.blade.php`
  take the section's route prefix and Livewire component as passed variables
  rather than being copied four times. One shared set parameterised by the
  section has no section folder to sit in, so it takes the same neutral place
  the abstract controller takes.
- `resources/views/admin/layouts/nav.blade.php:158`: the one Comments entry
  under Users goes, and each of the four section groups gains a `Comments`
  entry keyed on `@activeroute('admin.{section}.comments.*')`. The group already
  names the section, so the link does not repeat it.

### The migration

`2026_09_10_100500_comments_to_four_tables.php`:

- `up()` runs the census above and throws unless the `owners > 1` bucket is
  empty and the `owners = 0` bucket is empty:

  ```
  RuntimeException: 2 comments belong to nothing; delete them before migrating.
  ```

  `2026_09_10_100450_delete_ownerless_comments.php` runs first and empties the
  `owners = 0` bucket, so the guard is the check that it did rather than a step a
  person has to take. The cleanup selects on the same predicate, prints each row
  it is about to delete — id, author, date and the first 60 characters of the
  text — so the deploy log records what went, then deletes those ids. Its
  `down()` does nothing.

- Creates the four tables. Each: `integer('id', true)`; `integer('<owner>_id')`
  with a `cascadeOnDelete` foreign key, matching what the pivot has today;
  `integer('user_id')->default(0)` with an index and no constraint;
  `mediumText('text')->nullable()`; `dateTime('created_at')->nullable()` and
  `dateTime('updated_at')->nullable()`.
- Records `COUNT(*)` of each of the four pivots, then backfills each new table
  with `insertUsing` over `comments` joined to its pivot, carrying `comments.id`
  into the new `id` so existing keys survive — which is what "Out of scope"
  records for `changelogs`.
- Counts each new table and throws unless it equals the count recorded for its
  pivot. The four are 936, 3, 18 and 26 on the dev database on 2026-09-10, and
  are not written into the migration: `comments` has live writers and the
  deploy is not the measurement.
- Drops the four pivots and `comments` last.
- `down()` recreates `comments` and the four pivots with their 2026-09-10 DDL,
  reinserts `comments` from the four tables with `insertUsing` carrying the ids
  through, reinserts each pivot from the same ids, and drops the four tables.
  The two deleted rows do not come back. The ids survive the round trip because
  `up()` took them from one sequence, so the four tables hold disjoint ids until
  each starts handing out its own; a `down()` run after new comments have been
  written can therefore meet a duplicate key, and throws on it rather than
  renumbering. Renumbering would silently repoint every `changelogs` row that
  names a comment.

### The code

- Delete `app/Models/Comment.php`'s four `belongsToMany` declarations, its
  three derived attributes and its `HasFactory` body; what stays is an abstract
  `Comment` the four models extend, holding `$fillable`, `user()`, a `SECTION`
  constant and abstract `getTargetAttribute()` and `getTargetIdAttribute()`.
  There are no casts to carry over: `created_at` and `updated_at` are
  Eloquent's own.
  `TYPE_GAME`/`TYPE_REVIEW`/`TYPE_INTERVIEW`/`TYPE_ARTICLE` (lines 13-16)
  become each subclass's `SECTION`.
- Add `app/Models/{Game,Article,Interview,Review}Comment.php`: `$table`, the
  owner id appended to `$fillable`, the owner `belongsTo`, and
  `getTargetAttribute()` returning `$this->game->name`,
  `$this->article->title`, `$this->interview->individual->name` and
  `$this->review->game->name` — the last being why this unit follows Unit 1.

**No relation named `owner()` stands in for the four owner relations.** A
`belongsTo` that the base declares and a subclass fills by returning
`$this->game()` derives its key from the method that calls `belongsTo()`, so the
key is `game_id` while the method name is `owner` —
`RelationshipKeyAudit::relations()` reports that as divergent, and
`tests/Feature/RelationshipKeyConventionsTest.php:43-64` fails on a divergent
relation that its `DECLINED` registry does not list. The registry's four
accepted reasons are self-referential pivots, pivot subclasses, a table whose
model name diverges, and a method rename declined on cost; a delegating
accessor is none of them, and the docblock at lines 25-42 records that no fifth
reason has been accepted. Each subclass implements `getTargetIdAttribute()`
returning its own foreign key column instead, which also reads the id without a
query.

- `app/Models/Game.php:173`, `Article.php:45-48`, `Interview.php:45-48`,
  `Review.php:59-62`: `comments()` becomes `hasMany(GameComment::class)` and so
  on.
- The four `postComment()` writers (`GameController.php:186-187`,
  `ArticleController.php:62-63`, `InterviewController.php:51-52`,
  `ReviewController.php:160-161`) construct the matching model and swap their
  two saves for the reason Unit 1 records, so that
  `$owner->comments()->save($comment)` runs before
  `$request->user()->{$owner}Comments()->save($comment)`: the owner save has to
  be the insert, because the owner id is NOT NULL.

  ```
  SQLSTATE[23000]: Integrity constraint violation: 19 NOT NULL constraint failed: game_comments.game_id
  ```
- `app/Models/User.php:101-104`: `comments()` becomes `gameComments()`,
  `articleComments()`, `interviewComments()` and `reviewComments()`.
  `resources/views/admin/users/users/card_activity.blade.php:68-73` reads those
  four directly instead of
  `$user->comments->pluck('games')->flatten()->count()`.
- `app/Http/Controllers/Admin/Games/GameController.php:143-161`: the
  `DB::transaction`, the `$commentIds = $game->comments->modelKeys()` read and
  the `Comment::destroy($commentIds)` after the delete all go —
  `game_comments.game_id` cascades. The block comment at 144-155 goes with
  them.
- `app/Http/Controllers/Admin/Games/GameSubmissionController.php:70-77`:
  `new Comment([...])` plus `$comment->games()->attach($submission->game)`
  becomes one `GameComment::create([... 'game_id' => $submission->game_id])`,
  which is why `game_id` has to be fillable —
  `AppServiceProvider::preventSilentAttributeErrors()` turns on
  `Model::preventSilentlyDiscardingAttributes()` outside production, so a
  create naming a column the model does not list throws in dev and in the test
  suite.
- `app/Http/Controllers/CommentController.php:20,35`:
  `Comment::find($request->comment_id)` becomes a lookup on the model the
  posted `context` names, through a `['game' => GameComment::class, …]` map;
  `routes/web.php:64-65` keep their paths and names.
- `resources/views/components/cards/partial_comment.blade.php`: the delete form
  at lines 20-23 gains the `context` and `id` hidden inputs the edit form
  already posts at lines 51-52 — without them `CommentController::delete()`
  cannot tell which table the id is in. Line 31's
  `$comment->games->isNotEmpty()`/`$comment->games->first()` becomes
  `$comment->game`, and the contributor pencil at line 38 routes through
  `admin.{$context}s.comments.edit` rather than one
  `admin.users.comments.edit`.
- `resources/views/components/cards/latest-comments.blade.php:7` passes
  `'context' => 'game'` alongside `showGame`, which the pencil and the delete
  form now need; the card is game-only already.

**A comment post with no `context` resolves no comment and changes nothing.** A
comment id identifies a row only within its own table, so `context` is what
`CommentController::update()` and `delete()` look the id up by, and a post
without one has no table to look in. All five includes of
`partial_comment.blade.php` pass a context — `{games,reviews,interviews,
articles}/card_comments.blade.php:23` and `latest-comments.blade.php:7` — so no
page reaches either route without one. `tests/Feature/Public/ContentPagesTest.php:471-493`
asserts the old behaviour, an edit going through unlogged because
`insertChangelog()` returns early on a null context; it becomes an assertion
that the comment's text is unchanged and no changelog row is written.

- `app/View/Components/Cards/LatestComments.php:39-40`:
  `Comment::select('comments.*')->join('game_comment', …)` becomes
  `GameComment::query()`.
- `app/Helpers/AdminStatisticsHelper.php:136`: `DB::table('comments')->count()`
  becomes the sum of the four counts. Line 484's
  `bucketByYear(DB::table('comments')->pluck('created_at'))` concatenates the
  four plucks.
- `database/factories/CommentFactory.php` becomes `GameCommentFactory`,
  `ArticleCommentFactory`, `InterviewCommentFactory` and
  `ReviewCommentFactory`; each `definition()` carries the owner factory, and
  the four `on…()` states (lines 35-61) go.
- `database/seeders/E2ESeeder.php:481-493`: the `comments` insert and the
  `game_comment` insert fold into one `game_comments` insert; the comment at
  481-483 about the pivot not being optional goes, and the table list in the
  docblock at line 21 drops `game_comment`.
- Tests: `tests/Feature/FactoriesTest.php:112,126-136` (the four `->type`
  assertions become four factory classes),
  `tests/Feature/Public/ContentPagesTest.php:109,154,386,420,443-449,462-468,486-491,511`,
  `tests/Feature/Public/GamePageTest.php:313-327`,
  `tests/Feature/Public/ReviewPagesTest.php:256-271`,
  `tests/Feature/Admin/Other/RemainingSectionsTest.php:397-408`,
  `tests/Feature/Admin/Games/GameControllerTest.php:289,322`,
  `tests/Feature/Admin/Games/GameSubmissionTest.php:123,148` and
  `tests/Feature/Admin/AdminRenderTest.php:106` all reach comments through
  `Comment::sole()`, `Comment::query()->count()` or `Comment::factory()` and
  name the section's model instead. `tests/Feature/Admin/Tables/AdminTablesTest.php`
  gains a case per new Livewire table.
- `tests/e2e/support/comments.js` drives the shared comment box and needs no
  change. The four admin screens are covered where their sections already are:
  `tests/e2e/admin/games.spec.js` gains the game comments list and edit form
  beside the other section-level screens, and the three content sections gain a
  `comments` entry in the `extra` list `tests/e2e/admin/content.spec.js:16,23,30,36`
  already uses for submissions and types. `tests/e2e/admin/users.spec.js` loses
  the comments screen it visited. `tests/e2e/admin/editor.spec.js:34` and
  `tests/e2e/public-write/content.spec.js:92,105` name a comment edit URL and
  take the section's.

### Acceptance

- Each of `game_comments`, `article_comments`, `interview_comments` and `review_comments` holds the row count its pivot held before the migration, which is what `up()` asserts before it drops anything. On 2026-09-10 that was 936, 3, 18 and 26; `comments` takes writes, so the four figures move and the equality is the gate rather than the numbers.
- `SELECT COUNT(*) FROM game_comments gc JOIN <pre-migration comments> c ON c.id = gc.id JOIN <pre-migration game_comment> p ON p.comment_id = c.id WHERE gc.game_id != p.game_id OR gc.text <=> c.text = 0 OR gc.user_id != c.user_id` returns 0, and likewise for the other three, run against the pre-migration dump before it is discarded.
- `SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name IN ('comments', 'game_comment', 'article_comment', 'interview_comment', 'review_comment')` returns 0.
- `SHOW CREATE TABLE game_comments` names a cascading foreign key to `games` and no constraint on `user_id`, and likewise for the other three.
- `DELETE FROM games WHERE id = <a game with comments>` in a transaction removes that game's rows from `game_comments` with no application code running, then is rolled back.
- `grep -rn "FIXME: Should be N:1" app/Models` returns nothing, and `grep -rn "Comment::destroy\|modelKeys()" app/Http/Controllers/Admin/Games/GameController.php` returns nothing.
- `grep -n "function owner()" app/Models/*Comment.php` returns nothing, and `tests/Feature/RelationshipKeyConventionsTest.php` passes without a new `DECLINED` entry.
- Posting a comment through each of `games.comment`, `article.comment`, `interview.comment` and `review.comment` writes one row carrying both its owner id and its author. This is the ordering gate: with the two saves the other way round the insert fails with the NOT NULL error named above.
- `POST /comments/update` carrying a `comment_id` and no `context` leaves the comment's text unchanged and writes no changelog row.
- `php artisan migrate:rollback --step=1` then `php artisan migrate` puts every comment back on the same owner with the same id, text, author and timestamps — 983 of them on 2026-09-10. `--step=1` reverses the structural migration alone; a second step reverses the cleanup, whose `down()` restores nothing.
- `grep -rnE -e '->games(->first\(\)|\[0\])' app resources tests database --include=*.php --include=*.blade.php` returns nothing, and the `->(articles|interviews|reviews)` grep beside it in the end state returns only `resources/views/games/card_gameinfo.blade.php:77`. Unit 4 is the last unit at which both hold: Unit 1 clears 39 of the 42 lines and this unit clears the three that unwrap `Comment::games()`.
- `grep -rc belongsToMany app/Models/*.php | awk -F: '{s+=$2} END {print s}'` returns 41, down from the 51 the end state records.
- `./vendor/bin/sail artisan test` passes, including `tests/Feature/Public/GamePageTest.php`, `ContentPagesTest.php`, `ReviewPagesTest.php` and `tests/Feature/Admin/Tables/AdminTablesTest.php`.
- `artisan route:list --name=comments` names the sixteen `admin.{games,articles,interviews,reviews}.comments.*` routes, each resolving to that section's controller, and no `admin.users.comments.*` route.
- Each of the four screens opens its own nav group and marks its own link: the rendered `/admin/{section}/comments` carries `id="{section}" class="accordion-collapse collapse show` and one `<a class="active"` whose href is that screen.
- `php artisan db:seed --class=E2ESeeder` completes, and `tests/e2e/public/{games,reviews,articles,interviews}.spec.js`, `tests/e2e/public-write/{games,content,reviews}.spec.js`, `tests/e2e/admin/{games,content,editor,users}.spec.js` pass.
- In the browser: posting, editing and deleting a comment works on a game, an article, an interview and a review — the delete needs the new `context` input, and no other page posts to `/comments/delete`. Each of the four admin comment screens lists, sorts by date, searches by content, filters by author, edits and deletes. Deleting a game from the admin removes its comments.
## Verification

Run after every unit, not once at the end: `./vendor/bin/sail artisan test`,
then `php artisan migrate:fresh` and `php artisan db:seed --class=E2ESeeder`
against the e2e database, followed by the specs that unit's acceptance names.
`E2ESeeder` writes through `DB::table()->updateOrInsert()`, which no relation
and no model reaches, so it is the gate that catches an insert into a pivot the
migration has dropped.

`php artisan al:audit-relationship-keys --pivots` before and after each unit.
It reports the pivot table Eloquent resolves for every `belongsToMany`
(`RelationshipKeyAudit::pivotTables()`). Only Units 1 and 4 delete
`belongsToMany` declarations — two and eight respectively — so for those two
the gate is the output shrinking by exactly the declarations that unit names,
and for Units 2 and 3 it is the output not changing at all. Either way, what
it confirms is that none of the remaining declarations moved.

Against the long-lived dev database, before dropping the pre-migration dump
named in "Deploying": each unit's acceptance queries, run once before migrating
to record the values compared against, and once after.

## Deploying

A dump immediately before each of the four units — four separate deploys, not
one. Every unit moves real row data and drops a table, on tables with live
writers.

No step is taken by hand. Units 3 and 4 each delete their unreachable rows in a
migration that runs before the structural one, so `artisan migrate --force` in
`.github/workflows/deploy.sh:105` carries the whole unit. Both cleanups print the
rows they delete, and that output in the deploy log is the record: the rows do
not come back, on a rollback or otherwise.

What the cleanups do not remove is a dump's ZIP. A deleted dump leaves
`storage/app/public/zips/menus/{id}.zip` behind as a file no row names, which
`menus:check-dumps` never reports because it iterates rows. On 2026-09-10 the two
orphans were id 592 and 3052; the dev checkout has no `zips/menus` directory, so
whether those files exist could only be checked on the deploy target.

A failed guard stops the deploy with the site in maintenance mode and the
schema short of that unit. That is what the guards buy: a unit that cannot
complete refuses before any DDL runs, rather than leaving the schema half
migrated. Bring the site back with `artisan up`.

## Out of scope

- **The screenshot owner.** Five of the six screenshot pivots are one-to-many:
  every screenshot in them has exactly one owner. Measured 2026-09-10 with the
  pivot census at the end of this section, and with this ownership census:

  ```sql
  SELECT owners, COUNT(*) AS screenshots FROM (
    SELECT s.id,
           (s.id IN (SELECT screenshot_id FROM game_screenshot))
         + (s.id IN (SELECT screenshot_id FROM article_screenshot))
         + (s.id IN (SELECT screenshot_id FROM interview_screenshot))
         + (s.id IN (SELECT screenshot_id FROM review_screenshot))
         + (s.id IN (SELECT screenshot_id FROM game_fact_screenshot))
         + (s.id IN (SELECT screenshot_id FROM game_submission_screenshot))
         + (s.id IN (SELECT screenshot_id FROM spotlights)) AS owners
    FROM screenshots s) t
  GROUP BY owners ORDER BY owners;
  ```

  | Pivot | Rows | Distinct screenshots | Max owners per screenshot |
  |---|---|---|---|
  | `game_screenshot` | 25887 | 25887 | 1 |
  | `interview_screenshot` | 1168 | 1168 | 1 |
  | `game_fact_screenshot` | 420 | 420 | 1 |
  | `game_submission_screenshot` | 178 | 178 | 1 |
  | `article_screenshot` | 78 | 78 | 1 |
  | `review_screenshot` | 798 | 796 | 2 |

  Of 28727 screenshots, 26951 have exactly one owner, 791 have two and 985 have
  none. Every one of the 791 is a `game_screenshot` that a `review_screenshot`
  row also points at — 793 such rows — which is what
  `admin/reviews/reviews/card_edit.blade.php:173-174` produces by offering the
  review's own game's screenshots to caption. Overlap between every other pair
  of the six is 0. `review_screenshot` is therefore the one genuine
  many-to-many and would stay a pivot; the other five would become an owner on
  `screenshots`, which is `(id, imgext)` and has no way to say what it belongs
  to — `getUrl()`, `getUrlRoute()`, `getFolder()` and `getPath()`
  (`app/Models/Screenshot.php:40-68`) each take the owner's type as an argument
  and index `Screenshot::PATHS` with it. The grep below returns 84 lines across
  48 files, seven of them in `Screenshot.php` itself — four method definitions,
  two `PATHS` reads and one internal call — so 78 call sites across 47 other
  files pass that argument
  (`grep -rn "getUrl(\|getUrlRoute(\|getFolder(\|getPath(\|Screenshot::PATHS" app resources tests routes --include=*.php --include=*.blade.php`),
  which is its own plan.

- **`link_category`.** 188 links, 188 pivot rows, one category each, no link
  without one, `max_categories_per_link` 1 — and
  `link_submissions.category_id` models the same relation as a plain `int(11)`
  column. The pivot is not a legacy accident: the admin form is a checkbox
  multi-select over every category
  (`admin/links/links/card_edit.blade.php:60-79`) writing through
  `$link->categories()->sync()` (`Admin/Links/LinkController.php:77,109`), and
  `link_category` carries `UNIQUE (link_id, category_id)`. The second dimension
  has not been used; whether it should exist is a product question, not a
  schema defect.

- **`game_release_copy_protection` (94 rows, 94 releases) and
  `game_release_disk_protection` (73, 73).** One-to-one in the data today, with
  a `notes` column each, and plural by concept — a release can carry more than
  one protection scheme. Left as pivots.

- **Near-one-to-many pivots.** `sub_crew`: 36 rows over 34 crews, `crew_id` 35
  and 113 having two parents each. `individual_nickname`: 486 rows, 484 nicks,
  `nick_id` 4679 and 4771 shared by two individuals each. `game_engine`: 206
  rows, 205 games, `game_id` 4242 having two. `game_programming_language`: 691
  rows, 676 games. Each has a real exception in the data, so none is a
  one-to-many.

- **`game_vs`, `news.news_image_id`, `game_galleries`, `media_scans.media_id`,
  `spotlights.screenshot_id`.** `game_vs` is one-to-many and used as one: 1193
  rows over 1164 games, 29 games with more than one cross-reference.
  `news_images` is many-to-one from `news`: 391 references to 262 images, one
  image on 23 news items. `game_galleries` is 118 rows over 101 games and is a
  kept record table with no model, ruled on by
  `2026-08-28-dead-tables-and-columns.md` ("`game_gallery`", line 532).
  `media_scans.media_id` (43 rows, 43
  media) and `spotlights.screenshot_id` (6, 6) are one-to-one at their current
  row counts and plural by concept — a medium has a front and a back scan.

- **Missing unique keys on pivots.** `game_individual` holds 12 duplicate
  `(game_id, individual_id, individual_role_id)` triples and `game_developer`
  holds 2 duplicate `(game_id, company_id, developer_role_id)` triples; neither
  table has a unique index on its triple, where `link_category` does. That is a
  constraint gap and a data cleanup, not a cardinality error, and it belongs
  with whatever plan takes on the composite pivots.

- **`magazine_indices`.** `game_id`, `menu_software_id` and `individual_id` are
  three nullable foreign keys standing in for one target: of 1252 rows, 1209
  name a game, 32 a piece of software, 4 an individual, 8 name none and 1 names
  both a game and a piece of software. The "exactly one" is not enforced. It is
  the same shape Unit 4 resolves by splitting, which here would mean three index
  tables rather than one, or a target-type column; the row counts are too
  lopsided to rule on without deciding what a magazine index entry is, so it is
  left alone. It is also the one table in this section that takes enough writes
  to move: these figures were re-measured on 2026-09-10 after the rest of the
  plan was written, and were two rows lower before.

- **`changelogs` rows that name a comment.** `ChangelogHelper::insert()` writes
  a comment's key into `sub_section_id` beside a `section` of 'Games',
  'Articles', 'Interviews' or 'Reviews'
  (`Admin/CommentController.php`, `COMMENT_CHANGELOG_SECTIONS` at lines 15-19
  and the write at line 58 before this unit); `sub_section_id` is a plain
  `int(11)` with no foreign key, indexed only as part of
  `(section, sub_section)`. Unit 4 carries every existing comment id across
  unchanged, so no historical row is broken. What changes is the future: four
  tables hand out ids from four sequences, and the same `sub_section_id` starts
  appearing in more than one of them. `section` is what disambiguates, as it
  already does for every other section that shares an id space. Giving
  `changelogs` a real polymorphic target is its own plan.

The pivot census, run against the dev database. It is the query the four units
and this section were derived from:

```sql
-- For one pivot; the audit ran it as a UNION ALL over the 38 pivots the
-- inventory below returns, plus game_release_copy_protection and
-- game_release_disk_protection, so 40 in all. max_b_per_a = 1 means the
-- relation is 1:N in the A→B direction and the pivot's second dimension is
-- unused.
SELECT 'game_review' AS pivot,
       (SELECT COUNT(*) FROM game_review)                                              AS rows_total,
       (SELECT COUNT(DISTINCT review_id) FROM game_review)                             AS distinct_a,
       (SELECT COUNT(DISTINCT game_id) FROM game_review)                               AS distinct_b,
       (SELECT MAX(c) FROM (SELECT COUNT(*) c FROM game_review GROUP BY review_id) x)  AS max_b_per_a,
       (SELECT MAX(c) FROM (SELECT COUNT(*) c FROM game_review GROUP BY game_id) y)    AS max_a_per_b;
```

```sql
-- The junction-table inventory the census was built from: every table whose
-- columns are two or more foreign keys plus, at most, a surrogate `id`.
-- On 2026-09-10 it returns 38 tables; after Units 1 and 4 it returns 33,
-- game_review and the four comment pivots being gone, and after Unit 2 as well
-- it returns 30 — the three screenshot pivots gain a `description`, which is
-- not a foreign key, so they stop matching the shape below. It excludes
-- game_release_copy_protection and game_release_disk_protection, which carry
-- a `notes` column each — the audit measured those two by hand.
SELECT c.table_name,
       COUNT(*)                                AS columns,
       SUM(k.column_name IS NOT NULL)          AS fk_columns,
       SUM(c.column_name = 'id')               AS has_id
FROM information_schema.columns c
LEFT JOIN information_schema.key_column_usage k
  ON k.table_schema = c.table_schema AND k.table_name = c.table_name
 AND k.column_name = c.column_name AND k.referenced_table_name IS NOT NULL
WHERE c.table_schema = DATABASE()
GROUP BY c.table_name
HAVING fk_columns >= 2 AND columns = fk_columns + has_id
ORDER BY c.table_name;
```
