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
│   ├── test.js         the `test` every spec imports
│   ├── assertions.js   expectPageRenders / expectResourceLoads
│   ├── auth.js         signIn / signOut through the real forms
│   ├── write.js        helpers the admin-write project needs
│   ├── fixture.js      the seeded ids and names
│   └── server.php      dev-server router (see playwright.config.js)
├── public/             the public site, one file per section
├── admin/              the admin panel, read-only, one file per section
└── admin-write/        the admin panel, creating and deleting
```

**A spec's directory decides which project runs it**, and therefore whether it
has a session and whether it may write:

| Directory | Project | Session | Writes |
|---|---|---|---|
| `public/` | `public` | none — a clean guest | no |
| `admin/` | `admin` | signed in as admin, via `auth.setup.js` | no |
| `admin-write/` | `admin-write` | signed in as admin | yes — see below |

A spec anywhere else is silently skipped. After adding one, check it was picked
up:

```bash
npx playwright test --list
```

Pages that need a signed-in *non-admin* — `/profile`, `/reviews/submit` — live in
`public/account.spec.js` and sign in for themselves, because what is worth
testing there is what an ordinary account can do.

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
  the `page` fixture so an uncaught JavaScript exception fails the test without
  every spec wiring up a listener.
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
- **`public/` and `admin/` are read-only.** `fullyParallel` is on there and every
  worker shares one seeded database. Anything that writes goes in `admin-write/`.

## Writing specs that write

`admin-write/` is a fourth project: serial (`workers: 1`, `fullyParallel: false`)
and dependent on `public` and `admin`, so it runs once nothing else is reading.
Its rules, which is what makes running the suite twice against one database safe:

- **Create your own row, never edit a seeded one.** `uniqueName('News')` gives you
  an `E2E News <timestamp>` no other row will have — and something greppable in
  the database if a run is killed halfway through.
- **Delete it again in the same test.** `deleteRow(page, term)` for a Livewire
  table, `deleteByAction(page, '/releases/42')` for the cards that stand in for a
  table on releases, menus, disks and issues.
- **A games row is only deletable while nothing references it.** The button is
  rendered either way but disabled once the game has releases, screenshots or
  credits, so `deleteRow` on a seeded game times out on the actionability check
  rather than failing outright. Create your own game, as the spec does.
- **Every delete button is wrapped in `confirm()`, and Playwright dismisses
  dialogs by default** — which cancels the submit and fails the test somewhere
  else entirely. `deleteRow` and `deleteByAction` accept it for you;
  `acceptConfirms(page)` if you are clicking one yourself.
- **Drive the JavaScript rather than the field it writes to.** The hidden `author`
  / `game` / `individual` inputs are filled by the autocomplete's `onSelection`,
  and the body of every content form is posted by SCEditor, not by the textarea in
  the markup — so use `pickAutocomplete()` and `fillEditor()`. Filling the hidden
  input directly is exactly what the PHPUnit suite already does, and passes
  whether or not any of it still works.
- **Do not re-assert the controller.** Validation rules and changelog rows belong
  to `tests/Feature/Admin`. What is only testable here is that the rendered form
  submits at all.

## Adding a section

1. Create `public/<section>.spec.js`, `admin/<section>.spec.js` or
   `admin-write/<section>.spec.js`.
2. Seed anything it needs in `database/seeders/E2ESeeder.php`, with an explicit
   primary key exposed as a constant, and mirror it in `support/fixture.js`.
3. Write "lists X" and "displays one X", then a `TODO` for the rest.
4. `npx playwright test --list` to confirm the right project picked it up.

## Running

The app has to be served against the E2E database, and the fixture has to be
seeded into it first. CI does this with host `php` and MySQL as a service; the
recipe below does it through Docker Compose, which is how this project is
normally driven.

```bash
# One-off: an empty database for the fixture.
docker compose exec mysql mysql -uroot -patarilegend \
  -e 'CREATE DATABASE IF NOT EXISTS atarilegend_e2e;'

cd /path/to/atari-legend
E2E="-e DB_CONNECTION=mysql -e DB_HOST=mysql -e DB_DATABASE=atarilegend_e2e \
     -e DB_USERNAME=root -e DB_PASSWORD=atarilegend -e APP_ENV=testing \
     -e APP_KEY=base64:AckfSECXIvnK5r28GVIWUAxmbBSjTsmF/0rZh1cyWXk="

docker compose run --rm $E2E artisan migrate:fresh --force
docker compose run --rm $E2E artisan db:seed --class=E2ESeeder --force
docker compose run --rm npm run build      # @vite needs public/build/manifest.json

docker compose run -d --name al-e2e-serve -p 8123:8000 $E2E \
  -e APP_URL=http://127.0.0.1:8123 -e PHP_CLI_SERVER_WORKERS=16 \
  --entrypoint php site -S 0.0.0.0:8000 -t public tests/e2e/support/server.php

