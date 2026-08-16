import { test, expect } from '../support/test.js';
import { FIXTURE } from '../support/fixture.js';
import { expectPageRenders, expectResourceLoads } from '../support/assertions.js';

test.describe('Menu sets', () => {
  test('lists menu sets', async ({ page }) => {
    const response = await page.goto('/menusets');

    await expectPageRenders(page, response, '/menusets');
    await expect(page.getByRole('heading', { name: 'Game menus', level: 1 })).toBeVisible();
    await expect(page.getByRole('link', { name: FIXTURE.menuSet.name }).first()).toBeVisible();
  });

  test('displays one menu set', async ({ page }) => {
    await page.goto('/menusets');

    await page.getByRole('link', { name: FIXTURE.menuSet.name }).first().click();

    await expect(page).toHaveURL(new RegExp(`/menusets/${FIXTURE.menuSet.id}$`));
    await expect(page.getByRole('heading', { name: new RegExp(FIXTURE.menuSet.name), level: 1 }))
      .toBeVisible();
  });

  test('searches menu sets', async ({ page }) => {
    const response = await page.goto(`/menusets/search?search=${encodeURIComponent(FIXTURE.game.name)}`);

    await expectPageRenders(page, response, '/menusets/search');
  });

  test('lists the menus containing a piece of software', async ({ page }) => {
    const path = `/menusets/software/${FIXTURE.menuSoftware.id}`;
    const response = await page.goto(path);

    await expectPageRenders(page, response, path);
  });

  test('exports the scrolltexts as an EPUB', async ({ page }) => {
    const path = `/menusets/${FIXTURE.menuSet.id}/scrolltexts.epub`;

    await expectResourceLoads(await page.request.get(path), path, { magic: 'PK' });
  });

  // TODO: the disk contents listing, the dump downloads, the condition
  // filters, and the crew pages behind a menu.
});

// Two of the /ajax/*.json endpoints serve this section's data. Both are public
// and take a user-supplied ?q=, but the fields that call them are on admin
// forms - the magazine index, a disk's content, a crew's genealogy - so what
// is asserted here is the endpoint rather than the widget.
test.describe('Menu autocompletes', () => {
  const endpoints = [
    { name: 'software', term: FIXTURE.menuSoftware.name, key: 'name' },
    { name: 'crews', term: FIXTURE.crew.name, key: 'crew_name' },
  ];

  for (const endpoint of endpoints) {
    test(`serves the ${endpoint.name} autocomplete`, async ({ page }) => {
      const response = await page.request.get(`/ajax/${endpoint.name}.json`);

      expect(response.status()).toBe(200);
      expect(response.headers()['content-type'] ?? '').toContain('application/json');
      // Parses, and is a collection rather than an error object.
      expect(Array.isArray(await response.json())).toBe(true);
    });

    test(`filters the ${endpoint.name} autocomplete on the query string`, async ({ page }) => {
      const response = await page.request.get(
        `/ajax/${endpoint.name}.json?q=${encodeURIComponent(endpoint.term)}`
      );

      expect((await response.json()).map((row) => row[endpoint.key])).toContain(endpoint.term);
    });
  }
});
