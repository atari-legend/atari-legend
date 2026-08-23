# Renaming the foreign keys

Successor to `2026-08-17-primary-key-rename.md`, which closed with four foreign
key inconsistencies "recorded as follow-up, not done here". This plan assesses
that follow-up. It is written to be read alongside the primary-key plan, and it
does not repeat what that document already establishes about deploy ordering,
`renameColumn` on MariaDB, or the strictness guards in `AppServiceProvider`.

**Status: proposal. Nothing here has been implemented.** Every number and every
behaviour claim below was measured against this repository and a local MariaDB
10.11.18 on 2026-08-23; the commands are named so they can be re-run.

## The short version

The primary-key campaign framed itself as "rename 34 columns". Framing this one
the same way is the mistake to avoid. **Most of the divergence from Laravel's
convention is not in the schema at all**, and the schema changes that are worth
making are a minority of the work:

This table is the plan's conclusion, not its starting analysis — the
dispositions below were argued out in review and the reasoning for each is in
its own section.

| | Relations | Phase | Needs a migration? |
|---|---|---|---|
| Explicit key argument that is **already** Eloquent's default | 76 | A | No — delete the argument |
| No explicit key argument (already clean) | 35 | — | No |
| Divergent, fixed by renaming a **PHP method** | 5 | B | No |
| Divergent, fixed by renaming a **column** | 20 | C | Yes |
| Divergent because the relation is **wrong** (one unused, one live) | 2 | A | No — delete one, convert one |
| Divergent, convention **unreachable or declined** | 20 | D | No — keep the argument, record why |
| Divergent, **deferred** (`News::image()`) | 1 | — | No |
| **Total relations in `app/Models/`** | **159** | | |

The 48 divergent relations are the last five rows. Two notes on the edges:

- The 20 declined include the three `type()` relations, which Phase B could
  reach by renaming a method and deliberately does not — `->type` is 105 hits
  repo-wide and mostly plain columns.
- One of the 20 — `Release::distributors()`, on `game_release_distributor` — is
  only half fixed by its column rename: `game_release_id` moves and `pub_dev_id`
  does not, so it ends the campaign with one explicit argument rather than none.

The 20 column-rename relations resolve to **five renames covering 16 columns
across 16 tables**, listed in Phase C. So the campaign is: delete 76 arguments,
delete one dead relation and convert one broken one, rename 5 methods, rename 16 columns, and write down why
the remaining 20 stay as they are. The first item is the largest by a distance,
carries no database risk at all, and can start today.

### How these numbers were obtained

Not by grep. A script booted the application, reflected every public
zero-argument method on all 88 models, called it, kept the ones returning an
Eloquent `Relation`, and compared the relation's **actual** foreign key against
the key **Eloquent would derive by default** for that relation — using the same
three formulas Laravel uses internally. Counting `->hasMany(` occurrences by
hand gets a different and wrong answer, because the question "is this argument
redundant?" depends on the relation type, the method name, the model class name
and the related model's key name all at once.

The three formulas, which are the whole subject of this plan:

- **`belongsTo`** derives the foreign key from the **relationship method name**:
  `Str::snake($method).'_'.$related->getKeyName()`. Not from the related model.
- **`hasOne` / `hasMany`** derive it from the **declaring model's class name**:
  `Str::snake(class_basename($this)).'_'.$this->getKeyName()`. Not from its table.
- **`belongsToMany`** derives both pivot keys from the two **class names**, the
  same way.

Every surprise in this document follows from one of those three sentences, and
in particular from the two "not from" clauses. `Release`'s table is
`game_release` but its class is `Release`, so convention wants `release_id`.
`Article::type()` is a method called `type`, so convention wants `type_id`
whatever the related class is called.

## Phase A — delete the 76 redundant arguments

These relations pass an explicit key argument that is character-for-character
what Eloquent would have derived anyway. Deleting the argument changes no SQL.
There is no migration, no schema change, and no production risk; the only way
to get it wrong is to delete an argument that was *not* redundant, and the
script above is what says which is which.

Representative shapes, all already conventional today:

```php
$this->belongsTo(User::class, 'user_id')                      // Article::user()
$this->hasMany(Release::class, 'game_id')                     // Game::releases()
$this->belongsToMany(Memory::class, 'game_release_memory_minimum', 'release_id', 'memory_id')
```

The 76 are spread over 28 models; `User` contributes 9, `Game` 17, `Release` 8.
The full list is reproducible from the audit script and is not reproduced here,
because a list in a document goes stale and the script does not.

Two cautions, both learned from the primary-key campaign:

- **`belongsToMany`'s second argument is the pivot table name and mostly stays.**
  Only the third and fourth arguments are keys. Laravel derives the pivot table
  name alphabetically from the two class names, which is right for
  `crew_individual` and `game_individual` but wrong for `game_release_crew`
  (it would guess `crew_release`). Delete key arguments; leave table arguments
  alone unless the derived name is verified to match.
- **`withPivot()` is unaffected.** It names the pivot's own columns, not keys.

**"No SQL changes" is a claim to verify, not to assume** — raised by OpenCode
while reviewing this plan, and the caution above is not enough on its own. The
derive-the-pivot-name path is *live* in this codebase: `Crew::menuSets()` passes
`null` for the table and `MenuSet::crews()` passes `null, null`, so both already
depend on Laravel guessing `crew_menu_set`. One argument deleted a position too
far turns `game_release_crew` into `crew_release`, and nothing fails until that
relation is exercised.

So Phase A gets a mechanism rather than a warning. The audit script takes a
`pivots` argument and prints the resolved pivot table for all 51
`belongsToMany` relations; snapshot before the edit, snapshot after, and diff:

```
docker compose run --rm --no-deps php php \
  docs/plans/2026-08-23-foreign-key-rename-audit.php pivots > pivots.before
# ... delete the arguments ...
diff pivots.before pivots.after     # must be empty
```

