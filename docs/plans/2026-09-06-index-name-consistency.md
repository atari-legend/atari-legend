# Closing the stale index and constraint names

*2026-09-06*

Every campaign since the [schema consistency
sweep](2026-08-26-schema-consistency-sweep.md) has left index and constraint
names that do not match Laravel's derived convention, and has said so at the
point of leaving them:

- The sweep itself ruled it "Out of scope" — 89 of 170 non-primary indexes,
  12 misnamed constraints, all recorded as *"nothing at runtime reads
  either."*
- The [foreign-key rename](2026-08-23-foreign-key-rename.md) hit the trap
  first: `dropIndex(['col'])` derives a name from the column as it stands
  *today*; when the actual index carries an older name, the derivation is
  simply wrong and the `ALTER` fails with SQLSTATE 42000 / 1091.
- The [main/text table merge](2026-08-24-main-text-table-merge.md), the
  [pluralisation](2026-08-29-plural-table-rename.md), and the [entity-names
  and pivot-order](2026-09-02-entity-names-and-pivot-order.md) campaigns each
  added a few more — every rename that touched a column or table already
  named in an index or constraint left that name stranded, by design:
  `TableRenamer` only ever rewrites a name that begins with the table's *old*
  name, so a rename that moves a *column* (not the table) never touches the
  index sitting on it.

This closes all of it in one migration: every non-primary index and every
foreign-key constraint in the schema is renamed to what
`$table->foreign()`/`index()`/`unique()`/`fullText()` would derive today. It
is naming only — no column changes type, no constraint changes rule, no row
changes. The payoff is exactly the inverse of the risk recorded above: after
this migration, `dropIndex(['col'])` and `dropForeign(['col'])` can be trusted
anywhere in the schema without a trip through `information_schema` first.

## The census, and the trap it walked into

Measured against the dev MariaDB 10.11 on 2026-09-06 — the long-lived database
this repository has developed against since before this campaign, seeded at
some point from a production dump and advanced since by running every
migration once, in order, exactly as CI does on `dev.atarilegend.com` and
`www.atarilegend.com`. Every non-primary index:

```sql
SELECT s.TABLE_NAME, s.INDEX_NAME,
       GROUP_CONCAT(s.COLUMN_NAME ORDER BY s.SEQ_IN_INDEX) AS cols,
       MAX(s.NON_UNIQUE) AS non_unique,
       MAX(CASE WHEN s.INDEX_TYPE = 'FULLTEXT' THEN 1 ELSE 0 END) AS fulltext_idx
FROM   information_schema.STATISTICS s
WHERE  s.TABLE_SCHEMA = DATABASE()
AND    s.INDEX_NAME <> 'PRIMARY'
GROUP BY s.TABLE_NAME, s.INDEX_NAME
ORDER BY s.TABLE_NAME, s.INDEX_NAME;
```

Every foreign key, with the rule it enforces:

```sql
SELECT k.TABLE_NAME, k.CONSTRAINT_NAME, k.COLUMN_NAME,
       k.REFERENCED_TABLE_NAME, k.REFERENCED_COLUMN_NAME,
       r.UPDATE_RULE, r.DELETE_RULE
FROM   information_schema.KEY_COLUMN_USAGE k
JOIN   information_schema.REFERENTIAL_CONSTRAINTS r
       ON r.CONSTRAINT_SCHEMA = k.TABLE_SCHEMA
      AND r.CONSTRAINT_NAME   = k.CONSTRAINT_NAME
      AND r.TABLE_NAME        = k.TABLE_NAME
WHERE  k.TABLE_SCHEMA = DATABASE()
ORDER BY k.TABLE_NAME, k.CONSTRAINT_NAME;
```

151 indexes, 142 foreign keys, all single-column — no foreign key in this
schema is composite, which is what makes deriving today's expected name
mechanical.

