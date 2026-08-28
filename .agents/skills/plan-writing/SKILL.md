---
name: plan-writing
description: Write, review or rewrite an engineering plan document in docs/plans/. Use when the user asks for a plan for a campaign, migration, refactor or multi-phase change, or asks to review, revise or rewrite an existing plan. Covers the required structure (one spine, self-contained units), the writing rules (declarative, no review history, no status), the acceptance checklist, and the rule that a review reports its findings rather than applying them.
---

# Writing a plan

A plan in this repository is a durable engineering document, not a conversation
artifact. It is read months later by someone about to execute one phase of it.
Write for that reader.

## Where it goes

- `docs/plans/YYYY-MM-DD-slug.md`, dated the day the plan is started.
- Never write a plan to a scratchpad or a temp directory.
- Revising an existing plan means rewriting it in place. Do not produce a diff,
  a changelog, or a "what changed" section alongside it.
- The filename date and the date under the title both stay at the day the plan
  was started, through every rewrite. Only measurement dates move, and each of
  those is written next to the number it belongs to.

## How the work is delivered

- Base the work on `development`, not on whatever branch is checked out.
- **The delivery unit is one commit per unit of the spine, not one pull
  request.** State it in the header. Do not carry a pull-request rule over from
  an earlier plan without restating it at commit granularity.
- A commit is not the rollback unit. Where a unit carries several migrations,
  reversing it is `migrate:rollback --step=N` for the N migrations in that
  commit, and reverting the commit removes all N migration files at once. Name
  both numbers rather than writing "the revert commit deletes the migration
  file", which only holds for a single-migration commit.

## Reviewing a plan

**A review reports; it does not edit.** Read the plan, check it against the
rules below, hand back the findings, and leave the file untouched.

- A finding names what is wrong, where, and what it should say instead. Report
  it; do not apply it.
- Keep findings separable, so each can be accepted or rejected on its own. The
  user will take some and refuse others, and a plan silently rewritten to match
  the whole review takes that choice away.
- Verify before reporting: re-run the query, open the file at the line. A
  finding is a fact, not a suspicion.
- Only an explicit instruction — "fix these", "apply the review", a review
  handed back with the changes asked for — turns a review into a revision. Then
  the rewrite-in-place rule above applies.

## Structure

**Pick one spine and use it everywhere.** Either phases or workstreams — never
both, and never a set of topic sections shadowed by a second set of phase
sections that act on the same topics. Every top-level heading is one unit of
that spine, plus the cross-cutting sections at the end.

Each unit is self-contained and carries its content in this order:

1. **Findings** — what was measured, in tables or bullets.
2. **Decisions** — stated inside the unit they rule on, in bold, as a ruling.
3. **The changes** — migrations, code edits, or steps.
4. **Acceptance** — the specific gate for this unit.

The first three name an order, not headings: give each a topical `###` heading
that says what it is about — `### The columns`, `### The migrations`.
`Acceptance` is a literal heading, and it closes every unit.

Cross-cutting sections come last, once each: sequencing (if not in the header),
verification, deploying, out of scope.

The header carries: title, date, one paragraph of what this succeeds or follows
on from, the end state in one sentence, a table of the units, and the dependency
order between them.

**Every clause of the end state is checkable, and maps to an acceptance gate.**
Write each clause as the thing that would be true when the plan is done, in
terms someone could turn into a query, a grep or a test — and name the exception
where the plan takes one, rather than stating an absolute the units then
contradict. A clause with no gate under it is decoration: cut it, or replace it
with the claim actually being made.

**Never include a status.** No "proposed", "not yet executed", "reconnaissance
complete", "in progress". It drifts the moment work starts and nobody updates
it. Dates on measurements are fine — those do not drift, they age.

**No separate decisions table.** A numbered decisions register at the top forces
every topic to appear twice: once where the facts are and once where the ruling
is. Put the ruling with its facts.

## Writing rules

- Factual, declarative sentences. State the conclusion, not the path that
  reached it.
- No metaphors, slogans or figurative phrasing. Write the literal statement,
  which is the one a reader can check. The tell is a thing given a verb it
  cannot perform: an index does not *earn*, a table does not *want*, a column
  does not *know*, a migration does not *care*. A slogan also hides scope —
  "every index earns the write it costs" sounds like every index was audited
  against the read paths, when what was checked was structural duplication; "no
  index duplicates a primary key or another index" says what was actually done.
- No pros/cons comparisons. No "X, not Y" framing.
- No meta-commentary about the plan or its review history: no "previously
  considered", "after further analysis", "on reflection", "the first draft
  said", "the second review changed". Only the current, correct state matters.
  A reversed decision is written as the decision, not as a reversal.
- One topic per section, and each topic appears exactly once. Do not return to a
  topic later in the document.
- Bullets over prose wherever the content is a list of facts, steps or findings.
- One concrete example per topic where it helps, two or three lines maximum: a
  sample query, a table name, an error message, a `file.php:line` reference.
- Delete any sentence that does not add a new fact or a decision. If two
  sentences say the same thing, keep one.
- No rationale for a decision that was never in doubt.
- No speculative scope: work the user did not ask for, written up as part of the
  plan.
- Something uncertain gets a single line — `Open question: …` — and no
  surrounding hedging or exploration.
- No introductions, no summaries of summaries, no "in this section we will".
  Start each section with the finding.

## What a plan must contain

- **Measured numbers, with the query named so they can be re-run**, and the date
  they were measured. `17 orphans` beats `some orphans`.
- **The exact error text** for a failure mode that matters, in a fenced block.
- **`file.php:line` references** for the code a phase touches.
- **A per-unit acceptance gate** that is checkable, and honest about what it
  cannot check — a phase no test can fail needs to say so and name the schema
  query that is the real gate.
- **An out-of-scope section** that records what was examined and deliberately
  left alone, with the reason. This is what stops the next audit re-deriving the
  same inventory.

## Before finishing

Read the draft back and check:

- [ ] One spine; every top-level heading belongs to it.
- [ ] No status anywhere.
- [ ] Every topic appears exactly once. Grep for the two or three key nouns of
      the plan and confirm each clusters in one section.
- [ ] No sentence tells the story of the plan itself. Grep for "previously",
      "originally", "the first draft", "on reflection", "after review", "it
      turns out", and read each hit: a fact about the subject matter stays, a
      remark about how the plan got to its current state goes.
- [ ] Every number has a date and a way to re-measure it.
- [ ] Read the end state clause by clause and name the gate each one maps to.
- [ ] No schema object is given a verb it cannot perform.
- [ ] Every unit ends with an acceptance gate.
- [ ] Nothing is stated twice in different words.