Acceptance: that diff empty, `artisan test` and Playwright both green, and no
line of generated SQL changed. This is a pure no-op and should be reviewed as
one.

### One pull request, not thirty

Settled with OpenCode. 76 deletions across 28 models is not reviewable by eye,
and that is the argument *for* keeping it whole rather than against: it is
reviewable **by diff** — the pivot snapshot must be unchanged, the generated SQL
must be unchanged, and both suites must be green. Splitting it per model would
multiply CI runs without adding a single bit of signal, and would make the SQL
diff — the only check that actually proves the claim — into thirty partial
diffs that each prove less.

The six declarations the script cannot rewrite (multi-line, `->withPivot()`,
`->using()`) are the exception worth calling out in the pull request text, so a
reviewer knows which handful to read closely rather than skimming all 76
equally.

### The harness earned its keep before the phase even started

Phase A was run as an experiment rather than described: generated SQL captured
for all 162 relations, the deletions applied by script, SQL captured again and
diffed. **Three relations changed**, and all three broke:

```
< ScreenshotArticle::comment()   | ... where `article_comments`.`screenshot_article_id` is null
> ScreenshotArticle::comment()   | ... where `article_comments`.`` is null
```

`ScreenshotArticle`, `ScreenshotInterview` and `ScreenshotReview` all extend
`Pivot`, and `AsPivot` **overrides `getForeignKey()`** to return the pivot's
runtime-configured foreign key rather than one derived from the class name. On a
freshly instantiated pivot that is null, so there is no derivable default and
the explicit argument is load-bearing.

The audit script had reported all three as redundant because it *reimplemented*
Laravel's formula — `Str::snake(class_basename($model)).'_'.$model->getKeyName()`
— instead of asking Laravel. It now calls `$model->getForeignKey()`, and the
corrected counts are **76 redundant, 48 divergent, 35 clean**. Earlier drafts of
this plan said 79 and 45.

Two things worth taking from that. The narrow one: the three pivot relations
move to Phase D, as a category of their own — *the model is a `Pivot`, so no
default exists*. The general one: **a tool that reimplements the framework's
rule is only as good as the reimplementation**, and the whole premise of this
plan is that asking Laravel beats grepping. The audit was doing to Laravel's
formula exactly what a `grep` does to a column name. The SQL diff is what caught
it, which is the argument for running that diff on every Phase A pull request
rather than trusting the count.

Worth recording that the same flaw sat in the `belongsToMany` branch and was
fixed with it. It changed no counts — no `belongsToMany` is declared on a
`Pivot` — so it was latent rather than active, which is exactly the kind of
thing that survives a review of the output.

### Phase A, re-run and verified

With the classification corrected, the experiment was repeated:

- **70 arguments deleted, SQL diff across all 162 relations empty.** Not one
  character changed.
- **`artisan test`: 991 passed, 18 skipped, 3511 assertions** — identical to the
  figure the primary-key campaign signed off on.

So "Phase A is a no-op" is now a measurement rather than a claim. Two caveats
that belong with it rather than after it:

- **The script reached 70 of the 76.** The remaining six are multi-line or
  chained declarations — `->withPivot()`, `->using()` — that a line-oriented
  rewrite cannot safely touch. Phase A is therefore about seventy mechanical
  edits plus six careful ones, not one clean sweep.
- **An empty SQL diff and a green suite prove the *relations* are unchanged.**
  They do not prove nothing else in those 26 files was disturbed. That is what
  reading the diff is for, and it is the reason this phase is still a reviewed
  pull request rather than a scripted commit.

## Phase B — the ones that are a method name, not a column

Because `belongsTo` reads the **method** name, nine divergences *could* be
closed without the database being involved. Five of them should be; the reasons
the other four are not are as much a part of this phase as the renames:

| Relation | Column today | Convention wants | Rename the method to |
|---|---|---|---|
| `Article::type()` | `article_type_id` | `type_id` | `articleType()` |
| `Media::type()` | `media_type_id` | `type_id` | `mediaType()` |
| `MediaScan::type()` | `media_scan_type_id` | `type_id` | `mediaScanType()` |
| `GameDeveloper::role()` | `developer_role_id` | `role_id` | `developerRole()` |
| `GameIndividual::role()` | `individual_role_id` | `role_id` | `individualRole()` |
| `Game::series()` | `game_series_id` | `series_id` | `gameSeries()` |
| `News::image()` | `news_image_id` | `image_id` | `newsImage()` |
| `MenuDisk::donatedBy()` | `donated_by_individual_id` | `donated_by_id` | `donatedByIndividual()` |
| `GameVs::game()` | `atari_id` | `game_id` | `atari()` |

Note which way round this goes. The column names here are **good** —
`article_type_id` says more than `type_id` — and the convention would degrade
them. The method names are what should move.

**This phase is not free, and it is the one place this campaign repeats the
primary-key campaign's hardest problem.** A relationship method rename has to
follow through `->type`, `with('type')`, `whereHas('type')`, Livewire table
sort and search keys, and Blade. And `type` is *also* an ordinary column on
`ReleaseScan`, `MediaScan` and others: `grep -r '\->type'` returns **105** hits
across `app/`, `resources/views/` and `tests/`, of which only a handful are the
relationship. That is precisely the "roughly 85-90% of occurrences are
something else" hazard the primary-key plan documented, and the reason that
plan insisted on judgement per site rather than token substitution.

Priced by how ambiguous the token is:

| Method | `->method` hits repo-wide | Verdict |
|---|---|---|
| `vs` | 5 | Safe — do it |
| `series` | 7 | Safe — do it |
| `donatedBy` | 10 | Safe — do it |
| `role` | 16 | Safe with care — two models |
| `image` | 28 | Ambiguous — defer |
| `type` | 105 | Ambiguous — **keep the explicit argument** |

