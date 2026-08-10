import { test, expect } from '../support/test.js';
import { FIXTURE } from '../support/fixture.js';
import { expectPageRenders } from '../support/assertions.js';

test.describe('News', () => {
  test('lists news', async ({ page }) => {
    const response = await page.goto('/news');

    await expectPageRenders(page, response, '/news');
    await expect(page.getByRole('heading', { name: FIXTURE.news.headline })).toBeVisible();
  });

  // News has no detail page - items are read in full on the index.
  //
  // TODO: submitting news as a signed-in user, and the pagination.
});
