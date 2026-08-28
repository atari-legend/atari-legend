# The schema consistency sweep

*2026-08-26*

Successor to the [primary-key rename](2026-08-17-primary-key-rename.md),
[foreign-key rename](2026-08-23-foreign-key-rename.md) and
[main/text table merge](2026-08-24-main-text-table-merge.md) campaigns. Those
converged the model-backed half of the schema; this plan covers what they left
behind.

The end state: every table that has a primary key calls it `id`, no index
duplicates a primary key or another index, the thirteen columns listed in Phases
3a and 3b carry a foreign-key constraint, and every endpoint with a
`data-autocomplete-id` contract emits `id`.

Three exceptions are deliberate, and are argued where they are taken: the two
composite pivots keep their composite primary keys (Phase 2), ten tables keep no
primary key at all ("Out of scope"), and four `NOT NULL` columns pointing at
`users` stay unconstrained rather than be made nullable (Phase 3b).

Every figure below was measured against the dev MariaDB 10.11 on 2026-08-27,
with the query named so it can be re-run. Decisions were settled with nicolas on
2026-08-26 and 2026-08-27.

| Phase | Scope | Migration? | Risk |
|---|---|---|---|
| 1 — indexes | 15 indexes duplicating a primary key, 2 duplicate indexes on `users.userid`, 2 tables with no primary key | Yes | None — metadata only |
| 2 — model-less primary keys | 16 renames | Yes | Low — no code reads the columns |
| 3a — game-side constraints | 10 columns constrained, 27 orphan rows deleted | Yes | Low — data cleaned first |
| 3b — user-side constraints | 3 columns constrained, 1 constraint re-ruled, 1 guard added to `User` | Yes | Low — fixes a reachable admin bug |
| 5 — autocomplete wire format | 2 endpoints, 14 Blade attributes | No | Low — a mismatched key fails an existing spec |

Phase 4, the full-history rollback, is out of scope; see "Out of scope". The
numbering is unchanged so the earlier campaigns' references to phase 5 resolve.

One commit per phase, and Phase 3's two migrations are two commits. A phase
commit carries every migration of that phase.

- Phases 1, 2 and 5 are independent of everything else.
- Phase 3a depends on Phase 2, because `screenshot_game`, `game_similar`,
  `sub_crew` and `bug_report` get both a rename and a constraint, and the
  constraint migration should be written against the final column names.
- Phase 3b is independent of both.

## Phase 1 — the redundant and missing indexes

### Indexes that duplicate their own table's primary key

A second B-tree paid for on every insert and update, serving no read the primary
key does not serve. The census — every secondary index whose column list is
exactly its table's primary key:

```sql
WITH k AS (
    SELECT TABLE_NAME, INDEX_NAME,
           GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS cols
    FROM   information_schema.STATISTICS
    WHERE  TABLE_SCHEMA = DATABASE()
    GROUP BY TABLE_NAME, INDEX_NAME
)
SELECT   k.TABLE_NAME, k.INDEX_NAME, k.cols
FROM     k JOIN k AS pk ON pk.TABLE_NAME = k.TABLE_NAME
                       AND pk.INDEX_NAME = 'PRIMARY'
                       AND pk.cols = k.cols
WHERE    k.INDEX_NAME <> 'PRIMARY'
ORDER BY k.TABLE_NAME, k.INDEX_NAME;
```

15 rows today, which is the table below.

| Table | Index | Sits on | Dropped in |
|---|---|---|---|
| `comments` | `comments_id` | `id` | Phase 1 |
| `game` | `game_id` | `id` | Phase 1 |
| `game_genre` | `game_cat_id` | `id` | Phase 1 |
| `game_individual` | `game_author_id` | `id` | Phase 1 |
| `news` | `news_id` | `id` | Phase 1 |
| `pub_dev` | `pub_dev_id` | `id` | Phase 1 |
| `screenshots` | `screenshot_id` | `id` | Phase 1 |
| `users` | `user_id` | `id` | Phase 1 |
| `website` | `website_id` | `id` | Phase 1 |
| `website_category` | `website_category_id` | `id` | Phase 1 |
| `article_user_comments` | self-named unique | own PK | Phase 2 |
| `review_user_comments` | self-named unique | own PK | Phase 2 |
| `screenshot_game` | self-named unique | own PK | Phase 2 |
| `website_category_cross` | self-named unique | own PK | Phase 2 |
| `website_validate` | `website_id` | own PK | Phase 2 |

The last five are dropped in their table's Phase 2 rename migration, because
`renameColumn` leaves the index name behind and the two changes belong together.
`screenshot_game`'s sits on 26,028 rows.

This closes the merge plan's two deferrals — `pub_dev.pub_dev_id` (its Phase 6)
and `screenshots.screenshot_id` (its Phase 4 docblock).

### Duplicate indexes on `users.userid`

`SHOW INDEX FROM users` returns three non-unique indexes on the single column
`userid`: `userid`, `userid_2` and `userid_3`, the latter two being MySQL's
auto-suffixed names from an index added twice more. The table has 875 rows and
the login path writes to it.

### Tables with no primary key at all

The census is `information_schema.TABLES` left-joined to
`information_schema.STATISTICS` on `INDEX_NAME = 'PRIMARY'`, keeping the rows
where the join finds nothing:

```sql
SELECT   t.TABLE_NAME
FROM     information_schema.TABLES t
LEFT JOIN information_schema.STATISTICS s
       ON s.TABLE_SCHEMA = t.TABLE_SCHEMA
      AND s.TABLE_NAME   = t.TABLE_NAME
      AND s.INDEX_NAME   = 'PRIMARY'
WHERE    t.TABLE_SCHEMA = DATABASE()
AND      t.TABLE_TYPE   = 'BASE TABLE'
AND      s.INDEX_NAME IS NULL
ORDER BY t.TABLE_NAME;
```

