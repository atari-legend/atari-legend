import { test, expect } from '../support/test.js';
import { FIXTURE } from '../support/fixture.js';
import { expectPageRenders, expectResourceLoads } from '../support/assertions.js';

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

  // TODO: the remaining search filters (genre, engine, publisher, developer,
  // and the has-review/has-download checkboxes), voting, commenting, the
  // screenshot gallery, similar games.
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