**Decided, with OpenCode: do `vs`, `series`, `donatedBy` and `role`; keep the
arguments on `type()` and defer `image`.** `role` looked borderline on the hit
count alone, and what settles it is that both `role()` relations live on custom
`Pivot` models (`GameDeveloper`, `GameIndividual`) and are reached as
`$x->pivot->role->name` from six Blade files and `GameCreditsController` — every
call site names the concrete pivot class, so the token is fully traceable. There
is also no bare `role` column anywhere; only `developer_role_id` and
`individual_role_id`. Neither of those things is true of `type`.

Three explicit arguments is a cheaper price than a 105-site token audit for no
behavioural gain.

There is no `getRouteKeyName()`, `resolveRouteBinding()` or `Route::bind()` in
the repository, so relationship method renames cannot disturb route binding.

## Phase C — the column renames

Only these actually need migrations. Each converges on a name **the schema
already uses elsewhere**, which is the argument for doing them at all: the
inconsistency is internal to this database, not merely a deviation from a
framework's taste.

| Rename | Tables | Already-conventional siblings |
|---|---|---|
| `game_release_id` → `release_id` | 9 | 10 tables already say `release_id` |
| `ind_id` → `individual_id` | 4 | `game_individual`, `magazine_indices` |
| `comments_id` → `comment_id` | 1 (`article_user_comments`) | the other 3 comment pivots |
| `game_genre_id` → `genre_id` | 1 (`game_genre_cross`) | — |
| `dev_pub_id` → `pub_dev_id` | 1 (`game_developer`) | `game_release`, `pub_dev_text` |

The `game_release_id` split is worth stating plainly, because it is the single
best justification for this campaign: of the 19 tables holding a foreign key to
`game_release`, **10 call it `release_id` and 9 call it `game_release_id`**. The
schema is not consistently legacy; it is inconsistently half-migrated already,
and a developer cannot guess which name a given pivot uses. Laravel's convention
happens to pick the majority name.

`ind_id` is the same shape at smaller scale (2 tables already say
`individual_id`), and `comments_id` is a single outlier against three siblings.

### Order

Ascending by **silent** risk, not by table count — an earlier draft ordered by
blast radius and OpenCode was right that this is the better axis. One column
family per PR:

1. `comments_id` → `comment_id` — one table, one relation pair, no `$fillable`.
2. `game_genre_id` → `genre_id`, `dev_pub_id` → `pub_dev_id` — one table each.
3. `game_release_id` → `release_id` — nine tables, but every one of them loud:
   eight are `NOT NULL` pivots and the ninth, `menu_disk_contents`, is written
   through a relationship save (below).
4. `ind_id` → `individual_id` — only four tables, but it carries the campaign's
   single silent write. It goes **last**, not because it is the biggest but
   because it is the one that can be got wrong quietly. By the time it runs the
   recipe has been exercised twice, the checklist template has been proven, and
   the decision about the production `$fillable` guard has already been taken.
   Do it alone.

### Worked example: every site the `ind_id` rename touches

`ind_id` is the dangerous one, so it gets enumerated rather than left to a grep
at the time. This doubles as the checklist template for the other four renames:
the *categories* are what generalise, not the line numbers.

**Schema (4 tables).** `crew_individual`, `individual_nicks`, `individual_text`,
`interview_main`. `individual_nicks` also has `nick_id` pointing at the same
parent, which stays — see Phase D.

**Relationship definitions (6).** `Individual::text()`, `::interviews()`,
`::nicknames()`, `::individuals()`, `::crews()`, `Crew::individuals()`,
`Interview::individual()`. Four of these lose their argument; the two
self-referential ones keep both.

**`$fillable` (1).** `Interview.php:18` — and this is the campaign's one silent
write, covered above.

**A property read of the foreign key (1), and it is the one to fear.**

```php
// app/Models/IndividualText.php:17-21
// ind_id, not getKey(): this model's key is ind_text_id, but the
// ... filename is built from the PARENT's id
return Helper::filename($this->ind_id, $this->ind_imgext);
```

The primary-key campaign hit this exact line from the other direction and
records the outcome: rewritten to `getKey()`, "every individual avatar and
company logo 404d". It is a `MissingAttributeException` in dev and a silent
`null` filename in production, so the failure is 404s on every avatar rather
than an error page. Playwright is what catches it.

**Query-builder string columns (4).** Two Livewire join closures —
`InterviewsTable:65` (`interview_main.ind_id`) and `GameIndividualsTable:62`
(`individual_text.ind_id`) — and two calls to a *helper* that takes the column
name as an argument:

```php
// app/Helpers/AdminStatisticsHelper.php:122 and :183
self::countWithText('individual_text', 'ind_profile', 'ind_id')
```

That third category is worth naming separately: **a foreign key passed as data**.
It is not a query literal and not a property, so a reviewer scanning for either
shape misses it, and no arch test of the `QueryConventionsTest` kind can reach
it — a lint over query syntax cannot know that the third argument of a helper is
a column name.

It is, however, covered *functionally*, which is the point of preferring
functional tests: `Admin/StatisticsTest:153`
(`test_coverage_ignores_individuals_without_a_bio`) inserts into
`individual_text` and asserts the "Individuals with a bio" figure, so it runs
that helper for real. It also writes `ind_id` twice through `DB::table()`, which
is a raw insert and therefore fails loudly on 1054 after the rename. So the site
is both covered and self-announcing — it just is not reachable by static
tooling.

**Factories and seeders (4).** `IndividualFactory:32`, `InterviewFactory:25`,
`E2ESeeder:434` and `:439`. The primary-key campaign's harness change 1 gives
each table a distinct id range, so a foreign key wired to the wrong parent now
produces a visibly wrong number here rather than a coincidentally right one.

**PHPUnit (7 files).** `NormaliseBlankProfilesTest`, `Admin/StatisticsTest`,
`Admin/Interviews/InterviewsControllerTest`, `Public/AutocompleteTest`,
`Public/GamePageTest`, `Public/ResourceControllersTest`,
`Public/AjaxEndpointsTest`.

