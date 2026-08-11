import { test, expect } from '../support/test.js';
import { FIXTURE } from '../support/fixture.js';
import { expectPageRenders, expectResourceLoads } from '../support/assertions.js';

test.describe('Links', () => {
  test('lists links', async ({ page }) => {
    const response = await page.goto('/links');

    await expectPageRenders(page, response, '/links');
    await expect(page.getByRole('heading', { name: 'Links', level: 1 })).toBeVisible();
    await expect(page.getByRole('heading', { name: FIXTURE.website.name })).toBeVisible();
  });

  test('filters links by category', async ({ page }) => {
    const response = await page.goto(`/links?category=${FIXTURE.websiteCategory.id}`);

    await expectPageRenders(page, response, '/links');
    await expect(page.getByRole('heading', { name: FIXTURE.website.name })).toBeVisible();
  });

  test('serves a link screenshot', async ({ page }) => {
    const path = `/websites/${FIXTURE.website.id}/screenshot.webp`;

    await expectResourceLoads(await page.request.get(path), path, {
      contentType: 'image/webp',
      magic: 'WEBP',
    });
  });

  // TODO: submitting a link as a signed-in user, and the dead-link flagging
  // that `artisan links:check` feeds.
});
