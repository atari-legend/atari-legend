import { test, expect } from '../support/test.js';
import { FIXTURE } from '../support/fixture.js';

// The /ajax/*.json endpoints behind the site's autocomplete fields. They are
// public - no session, no CSRF - and they take a user-supplied ?q=, which is
// why they are worth a spec of their own rather than being left to whichever
// form happens to call them.

const endpoints = [
  'companies',
  'release-years',
  'genres',
  'engines',
  'games',
  'games-and-software',
  'software',
  'individuals',
  'crews',
];

test.describe('Public autocomplete endpoints', () => {
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

    const names = (await response.json()).map((row) => row.game_name);
    expect(names).toContain(FIXTURE.game.name);
  });

  test('treats a quote in the query as data', async ({ page }) => {
    // release-years builds its own SQL rather than going through the query
    // builder, and shipped an injection because of it. A quote has to come
    // back as an empty result, not as a 500 and not as the whole table.
    const response = await page.request.get("/ajax/release-years.json?q=1990'");

    expect(response.status()).toBe(200);
    expect(await response.json()).toEqual([]);
  });
});