**Playwright: nothing.** No spec names the column. It exercises the same paths
through the UI, which is exactly why it is the net for the avatar bug above.

**The autocomplete wire format: do not touch it, or move all eight together.**

```php
// app/Http/Controllers/Ajax/IndividualController.php:35
'ind_id' => $individual->getKey(),
```

This is an **array literal**, so the JSON key is a name the endpoint chooses,
not a column — it survived the primary-key rename unchanged for that reason. It
pairs with seven `data-autocomplete-id="ind_id"` attributes in Blade, and
`resources/js/autocomplete.js:83` reads
`feedback.selection.value[el.dataset.autocompleteId]`. The form field is called
`individual`, not `ind_id`, so the controller already maps between the two and
**the column rename does not require this to change at all.**

The trap is the tidy-up. Renaming the JSON key for consistency without moving
all seven Blade attributes assigns `undefined` into the hidden field, which
stringifies and submits happily — no PHP error, no SQL error, nothing in the
log. The primary-key plan's harness change 4 put a guard on exactly this:
`pickAutocompleteBy` now rejects the string `"undefined"`, so every existing
autocomplete spec is a wire-format check.

**Decision: the wire key stays `ind_id`.** An earlier draft offered "leave it or
move it all together" and left the choice open, which OpenCode rightly called a
dodge — so it is settled here. Moving it costs the endpoint, seven Blade
attributes and `AjaxEndpointsTest:174`, buys no behaviour, and its failure mode
is silent on both the server and the client. A rename campaign should not spend
its risk budget on a name that no database column will carry.

Two things go with that decision, because "fossil" is a fair charge:

- A comment at `Ajax/IndividualController.php:35` saying the key is a wire name
  and deliberately *not* the column, so the next person does not tidy it.
- A separate follow-up, with its own PR and its own risk budget: **make every
  autocomplete endpoint emit `id`.** Ten `data-autocomplete-id` attributes
  already say `id`; only the seven `ind_id` and seven `game_id` ones do not.
  That is consistency between *endpoints*, which is a better argument than
  consistency with the schema, and it is not this campaign's job.

## Phase D — the nine that convention cannot reach, and the eleven it should not

Write these down rather than leaving them to be rediscovered.

**Unreachable.** A self-referential `belongsToMany` needs two *different* pivot
key names, and convention derives the *same* name for both. The explicit
arguments can never be deleted:

- `Crew::parentCrews()` / `Crew::subCrews()` — `sub_crew (crew_id, parent_id)`
- `Individual::nicknames()` / `Individual::individuals()` — `individual_nicks (ind_id, nick_id)`
- `Game::similarGames()` / `Game::similarGamesReverse()` — `game_similar (game_id, game_similar_cross)`

(The `ind_id` half of `individual_nicks` still renames in Phase C; the relation
keeps its arguments regardless.)

**No default exists.** `ScreenshotArticle::comment()`,
`ScreenshotInterview::comment()` and `ScreenshotReview::comment()` are declared
on `Pivot` subclasses, whose `getForeignKey()` is overridden by `AsPivot` to
return a runtime value that is null outside a `belongsToMany` hydration. Their
arguments are not redundant and cannot be removed — see the Phase A experiment.

**Deliberately not adopted.**

- **`PublisherDeveloper` → `publisher_developer_id`.** Convention derives the
  key from the class name, so it wants `publisher_developer_id` on
  `game_release`, `game_release_distributor` and `pub_dev_text`. The table is
  `pub_dev`, the columns are `pub_dev_*`, and `pub_dev` is the vocabulary the
  whole application uses. The alternative — renaming the class to `PubDev` —
  makes `pub_dev_id` conventional at the cost of 23 references in `app/` and 44
  in `tests/`, and a worse class name. **Recommendation: keep the four explicit
  arguments.** Still do `dev_pub_id` → `pub_dev_id` in Phase C, which is an
  internal inconsistency worth fixing on its own merits and is unrelated to the
  convention question.
- **`Release::publisher()`** wants `publisher_id` by the method-name rule.
  Follows the decision above: keep the argument.
- **`GameVs`'s `atari_id` / `amiga_id`.** These say something `game_id` would
  not. `Game::vs()` is a `hasMany` and so cannot be fixed by a method rename;
  it keeps its argument. Only `GameVs::game()` → `atari()` is in Phase B.
- **`Release::trainers()`** wants `trainer_id`; the column is
  `trainer_option_id` and the table is `trainer_option`. The clean fix is to
  rename the *model* `Trainer` → `TrainerOption`, which also matches its table
  — 7 references in `app/`, 9 in `tests/`. Cheap, but it is a model rename in a
  foreign key plan; recorded as optional.
- **`GameSubmitInfo::screenshots()`** wants `game_submit_info_id` for
  `game_submitinfo_id`, purely because `Str::snake('GameSubmitInfo')` inserts an
  underscore the table name does not have. Not worth a migration. Keep the
  argument.
- **`Article::type()`, `Media::type()`, `MediaScan::type()`.** Phase B *could*
  reach these by renaming the method, and declines to — see the pricing table
  there. `->type` is 105 hits repo-wide and mostly plain columns on other
  models, which is the primary-key campaign's worst hazard reproduced for no
  behavioural gain. Three explicit arguments is the cheaper price. Listed here
  rather than in Phase B so the "declined" count is honest.

## Two defects the audit found

Both are fixed **by** adopting the convention, not in spite of it, and both are
worth landing in Phase A regardless of what happens to the rest of the plan.

**`Screenshot::reviewScreenshots()`** is
`hasMany(ScreenshotReview::class, 'id')` — it joins `screenshot_review.id`
against `screenshot_main.id`, which is not a relationship at all. It carries a
`FIXME` left by the primary-key campaign, which correctly declined to fix it
there because that is a behaviour change, not a rename. `screenshot_review` has
a `screenshot_id` column, and `screenshot_id` is exactly what convention
derives — so deleting the argument fixes the bug. The method is unreferenced
anywhere outside its own declaration, so this is safe, but it should get a test
that actually calls it before the argument goes, otherwise the fix is unproven.

