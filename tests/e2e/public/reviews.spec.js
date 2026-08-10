import { test, expect } from '../support/test.js';
import { FIXTURE } from '../support/fixture.js';
import { expectPageRenders } from '../support/assertions.js';

test.describe('Reviews', () => {
  test('lists reviews', async ({ page }) => {
    const response = await page.goto('/reviews');

    await expectPageRenders(page, response, '/reviews');
    await expect(page.getByRole('heading', { name: 'Reviews', level: 1 })).toBeVisible();
    // A review is titled after the game it reviews.
    await expect(page.getByRole('link', { name: FIXTURE.game.name }).first()).toBeVisible();
  });

  test('displays one review', async ({ page }) => {
    const response = await page.goto(`/reviews/${FIXTURE.review.id}`);

    await expectPageRenders(page, response, `/reviews/${FIXTURE.review.id}`);
    await expect(page.getByRole('heading', { name: FIXTURE.game.name, level: 1 })).toBeVisible();
  });

  // TODO: the submit-a-review form, commenting, the scores breakdown,
  // and that an unpublished review stays hidden from the index.
});