Twelve rows today, ten after this phase. Two of the twelve are in scope:
`news_image` and `trivia` each have an `id int(11) NOT NULL AUTO_INCREMENT`
column and no primary key — only a `UNIQUE KEY` carrying the pre-rename name
(`news_image_id`, `trivia_id`).

The unique index must be replaced rather than removed: `ADD PRIMARY KEY (id)`
**before** the `DROP INDEX`. InnoDB requires the `AUTO_INCREMENT` column to lead
some key, and the reverse order fails:

```
ERROR 1075 (42000): Incorrect table definition; there can be only one auto
column and it must be defined as a key
```

`DESCRIBE news_image` reports `id` as `PRI` — MySQL labels a `UNIQUE NOT NULL`
index that way when a table has no primary key. Only
`information_schema.STATISTICS` and `SHOW CREATE TABLE` show the real state.

The other ten are listed under "Out of scope".

### The migrations

Three, metadata-only, guarded `!== 'sqlite'` (never `=== 'mysql'`).

1. **The ten redundant primary-key indexes.** Drop `comments.comments_id`,
   `game.game_id`, `game_genre.game_cat_id`, `game_individual.game_author_id`,
   `news.news_id`, `pub_dev.pub_dev_id`, `screenshots.screenshot_id`,
   `users.user_id`, `website.website_id` and
   `website_category.website_category_id`. Drop by the literal index name —
   `dropIndex('game_id')`, not `dropIndex(['id'])`.
2. **`news_image` and `trivia`.** `ADD PRIMARY KEY (id)` first, then drop the
   stale `news_image_id` / `trivia_id` unique indexes. The `down()` re-adds the
   unique index before dropping the primary key, for the same reason in reverse.
3. **`users.userid_2` and `users.userid_3`.** Drop both; `userid` stays.

Rehearsed on a structure-only copy of the dev database on 2026-08-27: every
statement runs clean, including `users.user_id`, a unique index on `id` with ten
inbound foreign keys. InnoDB uses the primary key for all ten.

### Acceptance

- The redundant-index census above returns five rows — the five deferred to
  Phase 2 — and zero after Phase 2.
- `news_image` and `trivia` each report a `PRIMARY KEY (id)` in
  `SHOW CREATE TABLE`, and the no-primary-key census above returns ten rows.
  `DESCRIBE` is not the check — it reports `PRI` for both today.
- `SHOW INDEX FROM users` returns one index on `userid`.

## Phase 2 — the model-less primary keys

### The tables

25 tables carry a prefixed primary key, plus two composites. The census — every
primary key that is not the single column `id`:

```sql
SELECT   s.TABLE_NAME,
         GROUP_CONCAT(s.COLUMN_NAME ORDER BY s.SEQ_IN_INDEX) AS pk
FROM     information_schema.STATISTICS s
WHERE    s.TABLE_SCHEMA = DATABASE()
AND      s.INDEX_NAME = 'PRIMARY'
GROUP BY s.TABLE_NAME
HAVING   pk <> 'id'
ORDER BY s.TABLE_NAME;
```

27 rows today, which is the table below.

| Table | PK | Rows | Inbound FKs | Disposition |
|---|---|---|---|---|
| `article_user_comments` | `article_user_comments_id` | 3 | 0 | rename |
| `bug_report` | `bug_report_id` | 1 | 0 | rename |
| `bug_report_type` | `bug_report_type_id` | 4 | 0 | rename |
| `crew_individual` | `crew_individual_id` | 470 | 0 | rename |
| `game_similar` | `game_similar_id` | 420 | 0 | rename |
| `game_user_comments` | `game_user_comments_id` | 935 | 0 | rename |
| `individual_nicks` | `individual_nicks_id` | 486 | **1** (`crew_individual.individual_nick_id`) | rename |
| `interview_user_comments` | `interview_user_comments_id` | 18 | 0 | rename |
| `review_game` | `review_game_id` | 126 | 0 | rename |
| `review_user_comments` | `review_user_comments_id` | 26 | 0 | rename |
| `screenshot_game` | `screenshot_game_id` | 25,904 | 0 | rename |
| `screenshot_game_fact` | `screenshot_game_fact_id` | 420 | 0 | rename |
| `screenshot_game_submitinfo` | `screenshot_game_submitinfo_id` | 178 | 0 | rename |
| `sub_crew` | `sub_crew_id` | 38 | 0 | rename |
| `website_category_cross` | `website_category_cross_id` | 188 | 0 | rename |
| `website_validate` | `website_id` | 0 | 0 | rename |
| `database_change` | `database_change_id` | 247 | 0 | dead table — out of scope |
| `game_gallery` | `game_gallery_id` | 118 | 0 | dead table — out of scope |
| `gameinfo_screenshot` | `gameinfo_screenshot_id` | 0 | 0 | dead table — out of scope |
| `news_search_wordlist` | `news_word_text` (varchar) | 6,584 | 0 | dead table — out of scope |
| `theme` | `theme_id` | 0 | 0 | dead table — out of scope |
| `theme_style` | `theme_style_id` | 0 | 0 | dead table — out of scope |
| `theme_template` | `theme_template_id` | 0 | 0 | dead table — out of scope |
| `tools` | `tools_id` | 0 | 0 | dead table — out of scope |
| `users_reset` | `reset_id` | 22 | 0 | dead table — out of scope |
| `crew_menu_set` | composite `(crew_id, menu_set_id)` | 328 | 0 | left as a composite |
| `game_sndh` | composite `(game_id, sndh_id)` | 1,058 | 0 | left as a composite |

