import { test, expect } from '../support/test.js';
import { FIXTURE } from '../support/fixture.js';
import { expectPageRenders } from '../support/assertions.js';

test.describe('News', () => {
  test('lists news', async ({ page }) => {
    const response = await page.goto('/news');

    await expectPageRenders(page, response, '/news');
    await expect(page.getByRole('heading', { name: FIXTURE.news.headline })).toBeVisible();
  });

  test('offers a guest no submission form', async ({ page }) => {
    await page.goto('/news');

    // The rendered half of the guest rejection tests/Feature covers at the
    // route. Submitting it as a signed-in user is public-write/news.spec.js.
    await expect(page.getByText('Please log in to submit a news item')).toBeVisible();
    await expect(page.locator('form[action$="/news/submit"]')).toHaveCount(0);
  });

  // News has no detail page - items are read in full on the index.
  //
  // TODO: the pagination.
});
