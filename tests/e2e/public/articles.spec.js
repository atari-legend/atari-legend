import { test, expect } from '../support/test.js';
import { FIXTURE } from '../support/fixture.js';
import { expectPageRenders, expectResourceLoads } from '../support/assertions.js';

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

  // The type is a badge on the list and nothing more: there is no filter by
  // type anywhere in the application - ArticleController::index takes no
  // Request at all - which is worth stating, because the coverage checklist
  // used to promise one.
  test('badges an article with its type', async ({ page }) => {
    await page.goto('/articles');

    await expect(page.getByText(FIXTURE.article.type).first()).toBeVisible();
  });

  test('shows a screenshot with its caption', async ({ page }) => {
    await page.goto(`/articles/${FIXTURE.article.id}`);

    const screenshot = page.getByRole('img', { name: FIXTURE.article.screenshotCaption });
    await expect(screenshot).toBeVisible();

    // A storage URL rather than a route, so the file is fetched here: the
    // guard in support/test.js exempts /storage/ on purpose.
    const path = new URL(await screenshot.getAttribute('src')).pathname;
    expect(path).toContain(`/article_screenshots/${FIXTURE.article.screenshotId}.`);
    await expectResourceLoads(await page.request.get(path), path, { magic: 'PNG' });

    await expect(page.getByText(FIXTURE.article.screenshotCaption).last()).toBeVisible();
  });

  // Commenting on an article is public-write/content.spec.js.
});