**Sixteen renames.** The two composite pivots keep their composite primary keys
and are the standing exception to the "every primary key is `id`" end state.

**`website_validate.website_id` is a pure rename to `id`**, plus deletion of
`WebsiteValidate::$primaryKey` — the last `$primaryKey` declaration in
`app/Models/`. The column is an `AUTO_INCREMENT` surrogate with a bad name, not
a foreign key to `website`:

```
`website_id` int(11) NOT NULL AUTO_INCREMENT,
PRIMARY KEY (`website_id`),
UNIQUE KEY `website_id` (`website_id`),
```

No constraint sits on it, `LinkController::postLink()` relies on the
auto-increment, and the table is empty.

### No code reads any of these columns

A grep for the legacy names across `app/`, `database/migrations/`, `tests/` and
`resources/` returns only the historical `create_*` migrations and two data
migrations (`2021_04_24_092250_update_crew_tables`,
`2026_04_26_000000_link_constraints`), all of which run before any new rename in
date order and must not be touched. The `belongsToMany` relations using these
tables as pivots derive their keys from the model names; the three
`withPivot('id')` sites are on `screenshot_article`, `screenshot_review` and
`screenshot_interview`, which already have `id`. `E2ESeeder` inserts into
`screenshot_game`, `review_game`, `game_user_comments` and
`website_category_cross` and forces no primary key values.

This phase is therefore a pure schema campaign: no model, relationship, Blade or
seeder edit.

`crew_individual.individual_nick_id` → `individual_nicks` needs no drop and
re-add. `ALTER TABLE … RENAME COLUMN` rewrites every inbound foreign key
definition, named and unnamed, with the constraints staying live — verified by
the primary-key campaign on a 16-FK parent.

### The migrations

Sixteen, one per table, each a single `renameColumn()` plus, where the table has
one, the redundant index belonging to that column. Every migration in the phase
is the same two statements.

- The five tables Phase 1's table marks `Phase 2` drop their redundant index in
  the same migration, by the index's literal name.
- `individual_nicks` needs nothing extra.
- `website_validate` also loses `WebsiteValidate::$primaryKey`.
- The nine dead tables are not in this phase.

Order: `website_validate` first — the only empty table in scope, so a wrong
migration costs no rows — then the four comment pivots, then the rest.
No fan-out ordering is needed, because no code reads these columns.

The `down()` of every rename is one `renameColumn()` back, tested. Reverting
takes all sixteen out together, because the phase is one commit; see
"Deploying".

### Acceptance

With no code reading these columns, a wrong rename cannot fail a test. The gate
is the schema: the census query above must return **eleven** rows after the
phase — the nine dead tables left alone, and the two composite pivots. Ten or
fewer means something outside the sixteen was touched.

Add to the standard verification a `migrate:fresh` on the e2e database followed
by `E2ESeeder` and a Playwright run, since four of the renamed tables are e2e
pivots.

## Phase 3a — the game-side constraints

### The columns

Twelve are foreign keys in every sense except the constraint.

| Table.column | References | Orphans | Nulls | Disposition |
|---|---|---|---|---|
| `game_release.game_id` | `game.id` | 0 | 0 (NOT NULL) | constrain |
| `game_aka.game_id` | `game.id` | 0 | 0 (NOT NULL) | constrain |
| `screenshot_game.game_id` | `game.id` | **17** | nullable | constrain |
| `screenshot_game.screenshot_id` | `screenshots.id` | 0 | nullable | constrain |
| `sub_crew.crew_id` | `crew.id` | **2** | nullable | constrain |
| `sub_crew.parent_id` | `crew.id` | 0 | nullable | constrain |
| `game_similar.game_id` | `game.id` | **4** | nullable | constrain |
| `game_similar.game_similar_cross` | `game.id` | **1** | nullable | constrain |
| `game_vs.atari_id` | `game.id` | **3** | 0 (NOT NULL) | constrain |
| `game_gallery.game_id` | `game.id` | 0 | 0 (NOT NULL) | dead table — out of scope |
| `gameinfo_screenshot.game_id` | `game.id` | 0 | nullable (0 rows) | dead table — out of scope |
| `gameinfo_screenshot.screenshot_id` | `screenshots.id` | 0 | nullable (0 rows) | dead table — out of scope |

Plus `bug_report.bug_report_type_id` → `bug_report_type`: 1 row, 0 orphans,
nullable, constrained as `SET NULL`.

The orphan query, one branch per row of the table above:

```sql
SELECT 'game_release.game_id' AS col, COUNT(*) AS orphans
FROM game_release c LEFT JOIN game p ON p.id = c.game_id
WHERE c.game_id IS NOT NULL AND p.id IS NULL
UNION ALL SELECT 'game_aka.game_id', COUNT(*)
FROM game_aka c LEFT JOIN game p ON p.id = c.game_id
WHERE c.game_id IS NOT NULL AND p.id IS NULL
UNION ALL SELECT 'screenshot_game.game_id', COUNT(*)
FROM screenshot_game c LEFT JOIN game p ON p.id = c.game_id
WHERE c.game_id IS NOT NULL AND p.id IS NULL
UNION ALL SELECT 'screenshot_game.screenshot_id', COUNT(*)
FROM screenshot_game c LEFT JOIN screenshots p ON p.id = c.screenshot_id
WHERE c.screenshot_id IS NOT NULL AND p.id IS NULL
UNION ALL SELECT 'sub_crew.crew_id', COUNT(*)
FROM sub_crew c LEFT JOIN crew p ON p.id = c.crew_id
WHERE c.crew_id IS NOT NULL AND p.id IS NULL
UNION ALL SELECT 'sub_crew.parent_id', COUNT(*)
FROM sub_crew c LEFT JOIN crew p ON p.id = c.parent_id
WHERE c.parent_id IS NOT NULL AND p.id IS NULL
UNION ALL SELECT 'game_similar.game_id', COUNT(*)
FROM game_similar c LEFT JOIN game p ON p.id = c.game_id
WHERE c.game_id IS NOT NULL AND p.id IS NULL
UNION ALL SELECT 'game_similar.game_similar_cross', COUNT(*)
FROM game_similar c LEFT JOIN game p ON p.id = c.game_similar_cross
WHERE c.game_similar_cross IS NOT NULL AND p.id IS NULL
UNION ALL SELECT 'game_vs.atari_id', COUNT(*)
FROM game_vs c LEFT JOIN game p ON p.id = c.atari_id
WHERE c.atari_id IS NOT NULL AND p.id IS NULL
UNION ALL SELECT 'bug_report.bug_report_type_id', COUNT(*)
FROM bug_report c LEFT JOIN bug_report_type p
  ON p.bug_report_type_id = c.bug_report_type_id
WHERE c.bug_report_type_id IS NOT NULL AND p.bug_report_type_id IS NULL;
```

The last branch joins on `bug_report_type.bug_report_type_id`, which is what the
column is called until Phase 2 renames it to `id`; re-run after Phase 2 with the
new name. Run on 2026-08-27 it returns the Orphans column above, summing to the
27 rows the cleanup deletes.

No `NOT NULL DEFAULT 0` column in this set holds a zero row, so no constraint is
blocked by a sentinel.

Two columns that look like candidates and get no constraint:

- **`game_vs.amiga_id`** is an external id —
  `GameVs::getLemonAmigaUrlAttribute()` builds
  `https://www.lemonamiga.com/games/details.php?id=` from it — and its 471
  "orphans" are the expected state.
- **`news_submission.news_image_id`** is `NOT NULL DEFAULT 0` with all five rows
  at `0`: a "no image" sentinel copied from `news.news_image_id`. Nothing reads
  it — the only code touching a `news_image_id` is on `news` (`News::image()`,
  `NewsController:136`, `NewsTable:58`). Constraining it would require inventing
  a `news_image` row with id 0. It belongs to the deferred column-drop sweep.

### `Game::is_deletable` and `menu_disk_contents.game_id`

`Game::getIsDeletableAttribute()` (`app/Models/Game.php:38`) blocks on twelve
relations in thirteen checks: releases, screenshots, facts, individuals,
developers, sndhs, videos, reviews, `menuDiskContents`, magazine indices, info
submissions, and similar games in both directions. `GameController::destroy()`
(`app/Http/Controllers/Admin/Games/GameController.php:130`) refuses the request
before any `DELETE` is issued. Any claim of the form "deleting a game does X"
must be checked against this guard first.

Consequences:

- `Game::getIsDeletableAttribute()` already refuses every delete these
  constraints would catch, so they change nothing at runtime today. They take
  effect only if that guard is removed or bypassed.
- `menu_disk_contents.game_id` is the one child of `game` without an
  `ON DELETE` clause, and therefore `RESTRICT`. All 1,334 of its game-linked
  rows are game-only entries, carrying no `menu_software_id` or
  `game_release_id`. The 1451 it would raise is unreachable, because the guard
  checks `menuDiskContents()->exists()` (`app/Models/Game.php:72`).

**`menu_disk_contents.game_id` stays `RESTRICT` and is not touched by this
phase.** A `menu_disk_contents` row records what was actually on a disk — a
historical fact about the disk rather than a derived property of the game — and
`CASCADE` would delete 1,334 such rows silently.

Every other existing constraint pointing at `game` is `ON DELETE CASCADE`.

When re-measuring constraint rules, query the live database. A pass run against
`information_schema.REFERENTIAL_CONSTRAINTS` on a scratch database that was
mid-rollback reported six `RESTRICT` children of `game` — the 2022-era schema,
before the `*_constraints.php` migrations that renamed five of them and set
`CASCADE`.

### The migration

One migration in two halves.

1. **The cleanup**, each count asserted so that data changing since 2026-08-27
   aborts the migration rather than deleting a different set of rows: delete the
   17 `screenshot_game` rows, 2 `sub_crew` rows, 4 `game_similar` rows, 1
   `game_similar_cross` row and 3 `game_vs` rows whose parents no longer exist —
   27 distinct rows, verified: no `game_similar` row is orphaned in both
   columns. All 17 screenshots behind the orphaned `screenshot_game` rows exist
   in `screenshots` and are referenced by no other pivot and no second
   `screenshot_game` row, so those 17 `screenshots` rows are deleted too. The
   counts are `SELECT`s in the migration, not literals in a comment; the 17
   filenames go in the commit message so the files on disk can be swept
   separately.
2. **The ten constraints.** The nine live game-side columns, all
   `ON DELETE CASCADE`, matching what the delete code already does by hand and
   what the existing `CASCADE` children do, plus `bug_report.bug_report_type_id`
   as `SET NULL`.

**Leave the existing `screenshot_game.game_id` index alone.** Adding the
constraint creates no new index — `SHOW INDEX` still reports `PRIMARY`,
`screenshot_game_id` and `game_id`, because InnoDB binds the foreign key to the
usable index that already exists — and a subsequent drop fails:

```
ERROR 1553 (HY000): Cannot drop index 'game_id': needed in a foreign key
constraint
```

The same applies to every other column in this list that already has an index.

The `screenshot_game.game_id` cascade deletes the pivot row and leaves the
`screenshots` row and the JPEG on disk. That is harmless while
`Game::getIsDeletableAttribute()` refuses a game that has screenshots; if that
guard is relaxed, the cascade becomes a file leak. Note the coupling in the
migration.