**`GameAka::game()`** is `hasOne(Game::class, 'id', 'game_id')` — a `hasOne`
pinned at both ends to model what is really a `belongsTo`. `belongsTo(Game::class)`
derives `game_id` and needs no arguments.

**Convert it; do not delete it.** An earlier draft of this plan recommended
deletion on the grounds that it had no callers. That was wrong — it has six,
in two controllers:

- `Admin/Ajax/GameController.php:47,48,54` — the admin game autocomplete reads
  `$aka->game?->developers` to label an AKA row, and `$aka->game->getKey()` to
  give the row its id.
- `Admin/Games/GameController.php:289,290,296` — deleting an AKA reads
  `$aka->game->getKey()` and `->game_name` for the changelog, then redirects to
  `$aka->game`.

Deleting the relation would have returned a 500 from the admin autocomplete for
every AKA row and broken the AKA-delete redirect. The error came from running
the caller search through `| head`, which truncated the output at ten lines and
hid every `$aka->game` hit behind the `$game->akas` ones. The primary-key plan
records that "a search that finds nothing looks exactly like a search that ran
nothing"; this is its sibling — **a search that was truncated looks exactly like
a search that was complete.**

So this one is a genuine conversion, and it is covered: `tests/e2e/admin/games.spec.js:156-178`
exercises the endpoint and asserts the AKA row comes back with its developer
label and ranks ahead of the game. That spec is the net for the `hasOne` →
`belongsTo` change, and it should be run specifically, not just as part of a
full pass.

**`reviewScreenshots()` really is unused** — an unrestricted search finds only
its own declaration — so that one is still a deletion, and it removes a latent
bug rather than converting one.

## The risk model, and why it is not the previous campaign's

The primary-key campaign's characteristic failure was a **read**: a page
rendering 200 with a hole in it, because `preventAccessingMissingAttributes()`
logs rather than throws in production. This campaign inherits that, but its
characteristic failure is a **write**, and a write is not recoverable by
redeploying.

`AppServiceProvider.php:93` enables `preventSilentlyDiscardingAttributes()`
**outside production only**, and says why: *"a failed save is riskier to
surface on the live site."* That is a defensible choice for normal operation
and precisely the wrong one during a foreign key rename. After a column moves
and `$fillable` moves with it, a caller still passing the old key finds it
non-fillable, and in production Eloquent drops it and writes the row anyway.

### Measured, on MariaDB 10.11.18

A throwaway table modelled on the real shapes — one nullable foreign key like
`interview_main.ind_id`, one `NOT NULL` like `game_release_scan.game_release_id`,
both with live constraints — with the model's `$fillable` already moved to the
new names:

```
==== PRODUCTION (preventSilentlyDiscardingAttributes OFF) ====

[1] caller still passes the OLD key for the NULLABLE fk
    create({"ind_id":7,"release_id":7,"label":"stale-nullable"})
    INSERTED  individual_id=NULL  release_id=7
    ^^ SILENT. Row written, foreign key gone, no exception, nothing logged.

[2] caller still passes the OLD key for the NOT NULL fk
    create({"individual_id":7,"game_release_id":7,"label":"stale-notnull"})
    THREW  QueryException: SQLSTATE[HY000] 1364 Field 'release_id' doesn't have a default value
    ^^ LOUD. NOT NULL is the backstop, in production too.

==== DEV / TEST (guard ON) ====

[3] the same stale nullable key as [1]
    THREW  MassAssignmentException: Add fillable property [ind_id] ...
```

### What that narrows the danger to

The silent class is smaller than it first looks, and every boundary was checked
rather than assumed:

- **`NOT NULL` foreign keys fail loudly in production** (1364). That covers all
  eight `game_release_id` pivots, `individual_nicks.nick_id` and
  `trainer_option_id`.
- **`NOT NULL DEFAULT 0` fails loudly too**, via the foreign key constraint
  (1452) rather than the null check — `article_user_comments.comments_id` is
  the only column of this shape.
- **Direct assignment is loud everywhere.** `$model->old_column = x; save()`
  puts the unknown column into the `INSERT` and MariaDB rejects it with 1054.
  Mass assignment is the only path that discards silently.
- **Every write that goes through a relationship is safe.** `attach()`,
  `sync()`, `save()` and `saveMany()` called on a relation, and `associate()`,
  all take the key name from the relationship object — so it moves with the
  relationship definition by construction, and no array key can go stale.
  `game_developer.dev_pub_id` and `game_genre_cross.game_genre_id` are
  **nullable**, not `NOT NULL` as their pivot shape suggests, so it is genuinely
  the write path that makes them safe and not the column definition.

That last point is what closes the analysis, because it is exhaustive. The only
mechanism that can drop a foreign key silently is **a bare array key in
`create()`, `new Model([...])`, `fill()` or `update([...])`, against a model
whose `$fillable` names the column.** Checked against every nullable non-pivot
candidate:

| Column | How it is written | Verdict |
|---|---|---|
| `interview_main.ind_id` | `new Interview([...])`, `$fillable` names it | **the one silent site** |
| `individual_text.ind_id` | `$individual->text()->save($text)` | safe — relationship save |
| `pub_dev_text.pub_dev_id` | `$company->text()->save($text)` | safe — relationship save |
| `menu_disk_contents.game_release_id` | `$release->menuDiskContents()->save($content)` | safe — relationship save |
| `game_release.pub_dev_id` | not in `$fillable` | safe |

`MenuDiskContent::create()` at `MenuDisksContentController:84` and
`MenuImport:900` is worth looking at directly, because it is the shape that
looks dangerous and is not: both build the row *without* the release and then
link it through the relation.

Which reduces the entire silent class to a single sentence: **mass assignment
into a nullable foreign key column, in production.** There are five non-pivot
nullable candidates — `individual_text.ind_id`, `interview_main.ind_id`,
`game_release.pub_dev_id`, `pub_dev_text.pub_dev_id`,
`menu_disk_contents.game_release_id` — and of those, exactly one is in a
`$fillable` today:

