import { test, expect } from '../support/test.js';
import { FIXTURE } from '../support/fixture.js';
import { expectPageRenders } from '../support/assertions.js';

test.describe('Admin magazines', () => {
  test('lists magazines', async ({ page }) => {
    const response = await page.goto('/admin/magazines/magazines');

    await expectPageRenders(page, response, '/admin/magazines/magazines');
    await expect(page.getByText(FIXTURE.magazine.name).first()).toBeVisible();
  });

  test('opens the edit form for a magazine', async ({ page }) => {
    const path = `/admin/magazines/magazines/${FIXTURE.magazine.id}/edit`;

    await expectPageRenders(page, await page.goto(path), path);
  });

  test('opens the edit form for an issue', async ({ page }) => {
    // Issues are edited from their magazine, so they have no index route.
    const path = `/admin/magazines/magazines/${FIXTURE.magazine.id}/issues/${FIXTURE.magazineIssue.id}/edit`;

    await expectPageRenders(page, await page.goto(path), path);
  });

  test('lists the index types', async ({ page }) => {
    await expectPageRenders(page, await page.goto('/admin/magazines/index-types'), '/admin/magazines/index-types');
  });

  // TODO: uploading an issue cover, and the magazine index entries that link
  // an article to the issue it appeared in.
});