**The first version of this migration hard-coded the 89 names this census
found, and failed `migrate:fresh` on the fourth table it touched**, with
`SQLSTATE[42000]: ... Key 'user_id' doesn't exist in table 'comments'`. It
does not, on a database built by replaying every migration from an empty
schema today: that database already has `comments_user_id_index`, because the
2020 migration that creates the table calls `$table->index('user_id')`
explicitly, and always has. `comments`'s bare `user_id` on the long-lived
database is not a name any current migration file would produce — it is what
that same `Schema::create` produced when it last genuinely ran, years ago,
against a schema this application inherited from the CPANEL system named in
this repo's CLAUDE.md, before some of these old migration files were edited to
declare indexes explicitly. Laravel never re-runs a migration once its row
exists, so editing a 2020 file changes what `migrate:fresh` produces today
without changing what already-migrated databases carry.

Re-measuring the same two queries against a database built by `migrate:fresh`
finds only 19 of the 89 index names still wrong, plus the same 20 constraints
(constraints are unaffected: every one already went through an explicit
`ADD CONSTRAINT` in a real migration on both histories, so the two agree).
The other 70 are cases where `migrate:fresh` already produces the right name
today and the long-lived database still carries what an older version of that
file, or no explicit call at all, produced when it actually ran. This is the
exact trap [the foreign-key rename](2026-08-23-foreign-key-rename.md) named
first, for table renames rather than bare index names, and drew the same
conclusion from: *"index names differ between production and a
`migrate:fresh` database... so the campaign's migrations look up names at run
time."*

So this migration does too. `database/support/IndexRenamer.php` finds each
index **by its column list**, not by either measured name, and only issues
the `RENAME KEY` if what it finds does not already match the target. It is
correct — and a no-op on the 70 that don't need it — whichever of the two
histories it runs against, and would remain correct against a third history
neither measurement saw. The full 89 are still recorded below and in the
migration's `INDEXES` constant, because a name absent from both current
measurements does not mean absent everywhere, and the constant's third
element is what `down()` restores.

## Two mechanisms, not one

