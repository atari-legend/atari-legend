import { test, expect } from '../support/test.js';
import { FIXTURE } from '../support/fixture.js';
import { expectPageRenders, expectResourceLoads } from '../support/assertions.js';
import { pickSuggestion } from '../support/autocomplete.js';

test.describe('Games', () => {
  test('lists games', async ({ page }) => {
    const response = await page.goto('/games');

    await expectPageRenders(page, response, '/games');
    await expect(page.getByRole('heading', { name: 'Games search' })).toBeVisible();
    await expect(page.getByRole('link', { name: FIXTURE.game.name }).first()).toBeVisible();
  });

  test('draws the updates chart', async ({ page }) => {
    await page.goto('/games');

    // chartjs-render-monitor is the class Chart.js puts on a canvas it has
    // attached to, so this is the difference between the canvas being in the
    // markup and something having been drawn in it.
    await expect(page.locator('#updates-chart.chartjs-render-monitor')).toBeVisible();
  });

  test('displays one game', async ({ page }) => {
    await page.goto('/games');

    await page.getByRole('link', { name: FIXTURE.game.name }).first().click();

    await expect(page).toHaveURL(new RegExp(`/games/${FIXTURE.game.slug}$`));
    await expect(page.getByRole('heading', { name: FIXTURE.game.name, level: 1 })).toBeVisible();
  });

  test('opens a game by its slug', async ({ page }) => {
    // The click-through above proves the list links correctly; this proves the
    // URL works on its own, which is how every inbound link arrives.
    const path = `/games/${FIXTURE.game.slug}`;
    const response = await page.goto(path);

    await expectPageRenders(page, response, path);
    await expect(page.getByRole('heading', { name: FIXTURE.game.name, level: 1 })).toBeVisible();
  });

  test('lists the magazines that covered a game', async ({ page }) => {
    // The other end of the magazine index: an index row entered against an
    // issue is what puts the issue on the game it is about.
    // tests/e2e/public/magazines.spec.js asserts on the same rows from the
    // magazine's side.
    await page.goto(`/games/${FIXTURE.game.slug}`);

    const card = page.locator('.card').filter({ hasText: 'Magazines' });
    await expect(card.getByRole('link', { name: new RegExp(FIXTURE.magazine.name) })).toBeVisible();
    await expect(card).toContainText(FIXTURE.magazineIndex.game.type);
    await expect(card).toContainText(FIXTURE.magazineIndex.game.score);
  });

  test('displays one release', async ({ page }) => {
    const response = await page.goto(`/games/release/${FIXTURE.release.id}`);

    await expectPageRenders(page, response, `/games/release/${FIXTURE.release.id}`);
    await expect(page.getByRole('heading', { name: FIXTURE.game.name, level: 1 })).toBeVisible();
  });

  test('redirects a numeric game id to its slug', async ({ page }) => {
    // The legacy /games/{id} URLs are still linked to from elsewhere on the
    // web, so the 301 is load-bearing rather than a nicety.
    const response = await page.request.get(`/games/${FIXTURE.game.id}`, { maxRedirects: 0 });

    expect(response.status()).toBe(301);
    expect(response.headers()['location']).toContain(`/games/${FIXTURE.game.slug}`);
  });

  test('serves a game screenshot', async ({ page }) => {
    const path = `/games/${FIXTURE.game.slug}/screenshot-${FIXTURE.game.screenshotId}.${FIXTURE.game.screenshotExt}`;

    await expectResourceLoads(await page.request.get(path), path, {
      contentType: 'image/png',
      magic: 'PNG',
    });
  });

  test('serves a release box scan', async ({ page }) => {
    const path = `/games/release/${FIXTURE.release.id}/boxscan-${FIXTURE.release.boxscanId}.webp`;

    await expectResourceLoads(await page.request.get(path), path, {
      contentType: 'image/webp',
      magic: 'WEBP',
    });
  });

  test('offers a guest none of the contribution forms', async ({ page }) => {
    await page.goto(`/games/${FIXTURE.game.slug}`);

    // Three routes guard themselves in the controller and hide themselves in
    // the view; this is the second half. Doing any of them as a signed-in user
    // is public-write/games.spec.js.
    await expect(page.getByText('Please log in to vote')).toBeVisible();
    await expect(page.getByText('Please log in to add your own comment')).toBeVisible();
    await expect(page.getByText('Please log in to submit info')).toBeVisible();

    for (const action of ['/vote', '/comment', '/submitInfo']) {
      await expect(page.locator(`form[action$="${action}"]`)).toHaveCount(0);
    }
  });

  // TODO: the screenshot gallery, similar games.
});