### Acceptance

`information_schema.REFERENTIAL_CONSTRAINTS` names all ten new constraints, and
the orphan query above returns zero on every one of their columns. The
feature-test route is closed: `is_deletable` refuses a game that has a release,
so a controller-level delete flashes `alert-danger` without deleting, and a
`$game->delete()` test would be a model-layer test of a database rule. The
schema assertion is the coverage.

Run the standard verification with the production dump loaded; the cleanup half
needs real data to be meaningful.

## Phase 3b — the user-side constraints and the `User` guard

### How a user gets deleted

`Admin/User/UserController::destroy()` is a bare `$user->delete()` with no
guard, and `admin/users/users/datatable_actions.blade.php` renders the delete
button unconditionally. Every rule on every constraint pointing at `users` is
reachable from one click.

A second path reaches the same deletes:
`app/Console/Commands/DeleteUnverifiedUsers.php` — the command is
`user:delete-unverified`, not `users:delete-unverified` as CLAUDE.md has it —
iterates unverified accounts and calls `$user->delete()` inside `$users->each()`
with no `try`. A single `RESTRICT`-blocked account throws out of the closure and
abandons the rest of an unattended run. Today one unverified account is old
enough to be swept and none holds a `game_submitinfo`, `dump` or
`dump_user_info` row, so the risk is latent.

### The rules do not agree with each other

| Rule | Tables | What a delete does |
|---|---|---|
| `SET NULL` | `articles`, `interviews`, `news`, `reviews`, `website`, `website_validate` | content survives, author blanks |
| `RESTRICT` | `game_submitinfo`, `dump`, `dump_user_info` | 1451, raw error page |
| `CASCADE` | `game_votes` | votes destroyed |
| none | `comments`, `change_log`, `menu_disk_dumps`, `news_submission`, `bug_report`, `users_reset`, `users_login_attempts` | rows survive with a dangling `user_id` |

Measured over 875 users:

- **114 cannot be deleted at all.** 113 hold a `game_submitinfo` row; four hold
  `dump` rows, three of which are already among the 113, so `dump` blocks one
  more. `dump_user_info` is empty and blocks nobody today. Nothing catches the
  1451; the admin gets an error page:

  ```
  ERROR 1451 (23000): Cannot delete or update a parent row: a foreign key
  constraint fails (`atarilegend`.`game_submitinfo`, CONSTRAINT
  `game_submitinfo_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES
  `users` (`id`))
  ```

- **45 users hold 1,470 `game_votes` rows, which `CASCADE`.** No changelog entry
  names them.
- **150 users have comments**, which have no constraint at all.

The figures, and the dangling counts quoted further down, come from one query:

```sql
SELECT 'users' AS metric, COUNT(*) AS n FROM users
UNION ALL SELECT 'blocked by a RESTRICT', COUNT(*) FROM users u
  WHERE EXISTS (SELECT 1 FROM game_submitinfo t WHERE t.user_id = u.id)
     OR EXISTS (SELECT 1 FROM dump            t WHERE t.user_id = u.id)
     OR EXISTS (SELECT 1 FROM dump_user_info  t WHERE t.user_id = u.id)
UNION ALL SELECT 'blocked by game_submitinfo', COUNT(DISTINCT user_id)
  FROM game_submitinfo WHERE user_id IS NOT NULL
UNION ALL SELECT 'blocked by dump', COUNT(DISTINCT user_id)
  FROM dump WHERE user_id IS NOT NULL
UNION ALL SELECT 'blocked by dump_user_info', COUNT(DISTINCT user_id)
  FROM dump_user_info WHERE user_id IS NOT NULL
UNION ALL SELECT 'users holding votes', COUNT(DISTINCT user_id)
  FROM game_votes WHERE user_id IS NOT NULL
UNION ALL SELECT 'votes that would CASCADE', COUNT(*)
  FROM game_votes WHERE user_id IS NOT NULL
UNION ALL SELECT 'users holding comments', COUNT(DISTINCT user_id)
  FROM comments WHERE user_id IS NOT NULL
UNION ALL SELECT 'change_log dangling', COUNT(*)
  FROM change_log c LEFT JOIN users u ON u.id = c.user_id
  WHERE c.user_id IS NOT NULL AND u.id IS NULL
UNION ALL SELECT 'users_login_attempts dangling', COUNT(*)
  FROM users_login_attempts c LEFT JOIN users u ON u.id = c.user_id
  WHERE c.user_id IS NOT NULL AND u.id IS NULL
UNION ALL SELECT 'users_reset dangling', COUNT(*)
  FROM users_reset c LEFT JOIN users u ON u.id = c.user_id
  WHERE c.user_id IS NOT NULL AND u.id IS NULL;
