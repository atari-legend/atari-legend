# End-to-end tests

Playwright specs that drive a real browser against a real server, so they catch
what the PHPUnit feature suite cannot: JavaScript errors, Livewire not booting,
images that 500, pages that render blank.

This is a skeleton with one or two tests per section, not a finished suite. Most
files here are small on purpose — the value is that there is an obvious place to
put the next test.

## Layout

```
tests/e2e/
├── auth.setup.js       signs in as admin, saves the session for the admin project
├── support/            shared modules; matched by no project, so never run as tests
│   ├── test.js         the `test` every spec imports
│   ├── assertions.js   expectPageRenders / expectResourceLoads
│   ├── fixture.js      the seeded ids and names
│   └── server.php      dev-server router (see playwright.config.js)
├── public/             the public site, one file per section
└── admin/              the admin panel, one file per section
```

**A spec's directory decides which project runs it**, and therefore whether it
has a session:

| Directory | Project | Session |
|---|---|---|
| `public/` | `public` | none — a clean guest |
| `admin/` | `admin` | signed in as admin, via `auth.setup.js` |

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
- **Everything is read-only.** `fullyParallel` is on and every worker shares one
  seeded database, so a spec that writes needs its own fixtures — see follow-up 4.

## Adding a section

1. Create `public/<section>.spec.js` or `admin/<section>.spec.js`.
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
  --entrypoint php artisan -S 0.0.0.0:8000 -t public tests/e2e/support/server.php

cd site && PLAYWRIGHT_TEST_BASE_URL=http://127.0.0.1:8123 npx playwright test
docker rm -f al-e2e-serve
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

Route coverage is not the goal — feature coverage is. What the suite checks
today, and what it does not:

| Section | Covered | Not yet |
|---|---|---|
| Home | page renders, nav links to each section | the cards (Screenstar, Who is it?, Latest menus, Trivia) |
| Games | list, detail, release, slug redirect, screenshot | advanced search, A-Z browse, voting, comments, gallery, similar games |
| News | list | submitting news, pagination |
| Reviews | list, detail | submit form, comments, scores, unpublished hidden |
| Interviews | list, detail | chapter hotspots, comments, screenshots |
| Articles | list, detail | type filter, comments, screenshots |
| Menu sets | list, detail, search, by-software | disk contents, dump downloads, condition filters, crews |
| Magazines | list, detail | issues, covers, archive.org links, the index |
| Links | list, category filter | submitting a link, dead-link flagging |
| Music | — | the SNDH player (ym2149-wasm), covers |
| Account | sign in, profile, review form, guests kept out | profile edit, password change, avatar, voting, commenting |
| Crawler | sitemaps, robots.txt, both feeds | that they list the right entities |
| Admin games | list, edit, 7 game panels, 5 release panels, issues, music, 4 reference sections, 20 config tables | creating and saving anything, changelog rows |
| Admin content | list/create/edit for news, reviews, interviews, articles | saving, image uploads, `<br />` normalisation |
| Admin menus | sets list, 4 edit forms, import screen | running an import, screenshot and dump uploads, crew relationships |
| Admin magazines | list, magazine and issue edit, index types | cover upload, index entries |
| Admin links | list, edit, categories | approving submissions |
| Admin users | list, edit, comments | permissions, deactivation, moderation |
| Admin others | trivia, quotes, spotlights, statistics, changelog, 3 autocompletes | statistics figures |

Four tests are `test.fixme()` rather than missing — see follow-up 3.

## Follow-ups

1. **The public `magazines` write routes had no auth middleware.** They sat in a
   group with only `verified` and `nondraft`, which is a no-op for a guest, and
   failed only because the controller methods were missing. Pruning the resource
   to `only(['index', 'show'])` closed that; do not re-add them without auth.
2. **`tests/Feature/RoutesTest.php` guards the whole class of bug** that pruning
   fixed: a `Route::resource()` without `only()`/`except()` registers actions the
   controller does not implement, and those answer 500 rather than 404.
3. **The docker dev image has PNG-only GD.** `../php.dockerfile` (parent repo)
   installs GD from `libpng-dev` alone — no WebP, no FreeType — so the box scan,
   avatar, spotlight, link screenshot, music cover and EPUB routes 500 there
   today, independently of these tests. Their specs are `test.fixme()` with the
   reason inline. Fix with `docker-php-ext-configure gd --with-freetype
   --with-jpeg --with-webp`, then un-fixme them.
4. **Mutating flows are untested end to end** — creating and editing content
   through the admin forms, voting, commenting. They cannot join this suite as it
   stands: `fullyParallel` is on and all workers share one seeded database. The
   shape to reach for is a serial project with per-test fixtures. Until then the
   `tests/Feature/Admin/*` PHPUnit suite covers them at the HTTP layer.
5. **`/music/{sndh}` proxies a live request to `sndhrecord.atari.org`.** Extract
   that host to config so the music spec can point it at a local fixture instead
   of depending on a third party.
6. **Subresource 404s are invisible.** A `page.on('response')` check for
   same-origin 404s would catch a missing `storage:link` and broken asset paths.
