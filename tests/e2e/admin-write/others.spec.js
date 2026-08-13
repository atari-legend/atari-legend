import { test, expect } from '../support/test.js';
import { uniqueName, deleteRow } from '../support/write.js';

test.describe('Admin others', () => {
  test('creates and deletes a spotlight', async ({ page }) => {
    const text = uniqueName('Spotlight');

    await page.goto('/admin/others/spotlights/create');
    await page.fill('#spotlight', text);
    await page.fill('#link', 'https://example.com/e2e');
    await page.getByRole('button', { name: 'Save' }).click();

    await page.goto('/admin/others/spotlights');
    await deleteRow(page, text);
  });

  // TODO: trivia and quotes, which are added and edited inline in their
  // Livewire table rather than through a form, so they need a different
  // helper from the create-page-then-save shape everything else here uses.
  // And the spotlight image, which goes through Filepond.
});
