import { test, expect } from '../support/test.js';
import { uniqueName, deleteRow } from '../support/write.js';

test.describe('Admin links', () => {
  test('creates and deletes a link', async ({ page }) => {
    const name = uniqueName('Link');

    await page.goto('/admin/links/links/create');
    await page.fill('#name', name);
    await page.fill('#url', 'https://example.com/e2e');
    await page.getByRole('button', { name: /Save & Close/ }).click();

    await expect(page).toHaveURL(/\/admin\/links\/links$/);
    await deleteRow(page, name);
  });

  test('creates and deletes a category', async ({ page }) => {
    const name = uniqueName('Category');

    await page.goto('/admin/links/categories/create');
    await page.fill('#name', name);
    await page.getByRole('button', { name: 'Save' }).click();

    await page.goto('/admin/links/categories');
    await deleteRow(page, name);
  });

  // TODO: approving a link submitted from the public site, which is the only
  // part of this section a visitor can reach.
});
