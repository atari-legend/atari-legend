# End-to-end tests

Playwright specs that drive a real browser against a real server, so they catch
what the PHPUnit feature suite cannot: JavaScript errors, Livewire not booting,
images that 500, pages that render blank.

Every page of the site has a test that loads it and asserts the one thing it is
for. That is the floor, not the ceiling: most files here are still small, and the
value is that there is now an obvious place to put the next test.

## Layout

```
tests/e2e/
├── auth.setup.js       signs in as admin, saves the session for the admin project
├── support/            shared modules; matched by no project, so never run as tests
│   ├── test.js         the `test` most specs import
│   ├── public-write.js the `test` the public-write specs import, plus `adminPage`
│   ├── assertions.js   expectPageRenders / expectResourceLoads
│   ├── auth.js         signIn / signOut through the real forms
│   ├── editor.js       driving the SCEditor BBCode editors
│   ├── autocomplete.js driving the autocomplete fields
│   ├── comments.js     posting, editing and deleting a comment
│   ├── write.js        helpers and parent factories the write projects need
│   ├── fixture.js      the seeded ids and names
│   └── server.php      dev-server router (see playwright.config.js)
├── public/             the public site, one file per section
├── admin/              the admin panel, read-only, one file per section
├── admin-write/        the admin panel, creating and deleting
└── public-write/       the public site as a signed-in visitor, contributing
```

One file per section, with one exception: `admin-write/games.spec.js` covers the
game and its releases, and `admin-write/games-reference.spec.js` the individuals,
companies, series and configuration tables behind them. They were one file until
it was a thousand lines of two unrelated subjects.

**A spec's directory decides which project runs it**, and therefore whether it
has a session and whether it may write:

| Directory | Project | Session | Writes |
|---|---|---|---|
| `public/` | `public` | none — a clean guest | no |
| `admin/` | `admin` | signed in as admin, via `auth.setup.js` | no |
| `admin-write/` | `admin-write` | signed in as admin | yes — see below |
| `public-write/` | `public-write` | signs in as `FIXTURE.contributor` itself | yes — see below |

