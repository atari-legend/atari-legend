import { test, expect } from '../support/test.js';
import { FIXTURE } from '../support/fixture.js';
import { uniqueName, deleteRow, deleteByAction } from '../support/write.js';

test.describe('Admin magazines', () => {
  test('creates and deletes a magazine', async ({ page }) => {
    const name = uniqueName('Magazine');

    await page.goto('/admin/magazines/magazines/create');
    await page.fill('#name', name);
    await page.getByRole('button', { name: 'Save' }).click();

    await page.goto('/admin/magazines/magazines');
    await deleteRow(page, name);
  });

  test('creates and deletes an issue', async ({ page }) => {
    const magazine = `/admin/magazines/magazines/${FIXTURE.magazine.id}`;

    await page.goto(`${magazine}/issues/create`);
    await page.fill('#issue', '9999');
    await page.fill('#label', uniqueName('Issue'));
    // "Save", not "Save & Close": staying on the record is what puts its id in
    // the URL, and the issues table on the magazine has no searchable column
    // to find it by afterwards.
    await page.getByRole('button', { name: 'Save', exact: true }).click();

    await expect(page).toHaveURL(new RegExp(`${magazine}/issues/\\d+/edit$`));
    const issueId = page.url().split('/').at(-2);

    await page.goto(`${magazine}/edit`);
    await deleteByAction(page, `/issues/${issueId}`);
  });

  // TODO: uploading an issue cover, and the magazine index entries that link
  // an article to the issue it appeared in.
});