```php
// app/Models/Interview.php:18
protected $fillable = ['user_id', 'ind_id', 'draft'];

// app/Http/Controllers/Admin/Interviews/InterviewsController.php:62
$interview = new Interview([
    'user_id' => $request->author,
    'ind_id'  => $request->individual,   // <-- the one
    'draft'   => $request->draft ? true : false,
]);
```

**That line is the whole silent risk of this campaign.** If `interview_main.ind_id`
becomes `individual_id` and this line is missed, production creates interviews
with no individual attached, with nothing in the log, while dev and CI throw.
`InterviewsController::update()` does not touch the column (the individual is
fixed at creation, and the Blade field is inside `@if(!isset($interview))`), so
`store()` is the only write.

Two consequences for the plan:

- **The `ind_id` rename ships alone**, and its PR checklist names that line
  explicitly rather than relying on a grep.
- **Do *not* enable `preventSilentlyDiscardingAttributes()` in production.**
  An earlier draft floated it and left it open. Measured, it is the wrong
  instrument: the flag is global and would newly police **107 mass-assignment
  call sites across 51 files** in production, in order to protect against a
  risk that exists at **one** of them. Every latent stale key anywhere in the
  application — in paths that today work fine by dropping something harmless —
  would become a 500 instead. The suite being green with the flag on says
  nothing about the untested paths, which is precisely where such a key would
  survive. That is a large, permanent-feeling blast radius bought for a
  one-line problem, and `AppServiceProvider`'s existing comment is right on its
  own terms.

### Make the one silent site loud instead

The targeted fix is better than the global one, and it is a schema improvement
that stands up without the campaign.

`interview_main.ind_id` is nullable, which is the *only* reason the campaign has
a silent class at all. It is also nullable for no reason: **80 interviews in
production, zero with a null `ind_id`**, and the application already treats it
as mandatory — `InterviewsController:57` validates
`'individual' => 'required|exists:individuals,id'`. The schema simply never
caught up with the form.

Make it `NOT NULL`, in its own migration, **before** the `ind_id` rename:

- A stale `'ind_id'` key in `new Interview([...])` then fails with 1364 in
  production, exactly like the nine `NOT NULL` columns already do. The silent
  class stops existing rather than being watched for.
- It is compatible with the existing constraint: `interview_main_ind_id_foreign`
  is `ON DELETE CASCADE`, so deleting an individual removes the interview rather
  than trying to null the column. (Its sibling `interview_main_user_id_foreign`
  is `ON DELETE SET NULL` — that one could *not* take `NOT NULL`, which is why
  the delete rule has to be read before proposing this anywhere else.)
- It is correct regardless of whether Phase C ever happens.

The other four nullable non-pivot columns are deliberately left alone. Two of
them genuinely hold nulls in production — `game_release.pub_dev_id` has 11,815
and `menu_disk_contents.game_release_id` has 5,195 — so nullable is right there,
and both are already safe because they are written through relationships.

The one condition that would change this recommendation: if the campaign had
many silent sites rather than one, the per-column fix would not scale and the
global flag would start to look proportionate. It has one.

## Migration mechanics, verified

Renaming a **child**-side foreign key column is not the operation the
primary-key campaign verified (it renamed parent keys with inbound constraints).
Checked separately, on a scratch database built from the production DDL of
`interview_main`, with rows present:

- Laravel emits one statement:
  `alter table `interview_main` rename column `ind_id` to `individual_id``.
- The constraint stays live: an orphan insert is still rejected with 1452.
- Rolling the rename back is symmetric and lossless.
- On **SQLite**, which PHPUnit runs on, `renameColumn` rewrites the `foreign key`
  clause in the table definition automatically, the constraint still rejects
  orphans, and row data is preserved.

### The trap: constraint and index names do not follow the column

After `renameColumn`, MariaDB leaves both named for the old column:

```
KEY `interview_main_ind_id_foreign` (`individual_id`),
CONSTRAINT `interview_main_ind_id_foreign` FOREIGN KEY (`individual_id`) ...
```

That is cosmetic until some later migration writes
`$table->dropForeign(['individual_id'])`. Laravel derives the constraint name
from the column, looks for `interview_main_individual_id_foreign`, and fails:

```
SQLSTATE[42000]: 1091 Can't DROP FOREIGN KEY `interview_main_individual_id_foreign`
```

Measured, not predicted. The repository already holds 242 `->foreign()` calls
and several `*_constraints.php` migrations, so a future `dropForeign` on a
renamed column is a question of when.

The fix, verified with data in the table and `foreign_key_checks` on. MariaDB
10.11 can rename an index in place but not a constraint, so the constraint is
dropped and re-added:

```php
Schema::table('interview_main', fn (Blueprint $t) => $t->renameColumn('ind_id', 'individual_id'));

DB::statement('ALTER TABLE `interview_main`
    RENAME KEY `interview_main_ind_id_foreign` TO `interview_main_individual_id_foreign`');
DB::statement('ALTER TABLE `interview_main` DROP FOREIGN KEY `interview_main_ind_id_foreign`');
DB::statement('ALTER TABLE `interview_main` ADD CONSTRAINT `interview_main_individual_id_foreign`
    FOREIGN KEY (`individual_id`) REFERENCES `individuals` (`id`) ON DELETE CASCADE');
```

After which `dropForeign(['individual_id'])` succeeds.

**But that block is only correct for `interview_main`, and copying it is a
trap.** Raised by OpenCode, then queried across all 16 renameable foreign key
columns via `information_schema.STATISTICS` and `KEY_COLUMN_USAGE`. The two
naming schemes are **asymmetric**:

| | Laravel-named `<table>_<col>_foreign` | Named after the column |
|---|---|---|
| Constraints | **16 of 16** | 0 |
| Indexes | 6 of 16 | **10 of 16** |

