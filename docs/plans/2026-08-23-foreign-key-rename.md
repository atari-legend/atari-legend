# Renaming the foreign keys

Successor to `2026-08-17-primary-key-rename.md`, which closed with four foreign
key inconsistencies "recorded as follow-up, not done here". This plan assesses
that follow-up. It is written to be read alongside the primary-key plan, and it
does not repeat what that document already establishes about deploy ordering,
`renameColumn` on MariaDB, or the strictness guards in `AppServiceProvider`.

**Status: proposal. Nothing here has been implemented.** Every number and every
behaviour claim below was measured against this repository and a local MariaDB
10.11.18 on 2026-08-23; the commands are named so they can be re-run.

Independently reviewed on 2026-08-23 by OpenCode, which re-ran the audit,
re-queried the schema and re-tested the migration template from scratch on both
drivers. Almost everything reproduced; the four things that did not are
corrected in place below, and each correction says so where it lands rather than
being collected here. The largest was that this document's own account of *why*
`Release` → `GameRelease` is a prerequisite misapplied the `belongsTo`
derivation rule it states correctly elsewhere. That section is rewritten, and
the campaign now has an explicit order.

A second review, same day, re-ran the audit and every grep and found no factual
error in the measurements. It did find four places where this document
contradicted itself, all fixed below where they occur. One was not
merely presentational: the summary table split the 48 divergent relations
20 / 20 between Phases C and D while the sections split them 18 / 22, and the
sections were right. Its one disputed finding is also recorded where it lands —
it read the mass-assignment count as overstated, on a grep that missed the
static `Model::create([` form; the original figure was close and now carries the
command that reproduces it.

## Decisions taken

All settled with nicolas on 2026-08-23. Recorded here because three of them
changed what the phases contain.

| # | Question | Decision |
|---|---|---|
| 1 | Does "FK = table + `_id`" mean the singularised table name? | **Yes, singularised.** `users` → `user_id`, not `users_id`. |
| 2 | The `*_main` tables (16 keys) | **Out of scope — deferred.** See below; the tables are to be merged, so renaming their foreign keys now is work thrown away. |
| 3 | Phase B: columns or methods? | **Rename the methods, leave the columns.** They are already table-correct; the code bends to the schema. |
| 4 | Rename `Release` → `GameRelease`? | **Yes, and it lands before Phase A**, not merely before Phase C — see the ordering under "Why that model rename is a prerequisite". |

### Why the `*_main` group is deferred rather than decided

The immediate reason is nicolas's: `article_main` and `article_text` should not
be two tables at all. *"There's no need to have separate tables for the content,
it could be a single table that contains all the columns of the two."* Renaming
`article_id` → `article_main_id` across 16 foreign keys, only to merge the
tables afterwards, is work done twice and discarded once.

There was also a cost I had understated when this was first put to nicolas. I
called applying the rule here "safe", which was true but only about silent
writes — I had not checked the code side. **21 relationship key positions**
depend on `article_id`, `interview_id`, `review_id` or `screenshot_id`, and
every one of them is currently Eloquent's default, so all 21 hit the same
Phase A/C conflict as the `Release` group. Applying the rule would have meant
either four further model renames (`Article` → `ArticleMain`, and so on, baking
"main" into class names that are currently clean) or permanently adding 21
explicit arguments.

### Reconnaissance for that merge, since it is now on the horizon

Not a plan — just the measurements someone will want on day one, taken while
the question was live:

| Pair | Parent rows | Child rows | Distinct parents in child | Merges? |
|---|---|---|---|---|
| `article_main` / `article_text` | 5 | 5 | 5 | **strict 1:1** |
| `interview_main` / `interview_text` | 80 | 80 | 80 | **strict 1:1** |
| `individuals` / `individual_text` | 5,405 | 4,528 | — | 1:0..1 — merges with nullable columns |
| `pub_dev` / `pub_dev_text` | 1,387 | 1,185 | — | 1:0..1 — merges with nullable columns |
| `review_main` / `review_score` | 126 | 126 | 126 | **1:1 — merges, with a caveat** |

So the two nicolas named are the clean cases, and both are strictly 1:1 despite
`Article::texts()` and `Interview::texts()` being declared `hasMany`. The
`individuals` and `pub_dev` pairs merge too, at the cost of nullable columns for
the ~15% of rows with no text.

**`review_score` was wrong in an earlier draft of this table and is corrected
here.** It said 89 parents to 126 children and called the pair "genuinely
1:many". Re-measured 2026-08-23: `review_main` has 126 rows, `review_score` has
126, there are 126 distinct `review_id` values, and the maximum number of score
rows for any one review is **one**. The model agrees — `Review::score()` is a
`hasOne`, not a `hasMany` — and so do the write paths, though not in the way an
earlier draft of this paragraph said. It claimed "both write paths reuse the
existing row"; corrected after OpenCode checked them. There are three, and only
one reuses: `ReviewsController:110` (admin update) is
`$review->score ?? new ReviewScore()`, while `ReviewsController:74` (admin
create) and `ReviewController:106` (public submission) each build a `new
ReviewScore()` — but each does so alongside a brand-new `Review`, so neither
adds a *second* score to an existing one. The 1:1 conclusion is what survives,
and the data agrees with it: max one score row per review. So it merges too: four `NOT NULL` integer columns
(`review_graphics`, `review_sound`, `review_gameplay`, `review_overall`) fold
straight onto `review_main` with no nullability change, because every review has
a score.

The caveat is that nothing in the *schema* enforces the 1:1 — `review_score`
has its own surrogate `id` and a plain `KEY` on `review_id`, not a unique one —
so the merge has to assert one row per review at migration time rather than
assume it. That is a smaller obstacle than the row counts in the old table
suggested, and it means the merge campaign has three clean pairs to consider,
not two.

Worth noting for whoever picks that up: the primary-key campaign found the
`article_main` / `article_text` pair impossible to test properly *because* their
ids move in lockstep — see "What the suites cannot see" in that plan. Merging
them removes that blind spot permanently, which is a second argument for it.

