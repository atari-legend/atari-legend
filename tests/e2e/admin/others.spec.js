import { test, expect } from '../support/test.js';
import { FIXTURE } from '../support/fixture.js';
import { expectPageRenders } from '../support/assertions.js';

test.describe('Admin others', () => {
  // Trivia and quotes are edited inline in their table, so they have no
  // create or edit route - hence only an index each.
  const indexes = [
    { name: 'trivia', path: '/admin/others/trivias' },
    { name: 'quotes', path: '/admin/others/quotes' },
    { name: 'spotlights', path: '/admin/others/spotlights' },
    { name: 'the statistics', path: '/admin/others/statistics' },
    { name: 'the changelog', path: '/admin/others/changelog' },
  ];

  for (const index of indexes) {
    test(`lists ${index.name}`, async ({ page }) => {
      await expectPageRenders(page, await page.goto(index.path), index.path);
    });
  }

  test('opens the edit form for a spotlight', async ({ page }) => {
    const path = `/admin/others/spotlights/${FIXTURE.spotlight.id}/edit`;

    await expectPageRenders(page, await page.goto(path), path);
  });

  // TODO: the statistics page runs the heaviest queries in the admin and is
  // the most likely to regress on a schema change - it deserves assertions on
  // the numbers, not just that it renders.
});

test.describe('Admin autocomplete endpoints', () => {
  const endpoints = ['games', 'users', 'sndh'];

  for (const endpoint of endpoints) {
    test(`serves the ${endpoint} autocomplete`, async ({ page }) => {
      const response = await page.request.get(`/admin/ajax/${endpoint}.json`);

      expect(response.status()).toBe(200);
      expect(response.headers()['content-type'] ?? '').toContain('application/json');
      // Parses, and is a collection rather than an error object.
      expect(Array.isArray(await response.json())).toBe(true);
    });
  }
});