So the constraint name can be derived. **The index name cannot.** The ten plain
ones are `game_developer.dev_pub_id`, `game_genre_cross.game_cat_id`, and an
index literally called `game_release_id` on all eight `game_release_*` tables —
so `RENAME KEY game_release_aka_game_release_id_foreign` fails on eight of the
nine tables in the largest rename. Read the index name out of
`information_schema.STATISTICS` per table; never derive it:

```sql
SELECT TABLE_NAME, INDEX_NAME, COLUMN_NAME FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = 'game_release_id';
```

(No renameable column carries more than one index, which is the one thing here
that is simple.)

One distinction worth drawing, because it changes how urgent this is. For the
six `*_foreign` indexes, the rename *introduces* the 1091 trap. For the other
ten, `dropIndex(['release_id'])` is **already** broken today — Laravel derives
`<table>_<cols>_index` and the index is not named that either way — so renaming
them is tidiness rather than a regression fix. Do it anyway: an index named
`game_release_id` sitting on a column called `release_id` is precisely how
`game_genre_cross` ended up with an index called `game_cat_id`, on a column
that has not been called `game_cat_id` for years. That name is the empirical
proof that this drift is real and that nobody goes back for it.

### And PHPUnit is blind to half of it

The raw `RENAME KEY` / `DROP FOREIGN KEY` block has to be guarded
`!== 'sqlite'`, so on the driver PHPUnit runs it simply does not execute. The
question is whether the suite would still catch a later migration walking into
the 1091 trap. Measured on SQLite, same shapes:

| A later migration does… | SQLite (PHPUnit) | MariaDB (deploy) |
|---|---|---|
| `dropForeign(['individual_id'])` | **succeeds** | **fails, 1091** |
| `dropIndex(['individual_id'])` | fails, no such index | fails, 1091 |

So the two halves behave differently, and it is the more dangerous half that is
invisible. SQLite's foreign keys are unnamed and inline, and Laravel implements
`dropForeign` there by rebuilding the table — so it does not care what the
constraint is called and passes regardless. The index half is caught, because
SQLite's index name drifts exactly like MariaDB's (`zc_ind_id_index` survives
the column rename).

**A future `dropForeign` on a renamed column is therefore green in CI and broken
on deploy.** That is precisely the class the primary-key campaign's harness
change 3 exists for — the MariaDB `migrate:fresh` + `migrate:rollback --step=1`
job — and it is the argument for keeping that gate rather than quietly letting
it rot once this campaign ends. It is also the second reason to rename the
constraints properly now rather than leaving the drift: the cheapest fix is the
one that stops the trap existing.

Three further notes:

- The `ON DELETE` clause must be copied from `SHOW CREATE TABLE`, not assumed.
  `interview_main_ind_id_foreign` is `ON DELETE CASCADE`; its sibling
  `interview_main_user_id_foreign` is `ON DELETE SET NULL`. Getting this wrong
  changes what happens when an individual is deleted, and no test that never
  deletes an individual will notice.
- The raw statements must be guarded with
  `if (DB::connection()->getDriverName() !== 'sqlite')` — **never `=== 'mysql'`**,
  which silently no-ops here because the driver is `mariadb`. The primary-key
  plan records how that once lost a FULLTEXT index.
- Re-adding a constraint is not metadata-only the way the column rename is; it
  validates. On `game_release_scan`-sized tables that is still fast, but it is
  the reason to keep one column family per migration.

### The migration, written out and round-tripped

The plan kept saying "write the `down()` and test it" without showing one, and
the `down()` is where this gets fiddly: the constraint has to come back with its
original name *and* its original `ON DELETE`, and the statements have to run in
the opposite order. Verified on a scratch copy of the real `interview_main` DDL
with rows present — after `up()` then `down()`, `SHOW CREATE TABLE` is
**byte-identical to the original**.

```php
public function up(): void
{
    Schema::table('interview_main', fn (Blueprint $t) => $t->renameColumn('ind_id', 'individual_id'));

    if (DB::connection()->getDriverName() === 'sqlite') {
        return;                       // sqlite rewrites the fk clause itself
    }

    DB::statement('ALTER TABLE `interview_main`
        RENAME KEY `interview_main_ind_id_foreign` TO `interview_main_individual_id_foreign`');
    DB::statement('ALTER TABLE `interview_main` DROP FOREIGN KEY `interview_main_ind_id_foreign`');
    DB::statement('ALTER TABLE `interview_main` ADD CONSTRAINT `interview_main_individual_id_foreign`
        FOREIGN KEY (`individual_id`) REFERENCES `individuals` (`id`) ON DELETE CASCADE');
}

public function down(): void
{
    if (DB::connection()->getDriverName() !== 'sqlite') {
        DB::statement('ALTER TABLE `interview_main` DROP FOREIGN KEY `interview_main_individual_id_foreign`');
        DB::statement('ALTER TABLE `interview_main`
            RENAME KEY `interview_main_individual_id_foreign` TO `interview_main_ind_id_foreign`');
    }

    Schema::table('interview_main', fn (Blueprint $t) => $t->renameColumn('individual_id', 'ind_id'));

    if (DB::connection()->getDriverName() !== 'sqlite') {
        DB::statement('ALTER TABLE `interview_main` ADD CONSTRAINT `interview_main_ind_id_foreign`
            FOREIGN KEY (`ind_id`) REFERENCES `individuals` (`id`) ON DELETE CASCADE');
    }
}
```

Note the asymmetry: `up()` renames the key *before* dropping the constraint,
`down()` drops the constraint *before* renaming the key back. Both orders are
forced — MariaDB will not rename a key out from under a live constraint that
depends on it in one direction, and will not leave the index name colliding in
the other.

Three things in that template have to be re-derived per table rather than
copied: the index name (`information_schema.STATISTICS`), the `ON DELETE`
clause (`SHOW CREATE TABLE`), and the referenced table. Only the shape carries
over.

