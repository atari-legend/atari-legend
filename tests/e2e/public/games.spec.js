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

  test('displays one game', async ({ page }) => {
    await page.goto('/games');

    await page.getByRole('link', { name: FIXTURE.game.name }).first().click();

    await expect(page).toHaveURL(new RegExp(`/games/${FIXTURE.game.slug}$`));
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

  // TODO: advanced search (genre, year, publisher filters), the A-Z browse,
  // voting, commenting, the screenshot gallery, similar games.
});