MariaDB 10.11 can rename an index in place; it cannot rename a constraint
([foreign-key rename](2026-08-23-foreign-key-rename.md), "The trap: constraint
and index names do not follow the column"). `IndexRenamer` carries both, as
the sibling of `TableRenamer` this campaign has cited as future work since the
pluralisation:

- **`renameIndexes()`** — looks up the index currently covering each target's
  column list, then `ALTER TABLE … RENAME KEY … TO …` if it isn't already
  named right. Metadata-only, and safe on an index that backs a live
  constraint: a constraint does not track its backing index by name, only by
  which columns it covers, so renaming the index underneath it changes
  nothing about how the constraint enforces.
- **`renameForeignKeys()`** — drop and re-add under the new name, against the
  column, reference and `ON UPDATE`/`ON DELETE` rule read from the census
  above. Hard-coded rather than looked up, because the two histories agree on
  every one of these 20, unlike the 89 indexes. It creates no new index:
  MariaDB reuses whatever index already covers the constrained column, which
  by the time this runs is the index `renameIndexes()` just renamed to match.

The migration runs indexes first, constraints second, so that ordering holds;
`down()` reverses both, constraints first. Both no-op on SQLite, which neither
names indexes after their table nor supports `RENAME KEY` — the same guard
`TableRenamer` applies to its own raw statements, for the same reason.

`down()` restores the name each index carried on the long-lived database —
the one that matters, since that is what production and every real deploy
target actually has. On a `migrate:fresh` database, rolling back therefore
does not always restore byte-for-byte what that install started with (a
`_index`-suffixed name may go back to a bare one), which is a cosmetic
difference with no runtime effect and not something this migration promises;
migrating forward again immediately corrects it, because `renameIndexes()`
never assumes what it will find.

## The renames

### Indexes (`RENAME KEY`, found by column list) — 89

| Table | Name on the long-lived database | Laravel-derived name |
|---|---|---|
| `article_screenshot_comments` | `article_screenshot_comments_screenshot_article_id_foreign` | `article_screenshot_comments_article_screenshot_id_foreign` |
| `comments` | `user_id` | `comments_user_id_index` |
| `dumps` | `media_id` | `dumps_media_id_index` |
| `dumps` | `user_id` | `dumps_user_id_index` |
| `game_akas` | `game_id` | `game_akas_game_id_index` |
| `game_akas` | `language_id` | `game_akas_language_id_index` |
| `game_control` | `control_id` | `game_control_control_id_index` |
| `game_control` | `game_id` | `game_control_game_id_index` |
| `game_developer` | `developer_role_id` | `game_developer_developer_role_id_index` |
| `game_developer` | `game_id` | `game_developer_game_id_index` |
| `game_developer` | `pub_dev_id` | `game_developer_company_id_index` |
| `game_engine` | `engine_id` | `game_engine_engine_id_index` |
| `game_engine` | `game_id` | `game_engine_game_id_index` |
| `game_genre` | `game_cat_id` | `game_genre_genre_id_index` |
| `game_genre` | `game_id` | `game_genre_game_id_index` |
| `game_individual` | `game_id` | `game_individual_game_id_index` |
| `game_individual` | `individual_id` | `game_individual_individual_id_index` |
| `game_individual` | `individual_role_id` | `game_individual_individual_role_id_index` |
| `game_programming_language` | `game_id` | `game_programming_language_game_id_index` |
| `game_programming_language` | `programming_language_id` | `game_programming_language_programming_language_id_index` |
| `game_release_akas` | `game_release_id` | `game_release_akas_game_release_id_index` |
| `game_release_akas` | `language_id` | `game_release_akas_language_id_index` |
| `game_release_copy_protection` | `copy_protection_id` | `game_release_copy_protection_copy_protection_id_index` |
| `game_release_copy_protection` | `game_release_id` | `game_release_copy_protection_game_release_id_index` |
| `game_release_crew` | `crew_id` | `game_release_crew_crew_id_index` |
| `game_release_crew` | `game_release_id` | `game_release_crew_game_release_id_index` |
| `game_release_disk_protection` | `disk_protection_id` | `game_release_disk_protection_disk_protection_id_index` |
| `game_release_disk_protection` | `game_release_id` | `game_release_disk_protection_game_release_id_index` |
| `game_release_distributor` | `game_release_id` | `game_release_distributor_game_release_id_index` |
| `game_release_distributor` | `pub_dev_id` | `game_release_distributor_company_id_index` |
| `game_release_emulator_incompatible` | `emulator_id` | `game_release_emulator_incompatible_emulator_id_index` |
| `game_release_emulator_incompatible` | `game_release_id` | `game_release_emulator_incompatible_game_release_id_index` |
| `game_release_language` | `game_release_id` | `game_release_language_game_release_id_index` |
| `game_release_language` | `language_id` | `game_release_language_language_id_index` |
| `game_release_location` | `game_release_id` | `game_release_location_game_release_id_index` |
| `game_release_location` | `location_id` | `game_release_location_location_id_index` |
| `game_release_memory_enhanced` | `enhancement_id` | `game_release_memory_enhanced_enhancement_id_index` |
| `game_release_memory_enhanced` | `game_release_id` | `game_release_memory_enhanced_game_release_id_index` |
| `game_release_memory_enhanced` | `memory_id` | `game_release_memory_enhanced_memory_id_index` |
| `game_release_memory_incompatible` | `game_release_id` | `game_release_memory_incompatible_game_release_id_index` |
| `game_release_memory_incompatible` | `memory_id` | `game_release_memory_incompatible_memory_id_index` |
| `game_release_memory_minimum` | `game_release_id` | `game_release_memory_minimum_game_release_id_index` |
| `game_release_memory_minimum` | `memory_id` | `game_release_memory_minimum_memory_id_index` |
| `game_release_resolution` | `game_release_id` | `game_release_resolution_game_release_id_index` |
| `game_release_resolution` | `resolution_id` | `game_release_resolution_resolution_id_index` |
| `game_release_scans` | `game_release_id` | `game_release_scans_game_release_id_index` |
| `game_release_system_enhanced` | `enhancement_id` | `game_release_system_enhanced_enhancement_id_index` |
| `game_release_system_enhanced` | `game_release_id` | `game_release_system_enhanced_game_release_id_index` |
| `game_release_system_enhanced` | `system_id` | `game_release_system_enhanced_system_id_index` |
| `game_release_system_incompatible` | `game_release_id` | `game_release_system_incompatible_game_release_id_index` |
| `game_release_system_incompatible` | `system_id` | `game_release_system_incompatible_system_id_index` |
| `game_release_tos_incompatibilities` | `game_release_id` | `game_release_tos_incompatibilities_game_release_id_index` |
| `game_release_tos_incompatibilities` | `language_id` | `game_release_tos_incompatibilities_language_id_index` |
| `game_release_tos_incompatibilities` | `tos_id` | `game_release_tos_incompatibilities_tos_id_index` |
| `game_release_trainer_option` | `game_release_id` | `game_release_trainer_option_game_release_id_index` |
| `game_release_trainer_option` | `trainer_option_id` | `game_release_trainer_option_trainer_option_id_index` |
| `game_releases` | `game_id` | `game_releases_game_id_index` |
| `game_releases` | `pub_dev_id` | `game_releases_company_id_index` |
| `game_screenshot` | `game_id` | `game_screenshot_game_id_index` |
| `game_similar` | `game_similar_game_similar_cross_foreign` | `game_similar_similar_game_id_foreign` |
| `game_sound_hardware` | `game_id` | `game_sound_hardware_game_id_index` |
| `game_sound_hardware` | `sound_hardware_id` | `game_sound_hardware_sound_hardware_id_index` |
| `game_submission_screenshot` | `game_submission_screenshot_game_submitinfo_id_foreign` | `game_submission_screenshot_game_submission_id_foreign` |
| `game_submissions` | `user_id` | `game_submissions_user_id_index` |
| `game_vs` | `game_vs_atari_id_lemon64_slug_amiga_id_index` | `game_vs_atari_id_lemon64_slug_lemonamiga_id_index` |
| `games` | `game_progress_system_id` | `games_game_progress_system_id_index` |
| `games` | `game_series_id` | `games_game_series_id_index` |
| `games` | `port_id` | `games_port_id_index` |
| `interview_screenshot_comments` | `interview_screenshot_comments_screenshot_interview_id_foreign` | `interview_screenshot_comments_interview_screenshot_id_foreign` |
| `interviews` | `user_id` | `interviews_user_id_index` |
| `link_category` | `link_category_website_category_id_foreign` | `link_category_category_id_foreign` |
| `link_category` | `link_category_website_id_website_category_id_unique` | `link_category_link_id_category_id_unique` |
| `links` | `user_id` | `links_user_id_index` |
| `locations` | `continent_code` | `locations_continent_code_iso2_unique` |
| `magazine_indices` | `magazine_game_game_id_foreign` | `magazine_indices_game_id_foreign` |
| `magazine_indices` | `magazine_game_magazine_index_type_id_foreign` | `magazine_indices_magazine_index_type_id_foreign` |
| `magazine_indices` | `magazine_game_magazine_issue_id_foreign` | `magazine_indices_magazine_issue_id_foreign` |
| `magazine_indices` | `magazine_game_menu_software_id_foreign` | `magazine_indices_menu_software_id_foreign` |
| `magazine_issues` | `magazine_issue_magazine_id_foreign` | `magazine_issues_magazine_id_foreign` |
| `magazines` | `magazine_location_id_foreign` | `magazines_location_id_foreign` |
| `media` | `game_release_id` | `media_game_release_id_index` |
| `media` | `media_type_id` | `media_media_type_id_index` |
| `media_scans` | `media_id` | `media_scans_media_id_index` |
| `media_scans` | `media_scan_type_id` | `media_scans_media_scan_type_id_index` |
| `news` | `user_id` | `news_user_id_index` |
| `review_screenshot_comments` | `review_screenshot_comments_screenshot_review_id_foreign` | `review_screenshot_comments_review_screenshot_id_foreign` |
| `reviews` | `user_id` | `reviews_user_id_index` |
| `sndhs` | `title_search` | `sndhs_title_fulltext` |
| `users` | `userid` | `users_userid_index` |

19 of these — `game_similar`, `game_submission_screenshot`, `game_vs`, the
three `*_screenshot_comments` rows, both `link_category` rows, all four
`magazine_indices` rows, `magazine_issues`, and `magazines` — are also wrong
on a fresh install, because each already carries a Laravel-shaped suffix
(`_foreign`, `_unique`) pointing at a stale table or column name, which
`migrate:fresh` reproduces exactly rather than papering over. The other 70 are
the ones a fresh install already gets right; they are still fixed here
because the long-lived database, and everything that deploys the same way
production does, does not.

`users_userid_index` is the one entry with no rename history behind it — it
is the survivor the sweep's Phase 1 left after dropping the duplicate
`userid_2`/`userid_3` indexes, never itself renamed. `locations_continent_code_iso2_unique`
and `game_vs_…_lemonamiga_id_index` are the only two multi-column entries; the
[entity-names plan](2026-09-02-entity-names-and-pivot-order.md) flagged the
`game_vs` one by name when the `lemonamiga_id` rename created it.

### Foreign keys (`DROP FOREIGN KEY` + `ADD CONSTRAINT`, hard-coded) — 20

| Table | Old constraint | New constraint | Column | References | ON UPDATE | ON DELETE |
|---|---|---|---|---|---|---|
| `article_screenshot_comments` | `article_screenshot_comments_screenshot_article_id_foreign` | `article_screenshot_comments_article_screenshot_id_foreign` | `article_screenshot_id` | `article_screenshot.id` | RESTRICT | CASCADE |
| `dumps` | `dumps_ibfk_2` | `dumps_user_id_foreign` | `user_id` | `users.id` | RESTRICT | RESTRICT |
| `game_akas` | `game_akas_ibfk_1` | `game_akas_language_id_foreign` | `language_id` | `languages.id` | RESTRICT | RESTRICT |
| `game_developer` | `game_developer_pub_dev_id_foreign` | `game_developer_company_id_foreign` | `company_id` | `companies.id` | RESTRICT | CASCADE |
| `game_genre` | `game_genre_game_genre_id_foreign` | `game_genre_genre_id_foreign` | `genre_id` | `genres.id` | RESTRICT | CASCADE |
| `game_release_distributor` | `game_release_distributor_pub_dev_id_foreign` | `game_release_distributor_company_id_foreign` | `company_id` | `companies.id` | RESTRICT | CASCADE |
| `game_releases` | `game_releases_ibfk_3` | `game_releases_company_id_foreign` | `company_id` | `companies.id` | RESTRICT | RESTRICT |
| `game_similar` | `game_similar_game_similar_cross_foreign` | `game_similar_similar_game_id_foreign` | `similar_game_id` | `games.id` | RESTRICT | CASCADE |
| `game_submission_screenshot` | `game_submission_screenshot_game_submitinfo_id_foreign` | `game_submission_screenshot_game_submission_id_foreign` | `game_submission_id` | `game_submissions.id` | RESTRICT | CASCADE |
| `games` | `games_ibfk_1` | `games_game_series_id_foreign` | `game_series_id` | `game_series.id` | RESTRICT | RESTRICT |
| `interview_screenshot_comments` | `interview_screenshot_comments_screenshot_interview_id_foreign` | `interview_screenshot_comments_interview_screenshot_id_foreign` | `interview_screenshot_id` | `interview_screenshot.id` | RESTRICT | CASCADE |
| `link_category` | `link_category_website_category_id_foreign` | `link_category_category_id_foreign` | `category_id` | `categories.id` | RESTRICT | CASCADE |
| `link_category` | `link_category_website_id_foreign` | `link_category_link_id_foreign` | `link_id` | `links.id` | RESTRICT | CASCADE |
| `magazine_indices` | `magazine_game_game_id_foreign` | `magazine_indices_game_id_foreign` | `game_id` | `games.id` | CASCADE | CASCADE |
| `magazine_indices` | `magazine_game_magazine_index_type_id_foreign` | `magazine_indices_magazine_index_type_id_foreign` | `magazine_index_type_id` | `magazine_index_types.id` | RESTRICT | SET NULL |
| `magazine_indices` | `magazine_game_magazine_issue_id_foreign` | `magazine_indices_magazine_issue_id_foreign` | `magazine_issue_id` | `magazine_issues.id` | CASCADE | CASCADE |
| `magazine_indices` | `magazine_game_menu_software_id_foreign` | `magazine_indices_menu_software_id_foreign` | `menu_software_id` | `menu_software.id` | CASCADE | CASCADE |
| `magazine_issues` | `magazine_issue_magazine_id_foreign` | `magazine_issues_magazine_id_foreign` | `magazine_id` | `magazines.id` | CASCADE | CASCADE |
| `magazines` | `magazine_location_id_foreign` | `magazines_location_id_foreign` | `location_id` | `locations.id` | CASCADE | SET NULL |
| `review_screenshot_comments` | `review_screenshot_comments_screenshot_review_id_foreign` | `review_screenshot_comments_review_screenshot_id_foreign` | `review_screenshot_id` | `review_screenshot.id` | RESTRICT | CASCADE |

Four of these — `dumps_ibfk_2`, `game_akas_ibfk_1`, `games_ibfk_1`,
`game_releases_ibfk_3` — are auto-generated names from a constraint added
without Laravel ever naming it. The sweep's "Out of scope" listed their
pre-rename table names (`dump`, `game_aka`, `game`, `game_release`) as
`dump_ibfk_2` etc.; the number carried forward through every table rename
since, because InnoDB renumbers nothing, and — unlike the bare index names
above — the number is identical on both a `migrate:fresh` install and the
long-lived database, so it is safe to hard-code.

Six — the `magazine_indices`, `magazine_issues` and `magazines` rows — are the
pluralisation's leftovers, called out by name in the sweep as reading like
correct Laravel names while being wrong: `dropForeign(['location_id'])` on
`magazines` derives `magazines_location_id_foreign`, not the
`magazine_location_id_foreign` actually there.

The other ten are this campaign's own residue, one per rename that moved a
column but not the table it lives on — `pub_dev_id` → `company_id` (three
rows), `game_submitinfo_id` → `game_submission_id`, `website_id`/
`website_category_id` → `link_id`/`category_id` (two rows on the same table),
`game_similar_cross` → `similar_game_id`, `genre_id`'s constraint still
reading `game_genre_id`, and the three `screenshot_{article,interview,review}`
pivots whose comment tables still say `screenshot_{article,interview,review}_id`
where the column is now `{article,interview,review}_screenshot_id`.

No new mismatch is introduced: every target name was checked against
MariaDB's 64-character identifier limit; the longest here is
`interview_screenshot_comments_interview_screenshot_id_foreign` at 62.

## Why one migration, not several

Every prior campaign split into phases or units where two renames could
collide — a column and the table underneath it, a pivot and its comment child.
None of that applies here: an index rename never touches a column, a
constraint rename never touches a table, and no two rows in either table above
share a table *and* a column. The 109 entries are independent of each other
and safe to apply in any order within their own list; grouping them in one
migration costs nothing and avoids 60-odd near-empty migration files for a
change the sweep already ruled has no functional risk.

## Risk

None beyond DDL itself — metadata only, no row touched, no type changed, no
rule changed. The two-statement constraint swap
(`DROP FOREIGN KEY` / `ADD CONSTRAINT`) briefly removes enforcement on that one
column, the same window every constraint-renaming migration in this campaign
has already accepted since `TableRenamer` was written.

## Acceptance

Verified 2026-09-06 against both histories the census section describes:

- **A `migrate:fresh` database**: after this migration, the two census queries
  return zero mismatches; `migrate:rollback --step=1` then `migrate` again
  both run clean; the full `migrate:fresh` run (every migration since 2020, in
  order) completes without error, which the first draft of this migration did
  not.
- **The long-lived database**: the same two census queries against the
  pre-migration state return exactly the 89 and 20 rows above; after
  migrating, zero mismatches; after `migrate:rollback --step=1`, all 109 old
  names restored exactly (checked programmatically, not by inspection); after
  migrating again, zero mismatches.
- `php artisan test` passes on both (1010 tests; the migration no-ops under
  SQLite, so this confirms nothing else broke).
- No `app/`, `resources/`, `database/seeders/` or `tests/` file names any of
  the 109 old strings — confirmed by grep before this plan was written, and
  by design: index and constraint names are still not part of any gate.

## Deploying

A dump immediately before, as every campaign before it. One migration, one
commit, one deploy — there is no phase boundary to preserve.