Everything else follows the primary-key plan unchanged: append a new migration
rather than editing historical ones, one rename per migration because no
migration here runs in a transaction, and **write and test the `down()`** —
the revert commit deletes the migration file, so `migrate:rollback --step=1`
has to run over SSH *before* the revert is pushed.

## Verification per PR

Steps 1-3 and 6 are the primary-key plan's, unchanged. What differs:

1. `artisan test`, then `npx playwright test`. Playwright matters more here than
   the token counts suggest: **`grep` for these column names in `tests/e2e/`
   returns zero.** The e2e suite never names a foreign key — it drives the UI —
   so it is the only part of the harness that is naturally immune to a
   find-and-replace and can still tell you the feature broke.
2. **Exercise the write, not just the read.** The failure this campaign risks is
   an incomplete `INSERT`, which a page render cannot detect. For each renamed
   column, save the form that writes it and assert the association came back by
   id.
3. A MariaDB `migrate:fresh` and a `migrate:rollback --step=1`, per harness
   change 3 of the previous campaign.
4. Re-run the relationship audit script and confirm the divergence count moved
   by exactly the number of relations the PR claims. This is the campaign's
   progress metric and it is cheap to check.

## What the test suite must gain first

Audited jointly with OpenCode; its findings are folded in below rather than
kept in a separate document. The constraint agreed with nicolas up front:
**no test is to be written to test renaming.** Anything added must be a
functional test of a feature that a rename would break, and must still earn its
place with the renames deleted.

**The one silent site is already covered.** An earlier draft of this plan said
nothing guarded the interview write end to end. That was wrong, and OpenCode
caught it: `tests/Feature/Admin/Interviews/InterviewsControllerTest.php:59-70`
posts `individual` to the real route and then asserts

```php
$this->assertSame($individual->getKey(), $interview->ind_id);
```

which is exactly the by-id write assertion the risk section asks for. Line 70 is
a line to *update* during the `ind_id` rename, not a gap to fill. Worth being
precise about why it works, because it is the campaign's only real net: in the
test environment `preventSilentlyDiscardingAttributes()` is on, so a stale
`'ind_id'` key against a moved `$fillable` throws rather than being dropped —
and if `$fillable` were left alone instead, the column would be gone and
MariaDB would raise 1054. Both halves of the mistake are caught. Only production
is blind, which is the argument for the guard change proposed above.

What is genuinely missing:

- **Nobody posts `distributors`.** `GameReleaseController:191` validates it and
  `:255-259` detaches and re-saves it, and no test in the suite sends the field
  — while `locations`, its sibling three lines away, is covered at
  `GameReleaseControllerTest:134`. Worse, that file's docblock at line 19 lists
  distributors among the lists it covers. A docblock that overstates coverage is
  more dangerous than no docblock, because it stops the next person looking.
  `game_release_distributor` carries **two** columns in this campaign
  (`game_release_id` and `pub_dev_id`), so it is the single pivot most in need
  of a write test. The natural home is
  `GameReleaseControllerTest::test_locations_crews_and_languages_are_attached()`
  at `:126` — extend it and rename it, so the test name stops disagreeing with
  the file's own docblock. Frame it as covering an untested feature rather than
  as guarding a rename: `:258` links them with `saveMany()` through the relation,
  so like every other relationship write it is safe by construction. The test
  is worth writing because nothing exercises the field at all, not because the
  rename endangers it.
- **Nothing new for the two defects, but one existing spec gets promoted.**
  `reviewScreenshots()` is deleted, so it owes no test. `GameAka::game()` is
  converted, and `tests/e2e/admin/games.spec.js:156-178` already covers the
  admin autocomplete path that reads `$aka->game` — run it deliberately as the
  gate on that change rather than trusting a whole-suite pass.
- **The statistics figures**, narrowly. `Tops.php` and
  `AdminStatisticsHelper::topPublishers()/topDevelopers()` join `pub_dev` in raw
  SQL and nothing asserts their output. The exposure is *not* a missed SQL
  error: `<x-cards.tops />` renders on `games/index.blade.php` and
  `tests/e2e/admin/others.spec.js:36-47` loads `/admin/others/statistics` and
  asserts charts draw, so a stale column name surfaces as a failing spec — the
  primary-key campaign's precedent is Playwright catching that same Tops card
  return a 500. Every `dev_pub_id` reference is table-qualified already
  (`Tops.php:29`, `AdminStatisticsHelper:342`), and the one unqualified site,
  `GameCreditsController:106`, is a single-table query with no join. What is
  uncovered is a silently *wrong figure*, which `tests/e2e/README.md:350`
  already records as a known gap.

The previous campaign's harness change 1 — distinct id ranges per table — is
already in place and is load-bearing here for the same reason: a foreign key
read from the wrong column returns a plausible number unless the ranges differ.

## Deploying

No different from a primary-key rename, and the primary-key plan's section
applies verbatim: `deploy.sh` already takes the site down unconditionally for
the whole deploy, renames ship on their own, a dump is taken immediately before
the migration, and reverting is `migrate:rollback --step=1` over SSH *first* and
the revert commit second.

The one addition: because the failure mode here is a write rather than a read,
**check the affected table for rows written during the window** after any rename
deploy that had to be rolled back — `SELECT COUNT(*) FROM interview_main WHERE
individual_id IS NULL` and its equivalents. A read-side bug leaves no trace; this
one does, and it is repairable only if somebody looks.

## Reproducing the audit

The relationship audit is ~40 lines and should live in the repository rather
than in a scratch file, because Phase A's progress is measured by it and Phase
C's PRs are verified against it. Suggested home:
`artisan al:audit-relationship-keys`, printing the three counts, the pivot-table
snapshot and the
divergence table. That also makes it a candidate arch test later — "the number
of divergent relations must not increase" is the same shape as
`QueryConventionsTest` and `MigrationModelsTest`, both of which came out of the
previous campaign and are the parts of it still doing work.