Be precise about whose lockstep it is, because the two pairs are not in the same
position. The lockstep is a property of the **fixtures**, and it applies to every
pair: `ArticleFactory::configure()` creates exactly one `ArticleText` per
`Article`, so the two id sequences advance together and a wrong-key hydration
returns the right number anyway. In **production** the pairs have diverged —
`article_main`/`article_text` are still 1-5 in lockstep, but interviews have
drifted (interview 85's text row is 84), which is exactly why the primary-key
campaign could prove the interview hydration correct against live data and could
not prove the article one. So merging fixes the fixture blind spot for whichever
pair is merged; it is not a statement that interviews are untestable today.

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
| Explicit key argument that is **already** Eloquent's default | 78 | A | No — delete the argument |
| No explicit key argument (already clean) | 33 | — | No |
| Divergent, fixed by renaming a **PHP method** | 5 | B | No |
| Divergent, fixed by renaming a **column** (or by the model rename Phase C rests on) | 18 | C | Yes |
| Divergent because the relation is **wrong** (one unused, one live) | 2 | A | No — delete one, convert one |
| Divergent, convention **unreachable or declined** | 22 | D | No — keep the argument, record why |
| Divergent, **deferred** (`News::image()`) | 1 | — | No |
| **Total relations in `app/Models/`** | **159** | | |

The 48 divergent relations are the last five rows. Three notes on the edges:

- The 22 declined include the three `type()` relations, which Phase B could
  reach by renaming a method and deliberately does not — `->type` is 97 hits
  repo-wide and mostly plain columns. They also include
  `MenuDiskContent::release()` and `ReleaseAka::release()`, which an earlier
  draft counted under Phase C. Neither belongs there:
  `menu_disk_contents.game_release_id` and `game_release_aka.game_release_id`
  are already table-correct and do not move, and `belongsTo` derives from the
  **method** name, so the `Release` → `GameRelease` rename does not reach them
  either. Only renaming the method to `gameRelease()` would, and that is
  declined on pricing in Phase D. The rows read 18 / 22 for that reason, not
  20 / 20; second review 2026-08-23 caught that the sections and the table
  disagreed.
- One of the 18 — `Release::distributors()`, on `game_release_distributor` — is
  only half fixed by its column rename: `game_release_id` moves and `pub_dev_id`
  does not, so it ends the campaign with one explicit argument rather than none.
- The campaign also *adds* one argument that does not exist today.
  `Media::release()` is in the 33 clean, and only because `release_id` happens to
  be what its method name derives; Phase C renames that column and it gains a
  permanent `'game_release_id'`. Counted where it lands, not where it starts —
  see "The two hand-edits option 1 still needs".

The column-rename relations resolve to **six renames covering 18 columns
across 18 tables**, listed in Phase C (that this is also 18 relations is a
coincidence — the two sets are not in correspondence). So the campaign is:
delete 78 arguments,
delete one dead relation and convert one broken one, rename 5 methods, rename
18 columns (with 16 more deferred alongside the `*_main` merge), and
write down why the rest stay as they are. The first
item is the largest by a distance, carries no database risk at all, and can
start today.

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

**78 and 33 are corrected figures; the script said 76 and 35 until 2026-08-23.**
Found by OpenCode reviewing this plan, and it is the same flaw the plan warns
about two sections later from the other end. `Comment::interviews()` and
`Comment::reviews()` are spelled **`belongstoMany`** — lowercase `t` — and both
pass exactly the derived defaults, so they are redundant. But
`passesKeyArgument()` matched the relation type with a case-sensitive regex, so
it answered "no key argument passed" and filed both under *already clean*. The
classification, not the discovery: reflection found the relations fine. The
regex now carries an `/i` and the script reports **78 / 48 / 33**, which is what
the rest of this document uses. That the plan called this out as a hazard for
"the next tool" while it was live in this one is the second time the harness has
caught the audit rather than the code — see the `Pivot` correction in Phase A.

## Phase A — delete the redundant arguments (78 today, 79 after the model rename)

These relations pass an explicit key argument that is character-for-character
what Eloquent would have derived anyway. Deleting the argument changes no SQL.
There is no migration, no schema change, and no production risk; the only way
to get it wrong is to delete an argument that was *not* redundant, and the
script above is what says which is which.

### Phase A is not the first thing that lands

An earlier draft of this section held eight `Release` relations back by name and
said they would "ship with Phase A only if `Release` → `GameRelease` is
approved". That is wrong under **every** ordering, and OpenCode's review is what
established it. The eight pass `'release_id'`. If the model rename lands first,
Eloquent derives `game_release_id` for them and they are *divergent* at Phase A
time — not deletion candidates at all. If Phase A runs first and deletes them,
the model-rename pull request is the thing that breaks. Either way there is no
moment at which those eight are both redundant and safe to delete, and the
"hold list" was an attempt to hand-maintain a fact the audit already knows.

So the campaign has an order, and it is this:

| | Lands | Why here |
|---|---|---|
| 0 | Fix the four `belongstoMany` spellings | Purely cosmetic — PHP method names are case-insensitive, so no SQL and no behaviour change — but it is what makes the audit's counts correct, and every number below is one of those counts. |
| 1 | `Release` → `GameRelease` (58 files) | Moves nine relations into the deletable set and eight out of it, so that the audit computes Phase A's scope instead of a person doing it by hand. |
| 2 | **Phase A** | Delete whatever the audit now calls redundant. |
| 3 | Phase B, then Phase C | Method renames, then column renames. |

**Derived, not yet measured: after steps 0 and 1 the audit should read
79 / 48 / 32.** Worth predicting explicitly, because "the counts moved by
exactly the number of relations this PR claims" is the campaign's progress
metric and a rename that moves them in both directions at once is the one place
that metric is hard to read. From today's 78 / 48 / 33:

- the eight `Release` relations passing `'release_id'` go **redundant →
  divergent** (`memoryEnhanced`, `memoryMinimums`, `memoryIncompatibles`,
  `emulatorIncompatibles`, `tosIncompatibles`, `copyProtections`,
  `diskProtections`, `languages`);
- **nine** go **divergent → redundant** — the eight `Release` relations that
  already pass `game_release_id` (`boxscans`, `systemEnhanced`, `akas`,
  `menuDiskContents`, `crews`, `locations`, `resolutions`,
  `systemIncompatibles`) and `Crew::releases()`, whose *related*-side key
  converges;
- `Release::medias()` goes **clean → divergent**, because the rename PR gives it
  a temporary explicit `'release_id'` — see "Why that model rename is a
  prerequisite", where that argument is the whole subject.

Net: redundant 78 − 8 + 9 = **79**, divergent 48 + 8 − 9 + 1 = **48**, clean
33 − 1 = **32**. `Release::distributors()` and `Release::trainers()` stay
divergent (their second keys do not converge), and `ReleaseAka::release()`,
`MenuDiskContent::release()` and `Media::release()` are unaffected by a class
rename because `belongsTo` reads the method name. Re-run the audit either side
of the rename PR and check it against this paragraph rather than against a bare
"unchanged".

Representative shapes, all already conventional today:

```php
$this->belongsTo(User::class, 'user_id')                      // Article::user()
$this->hasMany(Release::class, 'game_id')                     // Game::releases()
$this->belongsToMany(Memory::class, 'game_release_memory_minimum', 'release_id', 'memory_id')
```

The 78 are spread over 27 models; `Game` contributes 17, `User` 9 and `Release`
9. Of `Release`'s nine, only `Release::game()` — which passes `'game_id'`, a
column Phase C does not touch — is in the same position after the model rename
as before it; the other eight are the ones that move out of the set. The full
list is reproducible from the audit script and is not reproduced here, because
a list in a document goes stale and the script does not.

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

Settled with OpenCode. Seventy-odd deletions across nearly thirty models is not
reviewable by eye,
and that is the argument *for* keeping it whole rather than against: it is
reviewable **by diff** — the pivot snapshot must be unchanged, the generated SQL
must be unchanged, and both suites must be green. Splitting it per model would
multiply CI runs without adding a single bit of signal, and would make the SQL
diff — the only check that actually proves the claim — into thirty partial
diffs that each prove less.

The declarations a line-oriented rewriter cannot touch (multi-line,
`->withPivot()`, `->using()`) are the exception worth calling out in the pull
request text, so a reviewer knows which handful to read closely rather than
skimming all seventy-odd equally. There are **seven** of them in the redundant
set — `Article::screenshots()`, `Game::individuals()`, `Individual::games()`,
`Interview::screenshots()`, `Review::screenshots()` (all `->withPivot()` plus
`->using()`) and `Release::copyProtections()` and `Release::diskProtections()`
(`->withPivot('notes')`, both with the call split across two lines). An earlier
draft said six; re-counted 2026-08-23 by OpenCode and confirmed against the
audit's redundant list. Two of the seven are `Release` relations that leave the
set at the model rename and come back in Phase C, so the count Phase A actually
carries is **five**. Three more — `Comment::games()`, `Game::releases()`,
`GameSubmitInfo::user()` — are wrapped or preceded by a comment but are single
statements, and any rewriter handles them.

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
— instead of asking Laravel. It now calls `$model->getForeignKey()`, which took
the counts from 79 / 45 to **76 redundant, 48 divergent, 35 clean**. A second
correction — the case-sensitive relation-type regex, described under "How these
numbers were obtained" — has since taken them to **78 / 48 / 33**, which is the
figure the rest of this document uses.

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
  character changed. Note that this experiment ran *before* the direction change
  and so deleted a **superset** of what Phase A now ships — it included the eight
  `Release` arguments that the model rename now moves out of the set. That does
  not invalidate it: if removing 70 was a no-op, removing the subset that remains
  in scope is a no-op too. It does mean the measurement should be repeated on the
  final set before the pull request, not cited as though it had tested it.
- **`artisan test`: 991 passed, 18 skipped, 3511 assertions** — identical to the
  figure the primary-key campaign signed off on.

("162 relations", here and in the pivot experiment, is the audit's 159 plus the
three `MorphMany` relations the audit skips — `User::notifications()`,
`::readNotifications()` and `::unreadNotifications()`, inherited from
`Notifiable`. A morph relation has no foreign key to converge on, so it has no
place in the three counts, but it does emit SQL and so belongs in the diff.)

So "Phase A is a no-op" is now a measurement rather than a claim. Three caveats
that belong with it rather than after it:

- **The rewrite script that produced it is not in the repository.** Only the
  audit script is. Raised by OpenCode, and it is a fair hit: this plan's own
  argument against lists in documents — "a list goes stale and the script does
  not" — applies to the campaign's central no-op claim resting on a tool nobody
  else can run. **Commit the rewriter next to the audit script before Phase A
  opens**, so the 70 is reproducible rather than reported. Until it is, treat
  "70 deleted, diff empty" as evidence that the *method* works, which the SQL
  diff on the actual pull request will re-establish from scratch anyway.
- **"70 of the 76" is not reproducible for the same reason, and the residue is
  seven, not six.** Static analysis of the redundant declarations finds seven a
  line-oriented rewriter cannot safely touch, enumerated under "One pull request,
  not thirty" — five of them in Phase A's scope once the model rename has landed.
  Phase A is therefore roughly seventy mechanical edits plus five careful ones,
  not one clean sweep. Nor does the residue arithmetic close: 70 deleted plus
  a static residue of seven is 77 against a redundant set of 78, and 70 of the
  pre-`/i` 76 leaves six where static analysis finds seven. Both gaps come from
  the same missing tool, so neither is worth reconciling on paper. **When the
  rewriter is committed, the pull request states its own split — how many the
  script rewrote, how many were done by hand, out of how many — rather than
  inheriting the 70.**
- **An empty SQL diff and a green suite prove the *relations* are unchanged.**
  They do not prove nothing else in those 27 files was disturbed. That is what
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
`ReleaseScan`, `MediaScan` and others: it returns **97** hits
across `app/`, `resources/views/` and `tests/`, of which only a handful are the
relationship. That is precisely the "roughly 85-90% of occurrences are
something else" hazard the primary-key plan documented, and the reason that
plan insisted on judgement per site rather than token substitution.

Priced by how ambiguous the token is. Counted as **matching lines**, re-measured
2026-08-23:

```
grep -rEn -- '->method[^A-Za-z_]' app/ resources/views/ tests/ | wc -l
```

The trailing character class is what makes the number mean anything: without it
`->type` also counts `->types` and `->typeId`, and `->image` counts `->images`
and `->imageFile`.

| Method | `->method` hits repo-wide | Verdict |
|---|---|---|
| `vs` | 5 | Safe — do it |
| `series` | 7 | Safe — do it |
| `donatedBy` | 10 | Safe — do it |
| `role` | 16 | Safe with care — two models |
| `image` | 27 | Ambiguous — **defer** |
| `type` | 97 | Ambiguous — **keep the explicit argument** |

Earlier drafts quoted **105** for `type` and **28** for `image`. Neither
reproduces under any counting method and both are corrected above; the stated
command is now part of the table so the next re-measurement can be compared to
it. For the record, counting raw *occurrences* rather than lines
(`grep -ro`) gives 107 and 40, and the bare `grep -rn -- '->image'` line count
is 36. The other four tokens are the same number however they are counted, which
is itself a signal about how unambiguous they are.

**Decided 2026-08-23: rename the methods, leave the columns.** The instruction
had been *"stick to Laravel database conventions … update the column names, not
just rename the methods"*, which conflicted with the Phase C rule from the same
message. The conflict was not a matter of taste:

- Under **FK = table + `_id`**, Phase B's columns are *already correct*.
  `article_type_id` points at `article_type`, `media_type_id` at `media_type`,
  `developer_role_id` at `developer_role`, `news_image_id` at `news_image`.
  Nothing to rename.
- Under **Eloquent's rule**, `belongsTo` derives from the *method* name, so
  convention-clean code means `type_id`, `role_id`, `image_id` — which is
  strictly *less* table-consistent.

The two instructions pointed in opposite directions. The tiebreak came from
*"the database matters more than the code"*: **leave the columns, rename the
methods** — the code bends to the schema. Confirmed by nicolas, so the table
below stands as written.

The pre-existing decision, still standing unless overruled: **do `vs`, `series`,
`donatedBy` and `role`; keep the arguments on `type()` and defer `image`.** `role` looked borderline on the hit
count alone, and what settles it is that both `role()` relations live on custom
`Pivot` models (`GameDeveloper`, `GameIndividual`) and every traversal of the
relation is `$x->pivot->role`, in **Blade only** — 12 lines across six views.
The relation has **no** call site in PHP. `GameCreditsController` was cited here
in an earlier draft and does not belong: its four `->role` hits are
`$request->role`, a form field, and they write the `individual_role_id` and
`developer_role_id` columns directly. OpenCode caught the miscitation, and it
makes the case stronger rather than weaker — the whole surface is six template
files and every one of them names the concrete pivot, so the token is fully
traceable.

Worth heading off the obvious objection, since these are two of the five
`Pivot` subclasses in the codebase and the other three are in Phase D under
*"no default exists"*: **that trap does not apply here.** `AsPivot` overrides
`getForeignKey()`, which is what `hasOne`/`hasMany` consult, so the three
`Screenshot*::comment()` relations have no derivable default and their arguments
are load-bearing. `role()` is a `belongsTo`, and `belongsTo` derives from the
**method** name via `Str::snake($method).'_'.$related->getKeyName()` —
`getForeignKey()` is never consulted. So renaming the method really does move
the derived key, on a `Pivot` exactly as on any other model. There
is also no bare `role` column anywhere; only `developer_role_id` and
`individual_role_id`. Neither of those things is true of `type`.

Three explicit arguments is a cheaper price than a 97-site token audit for no
behavioural gain.

There is no `getRouteKeyName()`, `resolveRouteBinding()` or `Route::bind()` in
the repository, so relationship method renames cannot disturb route binding.

## Phase C — the column renames

**Direction changed on nicolas's instruction (2026-08-23).** Earlier drafts of
this phase derived foreign key names from Eloquent's rule — the *model* class
name — which made `release_id` the target. The standing decision is now:

> The table name is `game_release`, so any foreign key pointing to it should be
> `game_release_id`. In general, the database matters more than the code. The DB
> schema needs to be fully consistent.

So the rule for this phase is **foreign key = singularised referenced-table name
+ `_id`**, and where that conflicts with what Eloquent would derive, the schema
wins and the code carries an explicit argument. That inverts the previous
`game_release_id` → `release_id` plan: it is the ten tables saying `release_id`
that move.

"Singularised" is an interpretation, not something the instruction said, and it
matters: applied literally the rule produces `users_id`, `individuals_id`,
`magazines_id`, `menus_id` and `sndhs_id`, because those tables are plural.
Singularising gives `user_id` and `individual_id`, which is what the schema
mostly already does. **Confirmed by nicolas (2026-08-23).**

### Where the schema stands against that rule

Measured across all 138 declared foreign keys: **100 already match, 38 differ.**
The 38 fall into three groups, and only the first is actionable today.

**Group 1 — unambiguous, 18 columns.** These are the same answer under either
rule, or are nicolas's explicit instruction.

| Rename | Tables | Note |
|---|---|---|
| `release_id` → `game_release_id` | 10 | the instructed reversal |
| `ind_id` → `individual_id` | 4 | `game_individual`, `magazine_indices` already agree |
| `comments_id` → `comment_id` | 1 (`article_user_comments`) | the other 3 comment pivots already agree |
| `dev_pub_id` → `pub_dev_id` | 1 (`game_developer`) | `game_release`, `pub_dev_text` already agree |
| `progress_system_id` → `game_progress_system_id` | 1 (`game`) | table is `game_progress_system` |
| `individual_nicks_id` → `individual_nick_id` | 1 (`crew_individual`) | plural |

The ten `release_id` tables, so that whoever executes the largest rename does
not have to re-derive them: `game_release_copy_protection`,
`game_release_disk_protection`, `game_release_emulator_incompatibility`,
`game_release_language`, `game_release_memory_enhanced`,
`game_release_memory_incompatible`, `game_release_memory_minimum`,
`game_release_tos_version_incompatibility`, `game_release_trainer_option` and
**`media`**. Nine pivots and one real table — `media` is the one that breaks the
pattern and the one a `game_release_*` glob would miss. The list is reproducible
rather than trusted:

```sql
SELECT TABLE_NAME FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL
  AND COLUMN_NAME = 'release_id';
```

**One of the eighteen points at a legacy primary key, and that is fine.**
`crew_individual.individual_nicks_id` references
`individual_nicks.individual_nicks_id` — a prefixed primary key the previous
campaign left in place, one of the 36 it recorded as follow-up. Only the
**child** column moves here: after the rename the constraint reads
`FOREIGN KEY (individual_nick_id) REFERENCES individual_nicks (individual_nicks_id)`,
which looks odd and is correct. Two things follow. The `down()` has nothing
extra to do — it renames one column back, not two. And the two campaigns do not
have to be ordered against each other: whenever `individual_nicks_id` → `id`
happens on the parent, it does not touch the child column's name, and vice
versa.

**`game_genre_id` → `genre_id` is cancelled.** It was in the previous draft
because the model is `Genre`. The table is `game_genre`, so under the new rule
the column is already correct and must not move.

**Group 2 — the `_main` tables, 16 columns, DEFERRED and out of scope.**
Decided 2026-08-23: these tables are to be merged with their `_text`
counterparts, so renaming their foreign keys first is work done twice — see
"Why the `_main` group is deferred" at the top. The analysis is kept because it
will be needed when that merge happens. Applying the
rule to `article_main`, `interview_main`, `review_main` and `screenshot_main`
renames `article_id` → `article_main_id`, `interview_id` → `interview_main_id`,
`review_id` → `review_main_id` and `screenshot_id` → `screenshot_main_id`
across 16 foreign keys. That is consistent, and it propagates a legacy table
name into sixteen further places. The inconsistency here is the *table* name,
not the foreign key.

The three options that were on the table, for the record:

- **(a)** apply the rule and accept `article_main_id`;
- **(b)** rename the tables first — `article_main` → `articles`, `review_main` →
  `reviews`, `interview_main` → `interviews`, `screenshot_main` → `screenshots`
  — after which the sixteen existing names are already correct and the renames
  vanish;
- **(c)** exempt `*_main` and record why.

**Option (a) adds no risk — checked, so the decision is about naming alone.**
All 16 were assessed against the silent-write rule: thirteen are `NOT NULL`, so
a stale key is a loud 1364. The three nullable ones —
`interview_user_comments.interview_id`, `screenshot_game_submitinfo.screenshot_id`
and `spotlight.screenshot_id` — are pivots written through `attach()`/`sync()`
or are not mass-assignable at all. The only `*_main` foreign key that appears in
a `$fillable` is `InterviewText.interview_id`, and that column is `NOT NULL`.

**Neither was taken — the merge supersedes both.** (b) had been the
recommendation, on taste rather than safety: it fixes
the cause instead of baking a legacy table name into sixteen more columns, and
"the schema must be fully consistent" argues for it harder than for (a). It is a
larger campaign and belongs in its own phase, not smuggled into this one. Since
(a) is demonstrably safe to execute, this is a judgement call and not a risk
trade-off — which is worth saying plainly, because it is the kind of decision
that gets deferred forever while people look for a technical reason to prefer
one side.

**Group 3 — four exceptions that stay.** Two are legitimate role-qualified
foreign keys: `individual_nicks.nick_id` and `menu_disks.donated_by_individual_id`
both point at `individuals`, and a table cannot hold two `individual_id`
columns. The other two are artefacts of *singularising*, not of the schema — a
naive rule wants `game_sery_id` from `game_series` and `to_id` from `tos`, and
`game_series_id` and `tos_id` are already right. Anyone scripting this rule
needs an irregular-noun exception list; without one it silently proposes
nonsense.

### Pre-flight: no name collisions

Checked before anything else, because it is the one condition that would make a
rename impossible rather than merely awkward: **no table already holds both a
target name and its current name.** Not `release_id`/`game_release_id`, not
`ind_id`/`individual_id`, not `comments_id`/`comment_id`,
`dev_pub_id`/`pub_dev_id`, or
`progress_system_id`/`game_progress_system_id`. All 18 renames are free to
proceed on that count.

### What this costs the code, and the way out

Eloquent derives foreign keys from the **model** class name. Adopting
table-based naming therefore means the explicit relationship arguments **cannot
be dropped** wherever the model and table names differ — `Release`/`game_release`
and `Genre`/`game_genre` are the live cases. The campaign's original headline
goal and full schema consistency are in direct tension, and the standing
instruction resolves it in the schema's favour.

There is a resolution that satisfies both, and it is cheap: **rename the models
to match their tables** — `Release` → `GameRelease`, `Genre` → `GameGenre`.
Eloquent then derives `game_release_id` and `game_genre_id` by itself, so the
schema gets the convention *and* the arguments still disappear.

**Size it honestly.** An earlier draft said `Release` has "7 references in
`app/` and 9 in `tests/`", which is wrong by more than an order of magnitude and
made the rename sound like a ten-minute job. Measured 2026-08-23, counting lines
where `Release` appears as a class token — `::`, a typehint, an import, a
parameter — and excluding every compound (`GameRelease`, `ReleaseAka`,
`ReleaseScan`, `ReleaseHelper` and the rest):

```
grep -rEn '(^|[^A-Za-z_])Release(::|\s*\$|\s*\||;|\)|,)' app/ tests/ database/ resources/
```

| | Lines | Files |
|---|---|---|
| `app/` | 102 | 30 |
| `tests/` | 206 | 26 |
| `database/` | 20 | 1 |
| `resources/` | 1 | 1 |
| **Total** | **329** | **58** |

The work is still mechanical — a class rename, its file, its import sites, its
factory (`ReleaseFactory` → `GameReleaseFactory`, since `HasFactory` resolves by
name) — and an IDE does most of it. But 58 files is a large diff to
land in the same campaign as a schema change, so it goes in **its own pull
request, before Phase A**, gated on the SQL diff being empty and both suites
green.

**`protected $table = 'game_release'` stays, and an earlier draft of this
paragraph was wrong to say it "becomes redundant at the same moment".** Found
while executing the rename. Eloquent derives a table name from the class with
`Str::snake(Str::pluralStudly(class_basename($this)))`, which turns
`GameRelease` into `game_releases`, not `game_release` — the singular table
name is exactly why the property is there, and the class rename does not change
that. Deleting it would have pointed the model at a table that does not exist.
The *foreign key* derivation is the one the rename fixes, because
`hasOne`/`hasMany` do not pluralise.

**It is not, however, the "pure refactor" an earlier draft called it, and its
stated gates could not have passed.** OpenCode's review is what established
this, and the correction is the subject of the next section: the rename moves
one relation's generated SQL unless the pull request also carries a temporary
explicit argument, and it necessarily moves the audit's three counts in both
directions at once. The gates are therefore: **SQL diff empty** — which is true
only with that temporary argument in place — both suites green, and the three
counts landing on **79 / 48 / 32**, the movement derived in Phase A. "The counts
must be identical" was a gate that could not pass, and a gate that cannot pass
teaches whoever executes it to ignore gates.

### Why that model rename is a prerequisite, not a nicety

Phase A and Phase C conflict without it, and **Phase A's own gates cannot see
the conflict.**

Eight relations on `Release` pass `'release_id'` today, and that is exactly what
Eloquent derives, so the audit classifies them as redundant and a Phase A that
ran first would delete them:

```
Release::memoryEnhanced()  memoryMinimums()  memoryIncompatibles()
emulatorIncompatibles()    tosIncompatibles()  copyProtections()
diskProtections()          languages()
```

Phase C then renames that column to `game_release_id`. Eloquent goes back to
deriving `release_id`, which no longer exists, and all eight break.

The dangerous part is the timing, and it is why the ordering below puts the
model rename first. Under the naive ordering the Phase A deletion genuinely *is*
a no-op at the moment it lands: the pivot snapshot is unchanged, the SQL diff is empty, the suite is 991
green. Every gate passes honestly. The failure lands one phase later, in a pull
request that never touched those lines — the same shape as the `id` collision
the primary-key campaign spent its Phase A2 defusing, arriving from a new
direction.

**And the eight are not the whole set.** An earlier draft of this section said
they were; enumerating every relation whose generated SQL names `release_id`
gives **eleven**, in three kinds:

| Kind | Count | What Phase C does to it |
|---|---|---|
| Passes `'release_id'` explicitly, redundant today | 8 | breaks *after* Phase A deletes the argument |
| Passes **no** argument, relies on the default | 2 | **breaks outright, with or without Phase A** |
| Passes `'release_id'` plus a divergent second key | 1 (`trainers()`) | needs the argument updated |

The two argument-free ones are `Release::medias()` — `hasMany(Media::class)` —
and `Media::release()` — `belongsTo(Release::class)`. Neither names a column
anywhere, and both work today only because `release_id` is what Eloquent
derives. They are invisible to any search for the token.

**And they are invisible in different ways, which is the point an earlier draft
of this section missed.** `Release::medias()` is a `hasMany`, so its key follows
the **declaring class name** — the model rename does move it.
`Media::release()` is a `belongsTo`, so its key follows the **method name** —
the model rename does nothing for it at all. Verified live: `Media::release()`
derives `release_id` whether the related class is `Release`, `GameRelease` or
anything else. That is the rule this document states three times and misapplied
once, and OpenCode's review is what caught it.

That kills one of the escape routes: **running Phase C before Phase A does not
avoid the problem**, because those two break regardless of when Phase A runs.

**The same trap exists outside the `Release` group.** Checked across all six
renamed columns rather than stopping at `release_id`: 23 relations depend on
one — 11 on `release_id`, 7 on `ind_id`, 2 on `comments_id`, 2 on
`dev_pub_id`, 1 on `progress_system_id` and **none** on
`individual_nicks_id`, which is a schema-only foreign key with no Eloquent
relation anywhere in `app/` (so Phase C renames that column and edits no model
at all) — and **three** pass no argument at all — `Media::release()`,
`Release::medias()`, and `Game::progressSystem()`, which is
`belongsTo(ProgressSystem::class)` and derives `progress_system_id`. Renaming
that column to `game_progress_system_id` breaks it with nothing in the diff to
show for it. Being a `belongsTo`, it can be fixed either by adding an argument
or by renaming the method — priced and settled under "The two hand-edits option
1 still needs" below.

Two false positives came out of that scan, and the cause is worth recording
because it will bite the next tool as well: `Comment::articles()` and
`Individual::individuals()` call **`belongstoMany`** — lowercase `t`. PHP method
names are case-insensitive so both work, but case-sensitive tooling does not see
them, and they were reported as argument-free when they are not.

**Those two are the ones this scan tripped over; there are four in the
repository.** All of them:

- `app/Models/Comment.php:39` — `articles()`, on `article_user_comments`
- `app/Models/Comment.php:45` — `interviews()`, on `interview_user_comments`
- `app/Models/Comment.php:51` — `reviews()`, on `review_user_comments`
- `app/Models/Individual.php:50` — `individuals()`, on `individual_nicks`

Only the first and the last touch a column this campaign renames, which is why
only those two showed up here; the other two are the next tool's false
positives, not this one's. The audit script's own relation-type regex is
case-sensitive too, and the flaw was live rather than hypothetical: reflection
found the relations, but `passesKeyArgument()` then classified two of the four
through a case-sensitive regex and filed them under *already clean*. That is
fixed (`/i`), and it is where 78 / 48 / 33 comes from. **Fix all four
declarations before Phase A**, as step 0 of the ordering — it is a
zero-behaviour change (PHP method names are case-insensitive) whose only purpose
is that the counts driving Phase A are the real ones.

Two ways out, then, not three — and neither is free, which an earlier draft got
wrong:

1. **Rename `Release` → `GameRelease`** before Phase A. Ten of the eleven
   converge by themselves: the eight `'release_id'` arguments become divergent
   at the rename and are deleted in Phase C once the column agrees with them
   again, `Release::medias()` derives the new name, and `trainers()` needs only
   its second key. **The eleventh, `Media::release()`, is not fixed by any class
   rename** and takes one hand-edit in Phase C. Ten of eleven automatically, one
   by hand.
2. **Hand-edit all eleven inside Phase C** and exclude the eight from Phase A
   by name. Workable, but it rests on a hand-maintained exclusion list — the
   exact failure mode the audit script exists to remove — and it leaves the
   campaign holding ten explicit arguments it did not have before.

It also inverts the campaign's headline under option 2: Phase C hands back
roughly ten explicit arguments that do not exist today, and Phase A's deletable
count falls to match. **Decision 4 takes option 1.**

#### The two hand-edits option 1 still needs

Both were missed by the draft that called this section's conclusion "everything
converges", and both are small. Naming them is what stops the rename PR going
red for reasons nobody predicted.

**One temporary argument, at the rename PR.** `Release::medias()` is
`hasMany(Media::class)` with no argument, so the moment the class is
`GameRelease` it derives `game_release_id` — while the column is still
`release_id` until Phase C. Left alone, the rename PR changes that relation's
SQL, `getDumpsAttribute()` (which reads `$this->medias`) starts raising 1054,
and the suite goes red. So the rename PR adds `'release_id'` explicitly:

```php
// Temporary: the class now derives game_release_id, the column is still
// release_id. Delete this argument in the media.release_id rename (Phase C).
return $this->hasMany(Media::class, 'release_id');
```

With it, the SQL diff on the rename PR is **empty**, which is the gate worth
keeping. The alternative OpenCode floated — accept a diff containing "exactly
the `medias()` change and nothing else" — is weaker for no benefit, because it
replaces a check anyone can run with a check someone has to read. The argument
is deleted again in Phase C, where the column and the default finally agree.

**One permanent argument, in Phase C.** `Media::release()` derives `release_id`
from its method name and always will. When Phase C renames `media.release_id`
to `media.game_release_id`, it breaks unless it is given
`belongsTo(GameRelease::class, 'game_release_id')` or the method is renamed to
`gameRelease()`.

**Take the argument, not the method rename** — priced the same way Phase B
prices everything else:

```
grep -rEn -- '->release[^A-Za-z_]' app/ resources/views/ tests/ | wc -l   # 77
```

77 lines, which is `->type` territory, and the three `release()` methods
(`Media::release()`, `ReleaseAka::release()`, `MenuDiskContent::release()`) all
point at the same table. Renaming all three to `gameRelease()` would close three
divergences and cost ~70 site edits for no behavioural gain; two of the three
already carry an explicit `'game_release_id'` today and are none the worse for
it. So Phase C adds the third, and the campaign ends with three `release()`
relations each naming their column — recorded in Phase D under *declined on
pricing*, exactly like `type()`.

`Game::progressSystem()` is the same shape and gets the opposite answer:
`->progressSystem` is **3** hits, so renaming the method to
`gameProgressSystem()` is the cheap fix there rather than adding an argument.

#### The ordering that follows

**Model rename → Phase A → Phase B → Phase C.** Stated explicitly because the
draft this replaces said "before Phase C" and left Phase A's relationship to the
rename to be inferred, which is precisely where it went wrong. With the rename
first there is no hold list and no exclusion by name: the audit reclassifies the
eight `Release` relations as divergent, Phase A deletes what the audit calls
redundant, and Phase C picks the eight back up when it renames their column.
Phase A's section carries the predicted count movement.

### Order

Ascending by **silent** risk, not by table count. One column family per PR:

1. `comments_id` → `comment_id` — one table, one relation pair, no `$fillable`.
2. `dev_pub_id` → `pub_dev_id`, `progress_system_id` →
   `game_progress_system_id`, `individual_nicks_id` → `individual_nick_id` —
   one table each. The `progress_system_id` PR also renames
   `Game::progressSystem()` to `gameProgressSystem()`, which is what keeps that
   relation working; `->progressSystem` is 3 hits.
3. `release_id` → `game_release_id` — ten tables, every one of them loud. This
   is the PR that settles the relation edits the model rename deferred: delete
   the eight `'release_id'` arguments on `GameRelease` (now redundant again),
   delete the temporary `'release_id'` on `GameRelease::medias()`, update
   `trainers()`'s first key, and **add** `'game_release_id'` to
   `Media::release()`. That last one names no column anywhere, so it is
   invisible to a grep for `release_id` and is the line to name in the PR
   description; the failure itself is loud (1054), it just is not findable.
4. `ind_id` → `individual_id` — only four tables, but it carries the campaign's
   single silent write, so it goes **last**. By then the recipe has been
   exercised twice, the checklist template has been proven, and the
   `interview_main.ind_id` `NOT NULL` change has already landed. Do it alone.

### Worked example: every site the `ind_id` rename touches

`ind_id` is the dangerous one, so it gets enumerated rather than left to a grep
at the time. This doubles as the checklist template for the other four renames:
the *categories* are what generalise, not the line numbers.

**Schema (4 tables).** `crew_individual`, `individual_nicks`, `individual_text`,
`interview_main`. `individual_nicks` also has `nick_id` pointing at the same
parent, which stays — see Phase D.

**Relationship definitions (7).** `Individual::text()`, `::interviews()`,
`::nicknames()`, `::individuals()`, `::crews()`, `Crew::individuals()`,
`Interview::individual()`. **Five** of these lose their arguments — `text()`,
`interviews()`, `crews()`, `Crew::individuals()` and `Interview::individual()`
all converge on `individual_id` once the column moves, and the two
`belongsToMany` among them lose *both* arguments, not one. Only the two
self-referential relations on `individual_nicks` keep theirs. (An earlier draft
counted this list as six and said four lose an argument; both were off by one,
caught by OpenCode.)

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
`Admin/InterviewsTable:65` (`interview_main.ind_id`) and
`Admin/Games/GameIndividualsTable:62` (`individual_text.ind_id`) — and two calls to a *helper* that takes the column
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

## Phase D — the nine that convention cannot reach, and the thirteen it should not

Write these down rather than leaving them to be rediscovered. Six unreachable,
three with no derivable default, thirteen declined: 22 of today's 48 divergent
relations end the campaign holding an explicit argument on purpose. The thirteen,
so the count is checkable against the audit output rather than trusted — the
three `type()` relations; the five that differ only because the model is
`PublisherDeveloper` and the table is `pub_dev` (`Game::developers()`,
`PublisherDeveloper::text()`/`games()`/`releases()`, `Release::publisher()`);
`Game::genres()`; `GameSubmitInfo::screenshots()`; `Game::vs()`; and
`ReleaseAka::release()` and `MenuDiskContent::release()`.

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

**Resolved outright by the table rule.** Four entries here were arguments about
which convention to follow. Nicolas's rule settles them, and in every case
**the schema is already correct** — only the code diverges. Verified against
`information_schema`:

| Column | References | Under the table rule |
|---|---|---|
| `pub_dev_id` (3 tables) | `pub_dev` | already correct |
| `game_submitinfo_id` | `game_submitinfo` | already correct |
| `trainer_option_id` | `trainer_option` | already correct |
| `game_genre_id` | `game_genre` | already correct |

So `publisher_developer_id`, `game_submit_info_id`, `trainer_id` and `genre_id`
are all off the table — they were only ever candidates because Eloquent derives
from the *model* name. This is a real point in the rule's favour: **against the
table convention this schema is far closer to consistent than it looked against
Laravel's**, 100 of 138 rather than the picture the earlier drafts painted.

What remains in each case is a code-side divergence only: the model name differs
from the table name, so the explicit argument stays unless the model is renamed
(`PublisherDeveloper` → `PubDev`, `Trainer` → `TrainerOption`, `Genre` →
`GameGenre`). Those are now optional tidy-ups rather than convention questions —
except for `Release` → `GameRelease`, which the section above shows is load
bearing.

**Deliberately not adopted.**
- **`GameVs`'s `atari_id` / `amiga_id`.** These say something `game_id` would
  not. `Game::vs()` is a `hasMany` and so cannot be fixed by a method rename;
  it keeps its argument. Only `GameVs::game()` → `atari()` is in Phase B.
- **`Media::release()`, `ReleaseAka::release()`, `MenuDiskContent::release()`.**
  All three point at `game_release`, all three could be closed by renaming the
  method to `gameRelease()`, and all three decline on the same pricing:
  `->release` is **77** lines across `app/`, `resources/views/` and `tests/`.
  Two already carry an explicit `'game_release_id'`; Phase C gives the third
  one. Three arguments against ~70 edits for no behavioural gain.
  `ReleaseAka::release()` and `MenuDiskContent::release()` count **here**, not
  in Phase C: their columns are already `game_release_id` and Phase C does not
  touch them, so nothing in that phase converges them. (`Media::release()` is
  the third, and it is not in today's 48 at all — it is clean today and
  divergent afterwards.)
- **`Article::type()`, `Media::type()`, `MediaScan::type()`.** Phase B *could*
  reach these by renaming the method, and declines to — see the pricing table
  there. `->type` is 97 hits repo-wide and mostly plain columns on other
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

`AppServiceProvider.php:94` enables `preventSilentlyDiscardingAttributes()`
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

Which reduces the silent class to a single sentence: **mass assignment into a
nullable foreign key column, in production.**

**Two sites, not one — and the second only exists because of the direction
change.** Phase C's new rule adds `game.progress_system_id` →
`game_progress_system_id`, and that column is nullable, is in `Game::$fillable`,
and is written by `GameController:178-183` through `$game->update([...])`. It
was not in the campaign before the rule changed.

It also fails *differently*, in a way the `NOT NULL` remedy below cannot reach:

| | `interview_main.ind_id` | `game.progress_system_id` |
|---|---|---|
| Write | `new Interview([...])` — an INSERT | `$game->update([...])` — an UPDATE |
| Dropped key does | writes `NULL` | omits the column entirely |
| Result | row with no individual | row keeps its **old** value |
| `NOT NULL` catches it | yes, 1364 | **no** — the column is never in the statement |

So the correction to the recommendation below: **making a column `NOT NULL`
protects INSERT paths only.** No schema constraint can see a key that was
dropped from an UPDATE, and the symptom is harder to diagnose than a null — an
admin changes the progress system, saves, and the value silently does not
change. That reads as "the form didn't take", not as a bug. There are five non-pivot
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
  instrument: the flag is global and would newly police **114 mass-assignment
  call sites across 55 files** under `app/`, in order to protect against a risk
  that exists at **one** of them. The command, because an earlier draft quoted
  107 across 51 without one:

  ```
  grep -rEn -- '(->|::)(create|createMany|fill|forceFill|update|firstOrCreate|updateOrCreate)\(|new [A-Z][A-Za-z]*\(\s*\[' app/
  ```

  The `(->|::)` alternation is what makes the number mean anything: a second
  review counted ~78 lines here and read the claim as overstated, having
  matched only `->create(` and missed every static `Model::create([`. It is
  still a floor — a call whose `(` and `[` land on different lines escapes any
  line-oriented count — and the order of magnitude is the argument either way:
  ~100 sites policed to protect one. Every latent stale key anywhere in the
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
trap.** Raised by OpenCode, then queried across the renameable foreign key
columns via `information_schema.STATISTICS` and `KEY_COLUMN_USAGE`.

**Re-measured 2026-08-23, because the first pass ran before the direction
reversal and surveyed the wrong set of columns.** It queried the *old* Group 1:
the nine tables carrying `game_release_id`, the four `ind_id` tables,
`article_user_comments.comments_id`, `game_developer.dev_pub_id` and
`game_genre_cross.game_genre_id` — 16 columns, and its 16 / 6 / 10 split was
correct **for that set**. The reversal changed twelve of them: the nine
`game_release_id` columns became the ten `release_id` ones, `game_genre_id` was
cancelled, and `game.progress_system_id` and
`crew_individual.individual_nicks_id` arrived. Only six columns are in both
sets. Under the current direction the set is the **18** of Group 1, and the
numbers move:

| | Laravel-named `<table>_<col>_foreign` | Named after the column |
|---|---|---|
| Constraints | **18 of 18** | 0 |
| Indexes | 6 of 18 | **12 of 18** |

The conclusion survives intact and is if anything stronger: the constraint name
can be derived, **the index name cannot**, and now two-thirds of the set is in
the undesirable half rather than well under it.

The six Laravel-named indexes are the four `ind_id` tables (`interview_main`,
`individual_text`, `individual_nicks`, `crew_individual`),
`crew_individual.individual_nicks_id` and `article_user_comments.comments_id` —
the small renames. The **twelve** plain ones are the whole of the two largest:
`game_developer.dev_pub_id`, `game.progress_system_id` (an index literally
called `progress_system_id`), and an index literally called **`release_id`** on
every one of the ten `release_id` tables, `media` included. So

```
RENAME KEY game_release_copy_protection_release_id_foreign TO ...
```

fails on all ten of them. Note which way round this goes:
the index is named for the column as it stands *today*, so it is `release_id`
that has to be renamed to `game_release_id`, not the reverse — the earlier draft
had this backwards because it was still describing the pre-reversal plan.

Read the index name out of `information_schema.STATISTICS` per table; never
derive it:

```sql
SELECT TABLE_NAME, INDEX_NAME, COLUMN_NAME FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = 'release_id';
```

(No renameable column carries more than one index — checked across all 18 —
which is the one thing here that is simple.)

One distinction worth drawing, because it changes how urgent this is. For the
six `*_foreign` indexes, the rename *introduces* the 1091 trap. For the other
twelve, `dropIndex(['release_id'])` is **already** broken today — Laravel
derives `<table>_<cols>_index` and the index is not named that either way — so
renaming them is tidiness rather than a regression fix.

Do it anyway, because the alternative is an index named `release_id` sitting on
a column called `game_release_id`, which is exactly the drift this campaign
exists to stop. The proof that nobody goes back for it is in the schema
already: `game_genre_cross` carries an index called **`game_cat_id`** on a
column that has not been called `game_cat_id` for years. That column is out of
this campaign's scope now — the `game_genre_id` → `genre_id` rename was
cancelled — so the index keeps its stale name for the time being. It is left
here deliberately, as the cheapest available illustration of what the other
twelve become if the index rename is skipped.

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
`down()` drops the constraint *before* renaming the key back.

Only `down()`'s order is actually forced, and not for the reason an earlier
draft gave. It said MariaDB will not rename a key out from under a live
constraint — **it will**; OpenCode probed this on 10.11.18 and `RENAME KEY`
is accepted in either order relative to the column rename, the constraint
following the column silently both ways. The real constraint on `down()` is
narrower: its final `ADD CONSTRAINT` needs the *old* name free, so the rename
back has to happen after the drop. `up()`'s order is a readability choice, not
a requirement.

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
4. Re-run the relationship audit script and confirm the three counts moved by
   exactly what the PR claims. This is the campaign's progress metric and it is
   cheap to check. **Predict the movement in the PR text rather than asserting
   "no change"** — the `Release` → `GameRelease` PR in particular moves relations
   in both directions at once (eight out of the redundant set, nine in, one out
   of clean), so a net figure hides the interesting part. Phase A's section
   carries the derivation.

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
- **An assertion on the game's progress system after saving it.**
  `tests/e2e/admin-write/games.spec.js:194` already selects it and saves, but
  never checks it came back, so a dropped key passes that spec green. Nothing in
  PHPUnit touches `updateBaseInfo` at all. One added assertion on an existing
  spec is the whole fix, and it is the *only* protection available for that
  site — see the UPDATE row in the table above.
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
C's PRs are verified against it. **The rewrite script that applies Phase A's
deletions should be committed beside it, for the same reason and with more
urgency**: it is the tool behind the "70 deleted, SQL diff empty" measurement,
and until it is in the repository that measurement cannot be reproduced by
anyone else. Suggested home:
`artisan al:audit-relationship-keys`, printing the three counts, the pivot-table
snapshot and the
divergence table. That also makes it a candidate arch test later — "the number
of divergent relations must not increase" is the same shape as
`QueryConventionsTest` and `MigrationModelsTest`, both of which came out of the
previous campaign and are the parts of it still doing work.
