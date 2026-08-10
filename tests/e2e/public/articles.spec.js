import { test, expect } from '../support/test.js';
import { FIXTURE } from '../support/fixture.js';
import { expectPageRenders } from '../support/assertions.js';

test.describe('Articles', () => {
  test('lists articles', async ({ page }) => {
    const response = await page.goto('/articles');

    await expectPageRenders(page, response, '/articles');
    await expect(page.getByRole('heading', { name: FIXTURE.article.title })).toBeVisible();
  });

  test('displays one article', async ({ page }) => {
    await page.goto('/articles');

    await page.getByRole('link', { name: FIXTURE.article.title }).first().click();

    await expect(page).toHaveURL(new RegExp(`/articles/${FIXTURE.article.id}$`));
    await expect(page.getByRole('heading', { name: FIXTURE.article.title, level: 1 })).toBeVisible();
  });

  // TODO: filtering by article type, commenting, the article screenshots.
});