// /games/search is a second controller action with its own view, and its
// behaviour depends on which criteria were supplied rather than on the route.
// The cases below are the different things it can return.
//
// Every assertion is scoped to #results, the container holding the matches.
// The page also carries the Screenstar and latest-comment cards, which link to
// games too - an unscoped locator passes on those alone and would report a
// search that returns nothing as working.
test.describe('Games search', () => {
  test('lists the matches for a partial title', async ({ page }) => {
    const path = `/games/search?title=${encodeURIComponent('Xenon')}`;
    const response = await page.goto(path);

    await expectPageRenders(page, response, '/games/search');
    await expect(
      page.locator('#results').getByRole('link', { name: FIXTURE.game.name }).first()
    ).toBeVisible();
  });

  test('browses games by first letter', async ({ page }) => {
    const path = '/games/search?titleAZ=X';
    const response = await page.goto(path);

    await expectPageRenders(page, response, '/games/search');
    await expect(
      page.locator('#results').getByRole('link', { name: FIXTURE.game.name }).first()
    ).toBeVisible();
  });

  test('filters on a genre', async ({ page }) => {
    const path = `/games/search?genre=${encodeURIComponent(FIXTURE.genre.name)}`;
    const response = await page.goto(path);

    await expectPageRenders(page, response, '/games/search');
    await expect(
      page.locator('#results').getByRole('link', { name: FIXTURE.game.name }).first()
    ).toBeVisible();
  });

  test('filters on an engine', async ({ page }) => {
    const path = `/games/search?engine=${encodeURIComponent(FIXTURE.engine.name)}`;
    const response = await page.goto(path);

    await expectPageRenders(page, response, '/games/search');
    await expect(
      page.locator('#results').getByRole('link', { name: FIXTURE.game.name }).first()
    ).toBeVisible();
  });

  test('filters on a developer', async ({ page }) => {
    // Not the same field as the publisher above, and not the same table: a
    // developer is a game_developer row, a publisher hangs off a release. One
    // company is both here, which is the only reason a single fixture can
    // exercise both branches.
    const path = `/games/search?developer=${encodeURIComponent(FIXTURE.company.name)}`;
    const response = await page.goto(path);

    await expectPageRenders(page, response, '/games/search');
    await expect(
      page.locator('#results').getByRole('link', { name: FIXTURE.game.name }).first()
    ).toBeVisible();
  });

  test('filters on the genre, engine and developer ids at once', async ({ page }) => {
    // Each of those three fields has a second, id-valued parameter behind the
    // dropdown beside it, and the controller answers it in a branch of its
    // own - a `where('id', …)` rather than a `like`. Sent together so the
    // three constraints have to hold at the same time.
    const path = `/games/search?genre_id=${FIXTURE.genre.id}`
      + `&engine_id=${FIXTURE.engine.id}&developer_id=${FIXTURE.company.id}`;
    const response = await page.goto(path);

    await expectPageRenders(page, response, '/games/search');
    await expect(
      page.locator('#results').getByRole('link', { name: FIXTURE.game.name }).first()
    ).toBeVisible();
  });

  test('keeps a game that has a screenshot, a boxscan and a review', async ({ page }) => {
    // The checkbox row of the form. Each one is a has()/whereHas() down a
    // different relation, and the seeded game satisfies these three.
    const path = `/games/search?title=${encodeURIComponent(FIXTURE.game.akaName)}`
      + '&screenshot=1&boxscan=1&review=1';
    const response = await page.goto(path);

    await expectPageRenders(page, response, '/games/search');
    await expect(
      page.locator('#results').getByRole('link', { name: FIXTURE.game.name }).first()
    ).toBeVisible();
  });

  test('drops a game that has neither a dump nor a tune', async ({ page }) => {
    // The other half of the same row, and the half that proves the boxes
    // constrain anything: the seeded game has no media, no dump and no SNDH,
    // so a title that finds it on its own has to stop finding it here.
    for (const filter of ['download', 'music']) {
      const path = `/games/search?title=${encodeURIComponent(FIXTURE.game.akaName)}&${filter}=1`;
      const response = await page.goto(path);

      await expectPageRenders(page, response, '/games/search');
      await expect(page.getByText('No game found')).toBeVisible();
      await expect(
        page.locator('#results').getByRole('link', { name: FIXTURE.game.name })
      ).toHaveCount(0);
    }
  });

  test('redirects an exact title match to the game', async ({ page }) => {
    // A shortcut worth protecting: the nav search box posts straight here, so
    // typing a game's full name has to land on the game rather than on a
    // results page listing it alone.
    await page.goto(`/games/search?title=${encodeURIComponent(FIXTURE.game.name)}`);

    await expect(page).toHaveURL(new RegExp(`/games/${FIXTURE.game.slug}$`));
  });

  test('returns nothing when no criteria were given', async ({ page }) => {
    // Deliberate: with no constraints the controller forces an impossible
    // where() rather than paginating the whole table.
    const response = await page.goto('/games/search');

    await expectPageRenders(page, response, '/games/search');
    await expect(page.getByText('No game found')).toBeVisible();
    await expect(
      page.locator('#results').getByRole('link', { name: FIXTURE.game.name })
    ).toHaveCount(0);
  });
});

