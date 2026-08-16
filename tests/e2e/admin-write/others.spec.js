import { test, expect } from '../support/test.js';
import { expectResourceLoads } from '../support/assertions.js';
import { PNG, uniqueName, acceptConfirms, deleteRow } from '../support/write.js';

/**
 * One row of a 'Did you know?' or 'Quotes' table, found by the text it holds.
 *
 * Both screens render every row as its own inline update form - a textarea and
 * an Update button - with a delete form beside it whose button title is the
 * same on every row. So the row has to be found by its content and the buttons
 * addressed inside it, or a click lands on somebody else's row: these two lists
 * are unfiltered and unpaginated, and the other write specs have rows in them
 * at the same time.
 *
 * hasText rather than a value selector, because a textarea keeps its value in
 * its text content and CSS `[value=...]` would never match one.
 */
function inlineRow(page, text) {
  return page.locator('tr').filter({ hasText: text });
}

/**
 * Add a row through the card above the table, edit it in place, and delete it.
 *
 * Trivia and quotes are the same screen twice over - same shape, same idiom,
 * different table - so they are the same test twice over, which is what this
 * takes.
 */
async function coversInlineTable(page, { index, deleteTitle, label }) {
  const text = uniqueName(label);
  // A name of its own rather than `${text} edited`, so that the row after the
  // edit no longer matches the text before it - otherwise the assertion that
  // the old text is gone is satisfied by the new row containing it.
  const edited = uniqueName(`${label} edited`);

  await page.goto(index);
  await page.fill('#text', text);
  await page.getByRole('button', { name: 'Add' }).click();

  await expect(page).toHaveURL(new RegExp(`${index}$`));
  await expect(inlineRow(page, text)).toHaveCount(1);

  // The edit. Filling the textarea changes nothing on the server until the
  // Update button beside it is pressed, so the assertion is on what comes back
  // after the redirect.
  const row = inlineRow(page, text);
  await row.locator('textarea').fill(edited);
  await row.getByRole('button', { name: 'Update' }).click();

  await expect(page).toHaveURL(new RegExp(`${index}$`));
  await expect(inlineRow(page, edited)).toHaveCount(1);
  await expect(inlineRow(page, text)).toHaveCount(0);

  acceptConfirms(page);
  await inlineRow(page, edited).locator(`button[title="${deleteTitle}"]`).click();

  await expect(page).toHaveURL(new RegExp(`${index}$`));
  await expect(inlineRow(page, edited)).toHaveCount(0);
}

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

  test('gives a spotlight an image and takes it away again', async ({ page }) => {
    const text = uniqueName('Spotlight');
    const image = page.getByRole('img', { name: 'Spotlight image' });

    acceptConfirms(page);

    // The image rides on the spotlight's own form - store() and update() both
    // hand off to addOrUpdateImage() - so it can go on at creation time.
    await page.goto('/admin/others/spotlights/create');
    await page.fill('#spotlight', text);
    await page.fill('#link', 'https://example.com/e2e');
    await page.locator('input[name="image"]').setInputFiles({
      name: 'spotlight.png',
      mimeType: 'image/png',
      buffer: PNG,
    });
    await page.getByRole('button', { name: 'Save' }).click();

    await expect(page).toHaveURL(/\/admin\/others\/spotlights$/);
    const edit = await page.getByRole('link', { name: text }).first().getAttribute('href');
    await page.goto(edit);

    await expect(image).toHaveAttribute('src', /images\/spotlight_screens\/\d+\.png$/);

    // And the file, fetched rather than left to the <img>: the guard in
    // support/test.js exempts /storage/ on purpose.
    const stored = new URL(await image.getAttribute('src')).pathname;
    await expectResourceLoads(await page.request.get(stored), stored, { magic: 'PNG' });

    // A save that is not about the image must leave the image alone - the trap
    // the user, individual and company forms all fell into.
    await page.fill('#link', 'https://example.com/e2e-edited');
    await page.getByRole('button', { name: 'Save' }).click();
    await page.goto(edit);
    await expect(image).toHaveAttribute('src', /images\/spotlight_screens\/\d+\.png$/);

    // Deleting the image comes back to the edit screen rather than the list,
    // so the placeholder is on the page it lands on.
    await page.locator('button[onclick*="delete-image"]').click();

    await expect(page).toHaveURL(new RegExp(`${edit}$`));
    await expect(image).toHaveAttribute('src', /images\/image-placeholder\.png$/);

    await page.goto('/admin/others/spotlights');
    await deleteRow(page, text);
  });

  test('adds, edits inline and deletes a trivia', async ({ page }) => {
    await coversInlineTable(page, {
      index: '/admin/others/trivias',
      deleteTitle: 'Delete trivia',
      label: 'Trivia',
    });
  });

  test('adds, edits inline and deletes a quote', async ({ page }) => {
    await coversInlineTable(page, {
      index: '/admin/others/quotes',
      deleteTitle: 'Delete quote',
      label: 'Quote',
    });
  });

  // TODO: the statistics screen's figures, which are read-only and belong in
  // admin/others.spec.js, and the changelog rows every write above leaves.
});
