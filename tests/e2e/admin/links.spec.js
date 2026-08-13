import { test, expect } from '../support/test.js';
import { FIXTURE } from '../support/fixture.js';
import { expectPageRenders } from '../support/assertions.js';

test.describe('Admin links', () => {
  test('lists links', async ({ page }) => {
    const response = await page.goto('/admin/links/links');

    await expectPageRenders(page, response, '/admin/links/links');
    await expect(page.getByText(FIXTURE.website.name).first()).toBeVisible();
  });

  test('opens the edit form for a link', async ({ page }) => {
    const path = `/admin/links/links/${FIXTURE.website.id}/edit`;

    await expectPageRenders(page, await page.goto(path), path);
  });

  test('lists link categories', async ({ page }) => {
    await expectPageRenders(page, await page.goto('/admin/links/categories'), '/admin/links/categories');
  });

  test('opens the edit form for a category', async ({ page }) => {
    const path = `/admin/links/categories/${FIXTURE.websiteCategory.id}/edit`;

    await expectPageRenders(page, await page.goto(path), path);
  });

  const createForms = [
    { name: 'a link', path: '/admin/links/links/create' },
    { name: 'a category', path: '/admin/links/categories/create' },
  ];

  for (const form of createForms) {
    test(`opens the create form for ${form.name}`, async ({ page }) => {
      await expectPageRenders(page, await page.goto(form.path), form.path);
    });
  }

  // TODO: approving a submitted link, and the dead-link report that
  // `artisan links:check` populates.
});