```

On 2026-08-27, in the order the query returns them: 875, 114, 113, 4, 0, 45,
1,470, 150, 1,047, 118, 11.

The dangling case is benign by design: `Comment::user()` is a `belongsTo` and
returns `null` for a dangling id exactly as for a null column, and
`components/cards/partial_comment.blade.php` is null-safe throughout. Line 3
passes `$comment->user` straight into `Helper::user()`, which is typed `?User`
and returns `'Former user'` for a null; every other use is behind
`$comment->user?->` or `@if (isset($comment->user))`.

### The policy

**A user can be deleted; what they wrote stays.** No constraint referencing
`users` may `CASCADE`. Nullable columns get `SET NULL`. `NOT NULL` columns get
no constraint rather than a column change, because the frontend already renders a
dangling `user_id` as a missing author. A `RESTRICT` is acceptable where a guard
on the model refuses the delete first.

`users.inactive` is a login gate — `User::isActive()` combines it with
`email_verified_at` — and says nothing about a person's contributions, which
stay attributed while the account is merely inactive. Deletion is the only
operation that removes attribution. Any future
erasure path (a spam purge that also takes the comments and the votes) should be
its own explicit admin action, because this button is also what an admin reaches
for when a real contributor asks to be removed.

### The changes

1. **`game_votes_user_id_foreign`.** Make `user_id` nullable and re-rule the
   constraint to `SET NULL`, so the vote survives as an anonymous one.

   A vote is not displayed as the user's; no view attributes one. `user_id`
   exists for the `UNIQUE (game_id, user_id)` one-vote-per-user rule and for
   `GameVoteController::findVote()` to read back the visitor's own vote. What is
   displayed is the aggregate: `Game::getScoreAttribute()` computes
   `votes->avg('score')` live with no cached column, and `TopGames`,
   `GameController::show()` and `AdminStatisticsHelper::voteDistribution()`
   aggregate without touching `user_id`. Voting is sparse — 1,470 votes over 885
   games, 1.7 per game, 552 of the 885 games carrying exactly one voter, the
   most prolific voter holding 225 votes — so deleting one active account can
   blank the score on hundreds of game pages.

   Two properties make it safe:

   - The unique index is kept. MySQL does not collide `NULL`s in a unique index,
     so anonymised votes coexist on the same game.
   - The read path is already null-safe. `findVote(Game $game, User $user)`
     matches on `$user->getKey()`, which is never null, so an anonymised row can
     never be returned as somebody's own vote.

   `GameVoteFactory` sets `user_id` from `User::factory()`, so nothing in the
   suite exercises a null voter.
2. **The `User` guard**, the only code change in the sweep. The three
   `RESTRICT`s stay as they are; what changes is that nothing reaches them.
   - `User::getIsDeletableAttribute()`, modelled on `Game`'s, returning false
     when `infoSubmissions()`, `dumps()` or `dumpUserInfos()` exist, and on
     nothing else. A relation that `SET NULL`s or dangles must not block, or the
     guard would make 150 comment-holders undeletable.
   - `Admin/User/UserController::destroy()` refuses with a flashed
     `alert-danger` and a redirect, the way
     `Admin/Games/GameController::destroy()` does — a refused write, not a 403
     or a 404.
   - `admin/users/users/datatable_actions.blade.php` renders the button under an
     `@if`, matching the games table.
   - `DeleteUnverifiedUsers` consults the same attribute and skips and reports a
     blocked account rather than throwing out of `$users->each()`. This is why
     the guard is an attribute on the model rather than a check in the
     controller.
   - The docblock names which relations block, which deliberately do not, and
     why.
3. **The five clean columns with no constraint**, all measuring 0 orphans:
   - `menu_disk_dumps.user_id` and `bug_report.user_id` are nullable and take
     `SET NULL`.
   - `menu_disk_contents.menu_software_id` points at `menu_software` and takes
     `CASCADE`. It matches `magazine_indices.menu_software_id`, the only other
     constraint pointing at `menu_software`, which already cascades.
     `MenuSoftwareController::destroy()` is a bare `$software->delete()` with no
     guard, so deleting a software entry already drops its magazine index rows
     silently; this extends the same treatment to 3,861 `menu_disk_contents`
     rows across 1,870 software entries. A software entry and the content rows
     naming it are one record of one thing, unlike a game, which exists
     independently of the disks it appeared on.
   - `comments.user_id` and `news_submission.user_id` are `NOT NULL` and get no
     constraint. Making one nullable to gain `SET NULL` would alter the column
     definition, which is a data change rather than a constraint change.
4. **The columns that stay unconstrained.** `change_log.user_id` has 1,047 rows
   whose user is gone and is an audit trail; nulling them needs a column change.
   `users_login_attempts.user_id` has 118, on a rate-limiting scratchpad.
   `news_submission.news_image_id` is a sentinel. `users_reset.user_id` has 11,
   on a dead table.
5. **The comment at `GameController.php:144-155`**, which lists `game_aka` and
   `game_vs` as "what the database will not do". After Phase 3a the database
   does both, so the two manual `->delete()` calls become redundant and the
   comment names only the comments case, which still needs the manual
   `Comment::destroy()`.

### Acceptance

Tests, all through HTTP:

- Delete a user who has an article and a comment; assert 302, that both still
  render on the public page, and that both show the missing-author placeholder.
- Delete a user who holds a `game_submitinfo` row; assert the flashed refusal
  and a redirect. **This fails today**; the guard is what makes it pass.
- Assert the delete button is not rendered for that user in the admin table.
- Run `user:delete-unverified --delete` over a fixture holding one blocked and
  one deletable unverified account; assert the deletable one goes, the blocked
  one survives and is reported, and the command exits 0.
- Anonymise a vote and assert the game page still renders its score.

The constraint-rule query names the three new constraints and returns no
`CASCADE` among the children of `users`. Nothing in the schema gates the three
`RESTRICT`s — the tests above do.

## Phase 5 — the autocomplete wire format

### The attributes and the endpoints

24 `data-autocomplete-id` attributes: 10 already say `id`, 7 say `ind_id`, 7 say
`game_id`. Two endpoints build the array literals: `Ajax/IndividualController`
(one key, line 41) and `Admin/Ajax/GameController::games()` (three keys, lines
54, 70 and 103). The public `Ajax/GameController` is a search box emitting `url`
with no `data-autocomplete-id` contract and is out of scope.

`tests/Feature/Public/AjaxEndpointsTest.php:174` pins the individuals payload
with a whole-array `assertSame(['ind_name' => …, 'ind_id' => …])`. It is the
only PHPUnit assertion on either wire key; `Admin/Ajax/GameController::games()`
has no PHPUnit coverage, and its only gate is the Playwright admin-write specs.

`data-autocomplete-key`, the name half of the wire format, is out of scope, so
the format after this phase is `{id, ind_name}`.

### The changes

One commit; endpoint and Blade move together, per the foreign-key plan's rule
that the pair moves as a unit or not at all.

- `Ajax/IndividualController`: `'ind_id'` → `'id'`, with the comment rewritten
  to say the key is the wire name `id` shared by every endpoint. The comment
  also needs one correction: it says the key is shared by "seven Blade
  `data-autocomplete-id` attributes plus `autocomplete.js`" and to "move all
  eight or none". `autocomplete.js` is not one of them —
  `resources/js/autocomplete.js:83` reads
  `feedback.selection.value[el.dataset.autocompleteId]` and names no key of its
  own. The eight are the seven Blade attributes and the endpoint.
- `Admin/Ajax/GameController::games()`: the three `'game_id'` keys → `'id'`.
- The 14 `data-autocomplete-id` attributes → `id`: seven `ind_id` in
  `magazine-index`, `interviews/card_edit`, `games/card_search`,
  `menus/crews/card_individuals`, `menus/import/review`, `menus/disks/card_edit`
  and `games/credits/card_list`; seven `game_id` in `magazine-index`,
  `reviews/card_edit`, `menus/import/review`,
  `menus/disks/content/create_game`, `create_release`, `games/similar/card_list`
  and `games/series/card_games`.
- `tests/Feature/Public/AjaxEndpointsTest.php:174` moves with the endpoint.

Nothing in `autocomplete.js` changes: it is data-driven on both sides.

### Acceptance

The failure mode already fails a test: `pickAutocompleteBy` rejects the string
`"undefined"`, which is what a mismatched key produces, so every existing
autocomplete spec is a gate. Zero non-`id` autocomplete ids remain.

## Verification

Per phase, in order:

1. `docker compose run --rm php artisan test`
2. `npx playwright test`
3. `migrate`, `migrate:rollback --step=N`, `migrate` on MariaDB, N being the
   phase's migration count, so every `down()` in the phase runs
4. `migrate:fresh` on the e2e database, `E2ESeeder`, and the Playwright write
   projects
5. The phase's own acceptance checks, above

Index and constraint names are not part of any gate.

## Deploying

A dump immediately before the deploy, as in the previous campaigns. Reverting a
phase is `migrate:rollback --step=N` over SSH while the migration files are
still on the server, then `git revert` of the phase commit. N is the migration
count in that phase's "The migrations" section. Rolling back fewer than N leaves
the commit partly applied. Two notes specific to this sweep:

- **Phase 3a's revert is not lossless.** The cleanup half deletes rows,
  including 17 `screenshots` rows, and a `down()` cannot know which deleted rows
  were orphans. The `down()` re-creates the constraints and stops; the dump
  taken before the deploy is the recovery path, and the commit message says so.
- **Push one phase commit per deploy.** CI deploys on push, so pushing several
  phase commits together runs their migrations as one batch, and `--step=N`
  then reaches back past the phase it was meant for. Phase 3's two commits are
  two deploys.

## Out of scope

### Index and constraint names

89 of the 170 non-primary indexes carry a name Laravel would not derive: 6 whose
name is the stale constraint name behind them, 9 other legacy names, and 74 that
are simply the column name. Twelve constraints are misnamed the same way.
Nothing at runtime reads either.

The cost is one line in a future migration. `$table->dropIndex(['crew_id'])`
derives `game_release_crew_crew_id_index`, does not find it, and fails with
1091. The literal form works:

```php
$table->dropIndex('crew_id');
$table->dropForeign('magazine_location_id_foreign');
```

The misnamed indexes worth knowing about:

| Table | Index | Sits on | Problem |
|---|---|---|---|
| `game_genre_cross` | `game_cat_id` | `game_genre_id` | names a column gone for years |
| `interviews` | `user_id` | `user_id` | backs `interviews_user_id_foreign` under a pre-Laravel name |
| `reviews` | `user_id` | `user_id` | backs `reviews_user_id_foreign` under a pre-Laravel name |
| `news` | `user_id` | `user_id` | backs `news_user_id_foreign` under a pre-Laravel name |
| `website` | `user_id` | `user_id` | backs `website_user_id_foreign` under a pre-Laravel name |
| `comments`, `game_submitinfo` | `user_id` | `user_id` | a pre-Laravel name with no constraint behind it |
| 74 others | the bare column name | that column | `game_release_crew.crew_id`, `media.game_release_id`, most pivots |

Six constraints carry auto-generated `ibfk_` names, all `RESTRICT`/`RESTRICT`:

| Table | Constraint | Column |
|---|---|---|
| `dump` | `dump_ibfk_2` | `user_id` |
| `dump_user_info` | `dump_user_info_ibfk_1` | `dump_id` |
| `dump_user_info` | `dump_user_info_ibfk_2` | `user_id` |
| `game` | `game_ibfk_1` | `game_series_id` |
| `game_aka` | `game_aka_ibfk_1` | `language_id` |
| `game_release` | `game_release_ibfk_3` | `pub_dev_id` |

Six more are named after tables that no longer exist, left behind by the
magazine table pluralisation, which renamed the tables and left the constraints
and the identically-named indexes behind them pointing at the old names:

| Table | Constraint | Column | Named after |
|---|---|---|---|
| `magazines` | `magazine_location_id_foreign` | `location_id` | `magazine` |
| `magazine_indices` | `magazine_game_game_id_foreign` | `game_id` | `magazine_game` |
| `magazine_indices` | `magazine_game_magazine_index_type_id_foreign` | `magazine_index_type_id` | `magazine_game` |
| `magazine_indices` | `magazine_game_magazine_issue_id_foreign` | `magazine_issue_id` | `magazine_game` |
| `magazine_indices` | `magazine_game_menu_software_id_foreign` | `menu_software_id` | `magazine_game` |
| `magazine_issues` | `magazine_issue_magazine_id_foreign` | `magazine_id` | `magazine_issue` |

These six read as correct Laravel names and are not: `dropForeign(['location_id'])`
on `magazines` derives `magazines_location_id_foreign`. `magazines`' rule is
`SET NULL` and three of the `magazine_indices` ones are `CASCADE`, so a
drop-and-re-add would have to read the rules rather than assume them.

This closes as will-not-fix the merge plan's `interviews.user_id` and
`reviews.user_id` deferrals and the foreign-key plan's
`game_genre_cross.game_cat_id`.

### The ten dead tables

None is named anywhere in `app/`, `resources/`, `tests/`, `routes/`, `config/`,
the factories or the seeders. Measured 2026-08-27.

| Table | Rows | Note |
|---|---|---|
| `news_search_wordmatch` | 32,294 | a search index nothing queries; no primary key |
| `news_search_wordlist` | 6,584 | its partner; `varchar` primary key |
| `database_change` | 247 | real data, no reader |
| `game_gallery` | 118 | real data, no reader |
| `users_reset` | 22 | superseded by `password_resets` (`config/auth.php:96`) |
| `gameinfo_screenshot` | 0 | |
| `theme` | 0 | the `theme` hits in the tree are CSS and config, not this table |
| `theme_style` | 0 | |
| `theme_template` | 0 | |
| `tools` | 0 | the `tools` hits are FontAwesome icon classes |

Nine of the ten carry a prefixed primary key and are the nine excluded from
Phase 2; `news_search_wordmatch` has no primary key at all. Nothing in this plan
renames, constrains, indexes or deletes any of them. They need one review that
asks the same question of all of them, and that review may conclude something
other than "drop", particularly for `database_change` and `game_gallery`, which
hold real rows. A `DROP TABLE` is not a `RENAME COLUMN`, so the previous
campaigns' safety argument does not carry over, and "holds no rows" is weaker
evidence than "has no reader" — `theme` and `tools` at least match a named
feature. This census covers the 26 tables this plan already had in hand; it is a
starting list for that review, not a complete one.

`users_reset` is not a live child of `users`: it holds 22 SHA-512 tokens dated
2017–2019, 11 of them for accounts that no longer exist, and `config/auth.php:96`
points password resets at `password_resets`.

`bug_report_type` has no direct reference, but `bug_report` is counted on the
admin statistics page (`AdminStatisticsHelper:139`), so the pair stays in scope.
The dead-table review should look at it.

### The nine remaining tables with no primary key

`game_control`, `game_developer`, `game_engine`, `game_genre_cross`,
`game_programming_language`, `game_release_crew`, `game_release_distributor`,
`password_resets` and `users_login_attempts` are pivots or scratchpads: nothing
reads a row of them by identity, so a surrogate key would have no reader. They
are listed so the next audit does not count them as a finding.

### The full-history rollback

A `migrate:rollback --step=400` on a scratch copy of the dev database rolls back
cleanly for 80 migrations and then dies:

```
2022_01_15_163533_add_news_foreign_keys  FAIL
SQLSTATE[HY000]: General error: 1830 Column 'news_image_id' cannot be NOT NULL:
needed in a foreign key constraint 'news_news_image_id_foreign' SET NULL
```

The `down()` makes the columns `NOT NULL` before dropping the `SET NULL` foreign
keys that point at them, and 229 migrations sit behind the failure.

A full-history rollback has no consumer: not CI, not the deploy, not the test
suite. `.github/workflows/build-and-deploy.yml` already records the decision —
`--step=1`, because what needs to work is reversing the newest migration, which
is what a bad deploy does. A dev-database dump and a `migrate:fresh` build are
also different migration histories that fail in different places (80 migrations
in, versus around the 32nd), so fixing one history would not fix the other.

One one-line fix is worth making: the CI comment says 262 migrations and there
are 308. The standing rule is otherwise "fix the `down()` you touch", which is
what the primary-key campaign did with
`2022_09_10_120014_magazine_individual`'s `down()`. Editing a `down()` is safe
in a way editing an `up()` is not: a `down()` never runs during a
`migrate:fresh`.

### Other deferrals

- **The name half of the autocomplete wire format**, `data-autocomplete-key`:
  eight distinct values across 35 attributes — `game_name` (9), `ind_name` (7),
  `name` (6), `pub_dev_name` (5), `userid` (4), `year` (2), `crew_name` (1),
  `display` (1). Several endpoints return a composed label rather than a column,
  so `name` would be a wire name rather than a schema name and needs its own
  argument.
- **`news_submission.news_image_id`** and the wider column-drop question it
  belongs to, which the merge plan already deferred.
- **The merge plan's deferred campaigns**: the unix-timestamp date columns, the
  `pub_dev` → `publisher_developers` table rename, and the `ind_profile` /
  `pub_dev_profile` / `article_text` column prefixes.
- **The server-side half of the data move** (2026-08-18): the move on dev and
  prod, the images-export cron check, the backup re-pointing. The repo change is
  in; the servers are not verifiable from here.
- **The e2e statistics-figures gap** (`tests/e2e/README.md`): the Tops and
  `topPublishers()` / `topDevelopers()` figures are asserted to draw, not to be
  right. A test gap, not a schema one.
