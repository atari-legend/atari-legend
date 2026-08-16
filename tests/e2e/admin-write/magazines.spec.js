import { test, expect } from '../support/test.js';
import { uniqueName, createMagazine, deleteMagazine, deleteByAction } from '../support/write.js';

test.describe('Admin magazines', () => {
  test('creates and deletes a magazine', async ({ page }) => {
    const magazine = await createMagazine(page);

    await deleteMagazine(page, magazine);
  });

  test('creates and deletes an issue', async ({ page }) => {
    // Its own magazine rather than the seeded one: an issue is a card on its
    // magazine, which is a page the read specs open.
    const magazine = await createMagazine(page);
    const path = `/admin/magazines/magazines/${magazine.id}`;

    await page.goto(`${path}/issues/create`);
    await page.fill('#issue', '9999');
    await page.fill('#label', uniqueName('Issue'));
    // "Save", not "Save & Close": staying on the record is what puts its id in
    // the URL, and the issues table on the magazine has no searchable column
    // to find it by afterwards.
    await page.getByRole('button', { name: 'Save', exact: true }).click();

    await expect(page).toHaveURL(new RegExp(`${path}/issues/\\d+/edit$`));
    const issueId = page.url().split('/').at(-2);

    await page.goto(`${path}/edit`);
    await deleteByAction(page, `/issues/${issueId}`);

    await deleteMagazine(page, magazine);
  });

  // TODO: uploading an issue cover, and the magazine index entries that link
  // an article to the issue it appeared in.
});