cd site && PLAYWRIGHT_TEST_BASE_URL=http://127.0.0.1:8123 npx playwright test
docker rm -f al-e2e-serve
```

Three things that will bite otherwise:

- **Serve from the `site` service, not `artisan`.** Both can run the router, but
  they are different images: `site` is `httpd.dockerfile`, which is what actually
  serves this application and builds GD with JPEG, WebP and FreeType. `artisan`
  is `php.dockerfile`, an Alpine image shared with the legacy CPANEL whose GD is
  PNG-only, so every route that re-encodes an image 500s under it.
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

Route coverage is not the goal — feature coverage is. What the suite checks
today, and what it does not:

| Section | Covered | Not yet |
|---|---|---|
| Home | page renders, nav links to each section, spotlight image | the cards (Screenstar, Who is it?, Latest menus, Trivia) |
| Games | list, detail by slug, release, slug redirect, screenshot, box scan | voting, comments, gallery, similar games |
| Games search | title, A-Z browse, exact-match redirect, empty state | genre, engine, publisher, developer and checkbox filters; the export view |
| Autocomplete | all 9 public endpoints, `?q=` filtering, a quote as data | ranking, the follow-URL behaviour of the nav box |
| News | list | submitting news, pagination |
| Reviews | list, detail | submit form, comments, scores, unpublished hidden |
| Interviews | list, detail, individual avatar | chapter hotspots, comments, screenshots |
| Articles | list, detail | type filter, comments, screenshots |
| Menu sets | list, detail, search, by-software, EPUB export | disk contents, dump downloads, condition filters, crews |
| Magazines | list, detail | issues, covers, archive.org links, the index |
| Links | list, category filter, screenshot | submitting a link, dead-link flagging |
| Music | cover image | the SNDH player (ym2149-wasm), the sndhrecord.atari.org proxy |
| Account | sign in, sign out, profile, review form, password confirm, guests kept out, unverified redirect | profile edit, password change, avatar, voting, commenting |
| Crawler | sitemaps, robots.txt, both feeds, health check | that they list the right entities |
| Admin games | list, create and edit forms, 7 game panels, 5 release panels, fact create/edit, issues, music, 4 reference sections + their create forms, 20 config tables | — |
| Admin content | list/create/edit for news, reviews, interviews, articles | image uploads, `<br />` normalisation |
| Admin menus | sets list, 4 edit forms, 6 create forms and their bare-URL 404s, 3 disk-content types, import screen and template | running an import, screenshot and dump uploads, crew relationships |
| Admin magazines | list, magazine and issue create/edit, index types | cover upload, index entries |
| Admin links | list, create and edit, categories | approving submissions |
| Admin users | list, edit, comments | permissions, deactivation, moderation |
| Admin others | trivia, quotes, spotlights + create/edit, statistics, changelog, 3 autocompletes | statistics figures |
| **Writes** | news, reviews, interviews, articles, game, game AKA, release, menu set, menu, disk, magazine, issue, link, category, spotlight — each created and deleted through its form | trivia and quotes (inline tables), every Filepond upload |

## Follow-ups

1. **The public `magazines` write routes had no auth middleware.** They sat in a
   group with only `verified` and `nondraft`, which is a no-op for a guest, and
   failed only because the controller methods were missing. Pruning the resource
   to `only(['index', 'show'])` closed that; do not re-add them without auth.
2. **`tests/Feature/RoutesTest.php` guards the whole class of bug** that pruning
   fixed: a `Route::resource()` without `only()`/`except()` registers actions the
   controller does not implement, and those answer 500 rather than 404.
3. **A missing SCEditor script takes out the admin's JavaScript (#272).**
   `admin/others.spec.js` failed once with an uncaught `Cannot read properties of
   undefined (reading 'set')` — `resources/js/admin/sceditor.js` reaching for a
   global that `formats/bbcode.js` had not set. It has not recurred, and the
   trigger looks environmental, but the unguarded globals are real. Retries are
   off on purpose, so if it comes back it will be visible rather than hidden.
4. **Mutating flows on the *public* side are still untested** — voting,
   commenting, submitting news, links and reviews. `admin-write/` is the shape to
   copy: a sibling `public-write/` project, serial, depending on the read ones.
5. **`/music/{sndh}` proxies a live request to `sndhrecord.atari.org`.** Extract
   that host to config so the music spec can point it at a local fixture instead
   of depending on a third party.
6. **Subresource 404s are invisible.** A `page.on('response')` check for
   same-origin 404s would catch a missing `storage:link` and broken asset paths.
   Follow-up 4 is the case for it: a script that never arrived is exactly what
   this would have caught at the time.
