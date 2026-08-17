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

  // Seven seeded items against a page size of six, dated backwards from the
  // headline above, so the oldest filler is the only thing on page two. The
  // assertion is on that split rather than on a count, because
  // public-write/news.spec.js can have an approved item in the table at the
  // same time - which moves rows between pages but never moves the oldest one
  // back onto page one.
  test('splits the list across pages', async ({ page }) => {
    const oldest = `${FIXTURE.news.fillerHeadline} ${FIXTURE.news.fillerCount}`;
    const response = await page.goto('/news');

    await expectPageRenders(page, response, '/news');
    await expect(page.getByRole('heading', { name: FIXTURE.news.headline })).toBeVisible();
    await expect(page.getByRole('heading', { name: oldest })).toHaveCount(0);
  });

  test('follows the pagination to the oldest item', async ({ page }) => {
    const oldest = `${FIXTURE.news.fillerHeadline} ${FIXTURE.news.fillerCount}`;
    const response = await page.goto('/news?page=2');

    await expectPageRenders(page, response, '/news');
    await expect(page.getByRole('heading', { name: oldest })).toBeVisible();

    // Only that the oldest item is here, and not that the newest is not:
    // public-write/news.spec.js publishes an item of its own while this runs,
    // and enough of those would push the seeded headline onto this page. The
    // oldest one cannot move the other way - a newer row only pushes it back.
  });
});