**No project waits for another.** Each of the four depends on `setup` for a
session and on nothing else, so they all run at once and any one of them runs on
its own. `admin-write` used to depend on `public` and `admin` as well — a mutex
against the shared database rather than a real dependency, which meant running
one write spec ran the whole suite first. What replaced it is isolation in the
specs themselves; see [Writing specs that write](#writing-specs-that-write).

`public-write` depends on `setup` for a reason that has nothing to do with its
own session: it signs in through the login form like the `public` specs do, but
a public form cannot create the game or the review it writes against, so it
opens a *second* context from `.auth/admin.json` to build the parent.

A spec anywhere else is silently skipped. After adding one, check it was picked
up:

```bash
npx playwright test --list
```

Pages that need a signed-in *non-admin* and only read — `/profile`,
`/reviews/submit` — live in `public/account.spec.js` and sign in for themselves,
because what is worth testing there is what an ordinary account can do. What an
ordinary account can *write* is `public-write/`.

## Conventions

```js
import { test, expect } from '../support/test.js';
import { FIXTURE } from '../support/fixture.js';
import { expectPageRenders } from '../support/assertions.js';

test.describe('Games', () => {
  test('lists games', async ({ page }) => {
    const response = await page.goto('/games');

    await expectPageRenders(page, response, '/games');
    await expect(page.getByRole('link', { name: FIXTURE.game.name }).first()).toBeVisible();
  });

  // TODO: advanced search, genre filters, voting, comments
});
```

- **Import `test` from `support/test.js`**, not from `@playwright/test`. It wraps
  the `page` fixture with the two checks nobody should have to remember: an
  uncaught JavaScript exception fails the test, and so does a subresource this
  application answered 404 to. The second one exempts `/storage/` and
  navigations, for reasons worth knowing before you trip over them — follow-up 7.
- **Assert content, not just a 200.** `expectPageRenders` checks the status, that
  the request was not redirected away, and that no Laravel exception page came
  back. That is the floor; a test earns its place by also asserting the thing the
  page is for.
- **Navigate by role and accessible name** (`getByRole('link', { name })`) rather
  than by CSS class, so a Bootstrap change does not break the suite.
- **Read fixture data from `FIXTURE`**, never as a literal string. Renaming a
  seeded row should be a one-line change.
- **Non-HTML routes use `expectResourceLoads`.** `page.goto()` returns a null
  response for a download and `page.content()` on an image gives you Chrome's
  viewer markup, so images, feeds, sitemaps and the EPUB are fetched with
  `page.request.get()` instead.
- **Leave a `TODO` naming what the section still does not cover.** Those comments
  are the backlog; the checklist below is their summary.
- **An autocomplete belongs to the section whose form calls it.** The endpoints
  under `/ajax` and `/admin/ajax` are asserted from that section's spec rather
  than from a file of their own, and `pickSuggestion()` drives the field itself.
  It takes a selector, not a locator, because the suggestion list is rendered as
  the input's next sibling — the games search alone has six on one page, and an
  unscoped `.autocomplete-results li` matches every one of them.
- **`public/` and `admin/` are read-only.** Every worker shares one seeded
  database. Anything that writes goes in `admin-write/` or `public-write/`.
- **A read spec must survive a row it did not expect.** The write projects are
  running at the same time, and however carefully they clean up, one of their
  rows can be alive while you are looking at a list. So assert that a `FIXTURE`
  name is *there* — never a count, never a row position, and never a bare locator that a
  transient row could also match. `public/interviews.spec.js` is the worked
  example: an interview is titled after its individual, so
  `getByRole('heading', { name: FIXTURE.individual.name })` would have two
  matches and fail on strict mode if a write spec ever interviewed the seeded
  individual. It creates its own individual precisely so that stays true.

## Writing specs that write

`admin-write/` and `public-write/` run in parallel with everything else against
the same database. One rule is what makes that safe, and what makes running the
suite twice over leave it as it started:

> **A write spec creates every row it modifies — the parent as well as the
> child — and deletes them all again before it ends.**

The line is *mutation*, not reference. These forms still **select** a seeded
crew, genre, port or condition; menu conditions and content types come from a
migration and have no create form at all. What a spec may not do is make a
seeded row the parent of something new: a release on the seeded game, an issue
on the seeded magazine, an interview about the seeded individual. Those are the
rows the read specs assert on, and a child of one is visible on its parent's
page.

An *association* the other way round is fine, and the full-game spec relies on
it: crediting the seeded individual on a game it created, or linking that game
to the seeded one as similar. The new row is the parent there, and the seeded
row is a peer — nothing appears under it that a read spec looks at.

`grep -rn "FIXTURE\." admin-write/` is the check. Every hit should be reference
data.

- **`support/write.js` has a factory for each parent** — `createGame()`,
  `createIndividual()`, `createMagazine()`, `createMenuSet()`, `createMenu()`,
  `createMenuDisk()` — each returning an `{ id, … }` carrying whatever its
  children need, and each with a matching `delete…()`. They drive the real
  create form, so the parent's form is covered as a side effect.
- **Name rows with `uniqueName('News')`**, which gives an
  `E2E News <timestamp><random>` no other row will have — and something greppable
  in the database if a run is killed halfway through. The random suffix matters:
  workers run in parallel and a timestamp alone collides.
- **Delete it again in the same test.** `deleteRow(page, term)` for a Livewire
  table, `deleteByAction(page, '/releases/42')` for the cards that stand in for a
  table on releases, menus, disks and issues.
- **Delete children before parents.** `Game::getIsDeletableAttribute()`
  (`app/Models/Game.php`) refuses a game that still has a release, a review, a
  fact, a credit, a developer, a screenshot or a similar-game link — the button
  is rendered either way but disabled, so `deleteRow` times out on the
  actionability check rather than failing outright. AKAs and VS rows are
  deliberately not on that list. The same goes down the menus hierarchy: disk,
  then menu, then set.
- **Every delete button is wrapped in `confirm()`, and Playwright dismisses
  dialogs by default** — which cancels the submit and fails the test somewhere
  else entirely. `deleteRow` and `deleteByAction` accept it for you;
  `acceptConfirms(page)` if you are clicking one yourself.
- **Drive the JavaScript rather than the field it writes to.** The hidden `author`
  / `game` / `individual` inputs are filled by the autocomplete's `onSelection`,
  and the body of every content form is posted by SCEditor, not by the textarea in
  the markup — so use `pickAutocomplete()` and `fillEditor()`. Filling the hidden
  input directly is exactly what the PHPUnit suite already does, and passes
  whether or not any of it still works. `pickAutocomplete()` is
  `pickSuggestion()` from `support/autocomplete.js` — which the public specs use
  too — plus the assertion that the companion id arrived.
- **Do not re-assert the controller.** Validation rules and changelog rows belong
  to `tests/Feature/Admin`. What is only testable here is that the rendered form
  submits at all.

The trade-off, so it is not a surprise: break a create form and every spec that
needs that parent fails too, as setup rather than as its own assertion. That is
the price of specs that owe nothing to each other, and it is the deliberate
choice here.

### Where `public-write/` bends the rule

A public form creates a comment, a vote, a submission or a review. It cannot
create the game, review, interview or article to hang one off, and for three of
those it cannot delete the row again either — only the admin has a screen for a
game submission, a news submission or an unpublished review.

So a `public-write/` spec runs **two sessions at once**: `page`, signed in as
`FIXTURE.contributor` through the real login form, and `adminPage`, a second
context restored from `.auth/admin.json`. The parent is built and torn down over
there with the same `support/write.js` factories `admin-write/` uses; everything
the test is about happens over here. Import `test` from `support/public-write.js`
rather than `support/test.js` to get that fixture — it extends the other one, so
`page` still fails on an uncaught JavaScript exception.

- **A user is the one row no form can create**, so the account the admin section
  edits — `FIXTURE.moderatedUser` — is seeded rather than built, and
  `admin-write/users.spec.js` puts back whatever it changes. Follow-up 14.
- **Two seeded accounts belong to this project and nothing else.**
  `FIXTURE.contributor` writes; `FIXTURE.accountUser` is the one whose own
  profile and password get rewritten, by `public-write/account.spec.js` alone.
  Neither is `FIXTURE.user`, which owns the seeded comment on the seeded game
  and which `admin-write/content.spec.js` picks in an autocomplete.
- **`account.spec.js` is `mode: 'serial'`.** Its three tests all rewrite one row,
  and the password one would sign the other two out halfway through.
- **The JavaScript is the point.** `comments.js`, `user.js`, `bbcode.js` and
  `review/submit.js` sit between the visitor and four of these POSTs, and
  `tests/Feature/Public/` posts straight past all of them. Drive the pencil, the
  trash link, the toolbar button and the Preview tab — never the form they
  submit. `support/comments.js` does the comment round trip, assertions on the
  `d-none` toggle included, because a test that only checked the text afterwards
  would pass with the script deleted.
- **Bootstrap rewrites roles as it boots.** A tab is `<a href="#preview">` in the
  markup and `role="tab"` once Bootstrap 5.2 has seen it, so `getByRole('link')`
  matches only before the script runs. `reviews.spec.js` addresses it by href and
  waits for the attribute — which is also the wait for Bootstrap, since
  `review/submit.js` hangs off `show.bs.tab`. An inactive tab pane is
  `display: none`, so anything inside it is out of the accessibility tree: switch
  back to `#edit` before looking for the Submit button.

### The one row a run leaves behind

Everything else in the suite is back where it started afterwards. This one is
not, and it is the application's doing rather than the spec's:

**A `website_validate` row per run.** `public-write/links.spec.js` submits a
link, and the admin in this repo has no screen for `website_validate` at all -
link submissions are still approved in the legacy CPANEL, so there is no route
to delete one through. The row is named `E2E Link …`, renders nowhere, and is
greppable. Give the admin a submissions screen - follow-up 12 - and this
becomes an ordinary spec.

`SELECT * FROM website_validate WHERE website_name LIKE 'E2E %'` is therefore
the only number that should move. Anything else growing across runs is a spec
that failed to clean up - including `screenshots`, which used to gain two
rows a run until follow-up 11 was fixed and is now a useful canary.

## Adding a section

1. Create `public/<section>.spec.js`, `admin/<section>.spec.js`,
   `admin-write/<section>.spec.js` or `public-write/<section>.spec.js`.
2. Seed anything a **read** spec needs in `database/seeders/E2ESeeder.php`, with
   an explicit primary key exposed as a constant, and mirror it in
   `support/fixture.js`. A **write** spec seeds nothing: it creates its parent
   through the admin, so add a factory to `support/write.js` if there is not one
   already. (The one exception is an account, which no public form can create
   without solving an hCaptcha — hence `contributor` and `accounttester`.)
3. Write "lists X" and "displays one X", then a `TODO` for the rest.
4. `npx playwright test --list` to confirm the right project picked it up.

## Running

The app has to be served against the E2E database, and the fixture has to be
seeded into it first. CI does this with host `php` and MariaDB as a service; the
recipe below does it through Sail, which is how this project is normally driven.

```bash
# One-off: an empty database for the fixture. `sail mariadb` opens a shell but
# forwards no arguments, so this one goes through compose directly.
docker compose exec mariadb mariadb -uroot -patarilegend \
  -e 'CREATE DATABASE IF NOT EXISTS atarilegend_e2e;'

cd /path/to/atari-legend

# The `sail` script exports these; plain `docker compose` does not, and Sail's
# entrypoint gosus to $WWWUSER - so without them it tries to switch to a user
# named after the first word of the command and dies.
export WWWUSER=$(id -u) WWWGROUP=$(id -g)

E2E="-e DB_CONNECTION=mariadb -e DB_HOST=mariadb -e DB_DATABASE=atarilegend_e2e \
     -e DB_USERNAME=root -e DB_PASSWORD=atarilegend -e APP_ENV=testing \
     -e APP_KEY=base64:AckfSECXIvnK5r28GVIWUAxmbBSjTsmF/0rZh1cyWXk="

docker compose run --rm $E2E laravel.test php artisan migrate:fresh --force
docker compose run --rm $E2E laravel.test php artisan db:seed --class=E2ESeeder --force
./vendor/bin/sail npm run build            # @vite needs public/build/manifest.json

docker compose run -d --name al-e2e-serve -p 8123:8000 $E2E \
  -e APP_URL=http://127.0.0.1:8123 -e PHP_CLI_SERVER_WORKERS=16 \
  laravel.test php -S 0.0.0.0:8000 -t public tests/e2e/support/server.php

PLAYWRIGHT_TEST_BASE_URL=http://127.0.0.1:8123 npx playwright test
docker rm -f al-e2e-serve
```

`docker compose run` rather than `sail` for the three that need `-e` overrides:
the `sail` wrapper has no way to pass them through. `laravel.test` is the
application container, and the only PHP image in the stack.

Any subset runs on its own, which is the point of no project depending on
another:

```bash
npx playwright test tests/e2e/admin-write/links.spec.js   # setup, then that file
npx playwright test --project=public                      # no login at all
npx playwright test --project=admin-write --no-deps       # skip even the login,
                                                          # while .auth/admin.json holds
npx playwright test --project=public --project=public-write   # the isolation claim
```

Two things that will bite otherwise:

- **`APP_ENV=testing` makes Laravel read `.env.testing` *instead of* `.env`**, and
  that file sets only `DB_CONNECTION=sqlite`. Every other `DB_*` value has to be
  passed explicitly, `APP_KEY` included, or the app quietly points somewhere
  else.
- **Do not run `migrate:fresh` without `DB_DATABASE` set.** It would drop the
  development database.

Without `PLAYWRIGHT_TEST_BASE_URL`, Playwright starts its own server via the
`webServer` block in `playwright.config.js`, which needs a host `php` with
`pdo_mysql`. That is how CI runs.

## Coverage checklist

Route coverage is not the goal - feature coverage is. What the suite checks
today, and what it does not:

| Section | Covered | Not yet |
|---|---|---|
| Home | page renders, nav links to each section, spotlight image, the Screenstar, Who is it? and Trivia cards | - |
| Nav search | the games-and-software endpoint, `?q=`, url and icon on every row, following a game and a piece of software from the box | the icons as rendered, keyboard selection |
| Games | list, detail by slug, release, slug redirect, screenshot, box scan, the magazines that covered it, the three contribution forms hidden from a guest | gallery, similar games |
| Games search | title, A-Z browse, exact-match redirect, empty state; its 6 autocomplete endpoints, AKA merging and ranking, a quote as data; searching by title, year, individual and publisher through the widget; genre, engine and developer by name, by id and through the dropdown toggle; the checkbox filters in both directions | the export view |
| News | list, no submission form for a guest, pagination across both pages | - |
| Reviews | list, detail | the scores as published, unpublished hidden |
| Interviews | list, detail, individual avatar, a chapter hotspot followed to its anchor, a screenshot and its caption | - |
| Articles | list, detail, the type badge, a screenshot and its caption | filtering by type, which does not exist - see follow-up 13 |
| Menu sets | list, detail, search by title and A-Z, the empty state, by-software, EPUB export, the software and crews autocompletes; a disk card end to end - contents in all three shapes, condition, donor, notes, scrolltext, screenshot and dump download; the software page, a game's menus card, and the Latest menus card | condition filters, crew pages |
| Magazines | list, detail, the rendered index of an issue and all four of its row shapes, an issue cover, the archive.org read link and its /details/ to /stream/ rewrite | the page-count chart (needs 5 seeded issues) |
| Links | list, category filter, screenshot, no submission form for a guest | dead-link flagging |
| Music | cover image | the SNDH player (ym2149-wasm). The proxy is covered by ResourceControllersTest rather than here - see follow-up 5 |
| Account | sign in, sign out, profile, review form, password confirm, guests kept out (pages and the admin autocompletes), a signed-in non-admin kept out of /admin, unverified redirect | registering (needs a real hCaptcha), the e-mail field's uniqueness rule |
| Crawler | sitemaps, robots.txt, both feeds, health check | that they list the right entities |
| Admin games | list, create and edit forms, 7 game panels, 5 release panels, fact create/edit, issues, music, 4 reference sections + their create forms, 20 config tables, the games and sndh autocompletes | - |
| Admin content | list/create/edit for news, reviews, interviews, articles | `<br />` normalisation |
| BBCode editor | boots on all 8 forms that host one; the custom toolbar buttons; a custom code round-tripping WYSIWYG ↔ source | the emoticon dropdown, image/link commands, maximize |
| Charts | the admin statistics page draws all of them, the updates chart on /games | the magazine page-count chart (needs 5 seeded issues) |
| Admin menus | sets list, 4 edit forms, 6 create forms and their bare-URL 404s, 3 disk-content types, import screen and template, screenshot and dump uploads | running an import |
| Admin magazines | list, magazine and issue create/edit, index types, the index editor rendering its rows and re-sorting them | - |
| Admin links | list, create and edit, categories | approving submissions - there is no screen for `website_validate` at all |
| Admin users | list, edit, comments, the users autocomplete | - |
| Admin others | trivia, quotes, spotlights + create/edit, statistics, changelog | statistics figures |
| **Admin writes** | news, reviews, interviews, articles, game, game AKA, release, individual, menu set, menu, disk, disk content, magazine, issue, menu software, link, category, spotlight - each created and deleted through its form, parents included; the company, individual, game, software, sndh and user pickers driven as widgets; magazine and issue updated field by field; the magazine index editor built row by row and checked on the public page after every change; a menu set built up to two menus and three disks, with a screenshot and a dump uploaded, and checked on the public page after every change; all six release system panels, the scene panel, release and media scans; crews with members, sub-crews, parent crews and a logo; individual nicknames and avatar, company logo, series membership, the issues-screen genres action, the game music panel; the inline tables - game config, menu conditions, content types, article types, magazine index types, trivia and quotes; article, interview and news images; a user edited, promoted, deactivated and given an avatar | deleting a user (nothing can create one - follow-up 12), running a spreadsheet import, the crew genealogy picker on an individual |
| **Public writes** | all 13 of the `auth:web` routes: rating a game and withdrawing it, commenting on a game, review, interview and article, editing and deleting one's own comment, correcting a game with a file attached, submitting a review - toolbar, preview tab and scores - submitting news and a link, updating the profile, adding and removing an avatar, changing the password and changing it back; and the moderation queues from both ends - a news submission and a review submission approved as well as deleted, a game submission approved, a comment edited and deleted from the admin | approving a link submission (no screen exists), several files in one correction, a non-image attachment |

## Follow-ups

1. **The public `magazines` write routes had no auth middleware.** They sat in a
   group with only `verified` and `nondraft`, which is a no-op for a guest, and
   failed only because the controller methods were missing. Pruning the resource
   to `only(['index', 'show'])` closed that; do not re-add them without auth.
2. **`tests/Feature/RoutesTest.php` guards the whole class of bug** that pruning
   fixed: a `Route::resource()` without `only()`/`except()` registers actions the
   controller does not implement, and those answer 500 rather than 404.
3. **Fixed: a missing SCEditor script took out the admin's JavaScript (#272),
   and it had become the suite's one real flake.** An uncaught `Cannot read
   properties of undefined (reading 'set')` — or `(reading 'command')` —
   `resources/js/admin/sceditor.js` reaching for a global that a separate
   `<script>` tag had not set. SCEditor and Chart.js are both bundled through
   Vite now, so the library is an `import` rather than a request that can go
   missing, and `admin/editor.spec.js` asserts the editors boot rather than only
   that the page renders.
4. **Done: mutating flows on the *public* side.** `public-write/` covers all 13
   `auth:web` routes, and now the approve half of the moderation queues as well.
   Registration is the one public write still out of reach: it needs a real
   hCaptcha response, which is why `tests/Feature/Public/AuthTest` swaps the
   captcha HTTP client instead.
5. **Fixed: `/music/{sndh}` had its upstream host spelled into the controller.**
   `config('al.sndh.mp3_base_url')` supplies it now, defaulting to the same URL,
   so a test can point it somewhere it controls. No e2e spec followed, and the
   TODO in `public/music.spec.js` says why: `ResourceControllersTest` already
   fakes the HTTP client and covers the URL composed, the subtune padding and a
   404 passing through.
6. **Fixed: clearing an index row's type silently threw away the edit.** The
   blank option of the magazine index editor's type select carried
   `value="null"`, so choosing it bound the *string* `null` to an integer
   column. Validation rejected it, the component has no `@error` output, and
   the screen looked like it had saved. `MagazineIndexTest` never saw it - it
   posts an id. Found by `admin-write/magazines.spec.js` on its first run.
7. **Fixed: subresource 404s were invisible.** `guardAgainstMissingSubresources()`
   in `support/test.js` now fails any spec whose page asked this application for
   a script, stylesheet, font or static image and got a 404 - the case follow-up
   3 was.

   It exempts `/storage/`, and that exemption is the interesting part: on its
   first CI run the guard went red on another spec's game screenshot, because a
   card that picks a random review had rendered it in the moment between that
   spec's page load and its teardown unlinking the file. Stored uploads belong
   to rows, rows come and go while everything runs in parallel, and that is the
   "survive a row you did not expect" rule rather than a defect. Uploads worth
   asserting on are asserted deliberately, with `expectResourceLoads()`, against
   a row the spec owns.
8. **Fixed: a menu set's sort direction did not survive its own edit form.**
   `admin/menus/sets/card_edit.blade.php` compared the stored `menus_sort`
   against `'ascending'` / `'descending'`, but the column is an enum of `asc` /
   `desc`. `MenuAdminTest` never saw it: it posts `sort` and reads the column
   back, so the round trip it checks never goes through the rendered select.
9. **Fixed: `public/menus.spec.js` searched on the wrong parameter.** It
   requested `/menusets/search?search=…`, but `MenuSetController::search()`
   reads `title` and `titleAZ`; with neither present it forces both result sets
   empty. So the test asserted that a deliberately blank search page renders.
   It now asks on `title`, asserts the software that comes back, and covers the
   A-Z browse and the empty state as well.
10. **Creating a review is not atomic, and any page listing reviews can catch
    it half-done.** `ReviewsController::store()` and `ReviewController::submit()`
    both insert the `reviews` row and attach `review_game` as two separate
    statements outside a transaction, and
    `resources/views/components/cards/reviews.blade.php` dereferences
    `$review->games[0]` unguarded. A request that renders the "In-Depth Reviews"
    card in that window gets `ErrorException: Undefined array key 0` — a 500 on
    an unrelated page, for a visitor who did nothing but load it while somebody
    else was submitting. Wrapping both controllers in a transaction is the fix;
    guarding the card with `$review->games->first()` would also stop it 500ing.
11. **Fixed: deleting a screenshot never deleted its `screenshots` row.**
    `GameScreenshotsController::destroy()` detached the pivot and unlinked the
    file; `GameSubmissionController::destroy()` unlinked the file and deleted the
    submission. Neither removed the row, so the table accumulated ids with
    nothing behind them — two per full e2e run, and one per screenshot a
    moderator had ever removed in production. Both delete the row now, as
    `MenuDisksController::destroyScreenshot()` always did.
12. **Link submissions have no screen in this admin at all.** `/links/submit`
    writes a `WebsiteValidate` row and the only place one can be read or approved
    is the legacy CPANEL. News submissions (`admin/news/submissions`) are the
    shape to copy: an index, an approve and a destroy. That would close the last
    gap in the admin's coverage of the three moderation queues, and turn
    `public-write/links.spec.js` into an ordinary spec.
13. **There is no filter by article type.** `ArticleController::index()` takes no
    `Request` at all. The checklist promised one for a long time; what exists is
    the badge on the list, which `public/articles.spec.js` now covers. If the
    filter is wanted, it is a feature request rather than a coverage gap.
14. **A user cannot be created through any form**, so `admin-write/users.spec.js`
    cannot delete one: registration is behind an hCaptcha and the admin has no
    create screen. It rewrites `FIXTURE.moderatedUser` instead, seeded for that
    file alone, and leaves the destroy route uncovered. An admin create screen -
    or a per-run seeded account - would close it.
15. **`MenuCrewController::destroy()` deletes the crew row and nothing else.** It
    never detaches `crew_individual` or `sub_crew`, and never unlinks
    `images/crew_logos/{id}.{ext}` — `destroyLogo()` is the only thing that
    does. The trash button is disabled only for crews on a menu set, so deleting
    a crew with members, a genealogy or a logo leaves orphan pivot rows and a
    stray file. Same shape as follow-up 11.
16. **Nothing validates a crew relationship.** `addIndividual()` and
    `addSubCrew()` call `find()` and redirect as if they had saved when it
    returns null, so the `@error('individual')` / `@error('subcrew')` markup in
    those cards can never render. `sub_crew` also has no unique key on
    `(crew_id, parent_id)` and `attach()` is unconditional, so the same
    sub-crew twice - or a crew under itself - is representable.
17. **A failed archive.org fetch is stored as the issue cover.**
    `resources/js/admin/magazines/magazines.js` binds one handler to both `load`
    and `error` and arms `useArchiveOrgCover` either way, and
    `MagazineIssuesController::fetchImage()` never checks `$response->successful()`
    — so archive.org's 404 page is written to `images/magazine_scans/{id}.html`
    with `imgext` set to `html`, and the issue renders a permanently broken
    cover. Two independent fixes: do not arm the flag on `error`, and guard on
    the response being a successful image. `admin-write/magazines.spec.js`
    stubs the host with `page.route()` and covers the browser half; the
    controller makes the same request again from PHP, against a hard-coded host
    that wants extracting the way follow-up 5 extracted the SNDH one.
18. **`ReleaseMediasScansController` looks up its fallback scan type by name.**
    `MediaScanType` `'Other'` is fetched with `where('name', …)->first()` and
    `associate()`d without a null check, so a database whose reference data does
    not happen to contain that row 500s on the next line. Read from the code
    rather than reproduced.
19. **Fixed: four forms dropped their own image on any unrelated save.** The user,
    individual and company edit forms all started from `$ext = null` and wrote it
    to their image-extension column whether or not a file had been chosen, so
    editing an e-mail address or a name took the picture down and stranded the
    file - the delete routes build their paths from that same column. Each keeps
    what is on record now, and `admin-write/users.spec.js`,
    `games-reference.spec.js` and `others.spec.js` each assert an image survives
    an unrelated save. Worth remembering as a pattern when the next upload form
    is written.