// The search form is the only consumer of six of the nine /ajax/*.json
// endpoints, so they are asserted here rather than as a sweep of their own.
//
// They are public - no session, no CSRF - and they take a user-supplied ?q=,
// which is what makes them worth more than a "does it render" check.
test.describe('Games search autocomplete', () => {
  const endpoints = [
    'games',
    'companies',
    'release-years',
    'genres',
    'engines',
    'individuals',
  ];

  for (const endpoint of endpoints) {
    test(`serves the ${endpoint} autocomplete`, async ({ page }) => {
      const response = await page.request.get(`/ajax/${endpoint}.json`);

      expect(response.status()).toBe(200);
      expect(response.headers()['content-type'] ?? '').toContain('application/json');
      // Parses, and is a collection rather than an error object.
      expect(Array.isArray(await response.json())).toBe(true);
    });
  }

  test('filters on the query string', async ({ page }) => {
    const response = await page.request.get(
      `/ajax/games.json?q=${encodeURIComponent(FIXTURE.game.name)}`
    );

    const names = (await response.json()).map((row) => row.name);
    expect(names).toContain(FIXTURE.game.name);
  });

  test('merges AKAs into the games it suggests, shortest match first', async ({ page }) => {
    // A game and its AKAs come from two tables and two queries, merged and
    // then ranked in PHP: earliest match, then shortest title. The fixture's
    // AKA matches at the same position as its game name and is shorter, so it
    // has to come first - which is only true if the ranking runs at all.
    const response = await page.request.get('/ajax/games.json?q=Xenon');

    const names = (await response.json()).map((row) => row.name);
    expect(names).toContain(FIXTURE.game.akaName);
    expect(names.indexOf(FIXTURE.game.akaName)).toBeLessThan(names.indexOf(FIXTURE.game.name));
  });

  test('treats a quote in the query as data', async ({ page }) => {
    // release-years builds its own SQL rather than going through the query
    // builder, and shipped an injection because of it. A quote has to come
    // back as an empty result, not as a 500 and not as the whole table.
    const response = await page.request.get("/ajax/release-years.json?q=1990'");

    expect(response.status()).toBe(200);
    expect(await response.json()).toEqual([]);
  });

  // The four tests below drive the widget itself rather than the endpoint
  // behind it. The title field carries data-autocomplete-submit, so picking a
  // suggestion submits the form; the others only fill a field, one of them
  // through a hidden companion input.
  test('submits the form when a title suggestion is picked', async ({ page }) => {
    await page.goto('/games');

    await pickSuggestion(page, '#title', FIXTURE.game.akaName);

    await expect(page).toHaveURL(/\/games\/search\?/);
    // The results page repopulates the field, so this is what was searched on.
    await expect(page.locator('#title')).toHaveValue(FIXTURE.game.akaName);
    await expect(
      page.locator('#results').getByRole('link', { name: FIXTURE.game.name }).first()
    ).toBeVisible();
  });

  test('goes straight to the game when its own title is picked', async ({ page }) => {
    // The other half of the same branch: the form is submitted the same way,
    // and the controller then redirects because the title is an exact match.
    await page.goto('/games');

    await pickSuggestion(page, '#title', FIXTURE.game.name);

    await expect(page).toHaveURL(new RegExp(`/games/${FIXTURE.game.slug}$`));
  });

  test('searches on a release year picked from the suggestions', async ({ page }) => {
    await page.goto('/games');

    await pickSuggestion(page, '#year', FIXTURE.release.year);
    await expect(page.locator('#year')).toHaveValue(FIXTURE.release.year);

    await page.getByRole('button', { name: 'Search' }).click();

    await expect(
      page.locator('#results').getByRole('link', { name: FIXTURE.game.name }).first()
    ).toBeVisible();
  });

  test('searches on an individual picked from the suggestions', async ({ page }) => {
    await page.goto('/games');

    await pickSuggestion(page, '#individual', FIXTURE.individual.name);

    // This endpoint decorates a name with the games that person worked on, and
    // the field shows what was picked - so the decoration is what lands in it.
    await expect(page.locator('#individual')).toHaveValue(new RegExp(FIXTURE.game.name));
    // Only the id is searched on, and only onSelection puts it there.
    await expect(page.locator('input[name="individual_select"]')).toHaveValue(
      String(FIXTURE.individual.id)
    );

    await page.getByRole('button', { name: 'Search' }).click();

    await expect(
      page.locator('#results').getByRole('link', { name: FIXTURE.game.name }).first()
    ).toBeVisible();
  });

  test('searches on a publisher picked from the suggestions', async ({ page }) => {
    // No companion id on this one: the controller searches on the name, so
    // filling the visible field is the whole of what onSelection has to do.
    await page.goto('/games');

    await pickSuggestion(page, '#publisher', FIXTURE.company.name);
    await expect(page.locator('#publisher')).toHaveValue(FIXTURE.company.name);

    await page.getByRole('button', { name: 'Search' }).click();

    await expect(
      page.locator('#results').getByRole('link', { name: FIXTURE.game.name }).first()
    ).toBeVisible();
  });

  test('searches on a developer picked from the suggestions', async ({ page }) => {
    // Same endpoint as the publisher field and the same shape of onSelection,
    // but a different parameter and a different join behind it - the two
    // fields are only interchangeable because one company happens to be both.
    await page.goto('/games');

    await pickSuggestion(page, '#developer', FIXTURE.company.name);
    await expect(page.locator('#developer')).toHaveValue(FIXTURE.company.name);

    await page.getByRole('button', { name: 'Search' }).click();

    await expect(
      page.locator('#results').getByRole('link', { name: FIXTURE.game.name }).first()
    ).toBeVisible();
  });

  test('searches on an engine picked from the suggestions', async ({ page }) => {
    await page.goto('/games');

    await pickSuggestion(page, '#engine', FIXTURE.engine.name);
    await expect(page.locator('#engine')).toHaveValue(FIXTURE.engine.name);

    await page.getByRole('button', { name: 'Search' }).click();

    await expect(
      page.locator('#results').getByRole('link', { name: FIXTURE.game.name }).first()
    ).toBeVisible();
  });
});

