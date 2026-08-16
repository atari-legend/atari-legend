import { test, expect } from '../support/test.js';
import { FIXTURE } from '../support/fixture.js';
import { expectPageRenders, expectValues } from '../support/assertions.js';

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

  test('renders the index editor on an issue', async ({ page }) => {
    const path = `/admin/magazines/magazines/${FIXTURE.magazine.id}/issues/${FIXTURE.magazineIssue.id}/edit`;
    const index = FIXTURE.magazineIndex;

    await page.goto(path);

    const editor = page.locator('.magazine-index-editor');
    await expect(editor).toBeVisible();
    await expect(editor.locator('tbody tr')).toHaveCount(4);
    await expect(editor.getByRole('button', { name: 'Add row' })).toBeVisible();
    await expect(editor.getByRole('button', { name: 'Save', exact: true })).toBeVisible();
    await expect(page.locator('#autosort')).toBeVisible();

    // The seeded rows arrive in their fields rather than as text, so the
    // assertion is on the values the component bound. Unsorted, the editor
    // shows them in the order they are stored, which is not page order.
    const pageNumbers = editor.locator('tbody tr td:first-child input');
    await expectValues(pageNumbers, ['12', '30', '44', '3']);
    await expect(page.locator(`input[name="${index.game.id}_game_id"]`))
      .toHaveValue(String(FIXTURE.game.id));

    // A component that never booted renders the same markup minus the
    // behaviour, so drive one control. Auto-sort is the only one that writes
    // nothing: it reorders what is on screen and leaves the rows alone.
    await page.locator('#autosort').check();
    await expectValues(pageNumbers, ['3', '12', '30', '44']);
  });

  const createForms = [
    { name: 'a magazine', path: '/admin/magazines/magazines/create' },
    { name: 'an issue', path: `/admin/magazines/magazines/${FIXTURE.magazine.id}/issues/create` },
  ];

  for (const form of createForms) {
    test(`opens the create form for ${form.name}`, async ({ page }) => {
      await expectPageRenders(page, await page.goto(form.path), form.path);
    });
  }

  test('lists the index types', async ({ page }) => {
    await expectPageRenders(page, await page.goto('/admin/magazines/index-types'), '/admin/magazines/index-types');
  });

  // TODO: uploading an issue cover, and the magazine index entries that link
  // an article to the issue it appeared in.
});
