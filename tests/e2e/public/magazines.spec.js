import { test, expect } from '../support/test.js';
import { FIXTURE } from '../support/fixture.js';
import { expectPageRenders } from '../support/assertions.js';

test.describe('Magazines', () => {
  test('lists magazines', async ({ page }) => {
    const response = await page.goto('/magazines');

    await expectPageRenders(page, response, '/magazines');
    await expect(page.getByRole('heading', { name: 'Magazines', level: 1 })).toBeVisible();
    await expect(page.getByRole('link', { name: FIXTURE.magazine.name }).first()).toBeVisible();
  });

  test('displays one magazine', async ({ page }) => {
    await page.goto('/magazines');

    await page.getByRole('link', { name: FIXTURE.magazine.name }).first().click();

    await expect(page).toHaveURL(new RegExp(`/magazines/${FIXTURE.magazine.id}$`));
    await expect(page.getByRole('heading', { name: FIXTURE.magazine.name, level: 1 })).toBeVisible();
  });

  // TODO: the issue list and its covers, the archive.org links, and the
  // magazine index (which articles appeared in which issue).
  //
  // TODO: the page-count chart, which the equivalent test in public/games.spec.js
  // covers for /games. card_page_count.blade.php renders only above four issues
  // and E2ESeeder seeds one, so this needs five seeded before it can assert
  // anything.
});