// The rest of the search form: the two controls that are not autocompletes.
//
// Both are driven through the browser rather than through the query string,
// because both depend on JavaScript - the dropdown on resources/js/game/search.js
// and the checkboxes on nothing at all, which is exactly what makes the pair
// worth asserting side by side.
test.describe('Games search form', () => {
  test('swaps the genre field for a dropdown and searches on the id', async ({ page }) => {
    // The chevron beside the Genre label toggles d-none on both the
    // autocomplete and the <select> of every genre. They post different
    // parameters - genre and genre_id - so which one is visible decides which
    // branch of the controller runs.
    await page.goto('/games');

    await expect(page.locator('#genre_id')).toBeHidden();
    await page.locator('[data-dropdown-toggle="genre,genre_id"]').click();
    await expect(page.locator('#genre')).toBeHidden();
    await expect(page.locator('#genre_id')).toBeVisible();

    await page.locator('#genre_id').selectOption({ label: FIXTURE.genre.name });
    await page.getByRole('button', { name: 'Search' }).click();

    await expect(page).toHaveURL(new RegExp(`genre_id=${FIXTURE.genre.id}(&|$)`));
    await expect(
      page.locator('#results').getByRole('link', { name: FIXTURE.game.name }).first()
    ).toBeVisible();
  });

  test('searches on the checkboxes as rendered', async ({ page }) => {
    // The query-string tests above prove the controller; this proves the boxes
    // in the markup are wired to those parameters at all.
    await page.goto('/games');

    await page.locator('#title').fill(FIXTURE.game.akaName);
    await page.getByRole('checkbox', { name: 'Screenshot' }).check();
    await page.getByRole('checkbox', { name: 'Review' }).check();
    await page.getByRole('button', { name: 'Search' }).click();

    await expect(page).toHaveURL(/screenshot=on/);
    await expect(page).toHaveURL(/review=on/);
    await expect(
      page.locator('#results').getByRole('link', { name: FIXTURE.game.name }).first()
    ).toBeVisible();
  });
});
