import { expect } from '@playwright/test';

/**
 * Helpers for the admin-write project.
 *
 * These specs create their own rows rather than editing the seeded fixture,
 * so that a leaked row cannot change what a read spec sees. Everything here
 * exists to make that round-trip - create, find, delete - a one-liner.
 */

/**
 * A name no other row will have.
 *
 * The 'E2E ' prefix is what you grep the database for when a run is killed
 * halfway through and leaves something behind.
 */
export function uniqueName(label) {
  return `E2E ${label} ${Date.now()}`;
}

/**
 * Accept the confirm() every delete button is wrapped in.
 *
 * Playwright *dismisses* dialogs by default, which cancels the submit and
 * leaves the row in place - the test then fails several assertions later with
 * no hint as to why. Call this before clicking anything that deletes.
 */
const listeningPages = new WeakSet();

export function acceptConfirms(page) {
  if (!listeningPages.has(page)) {
    listeningPages.add(page);
    page.on('dialog', (dialog) => {
      dialog.accept().catch(() => {});
    });
  }
}

/**
 * Type into an autocomplete field and pick the first suggestion.
 *
 * Worth driving rather than filling the hidden input it feeds: the visible
 * field carries the name, the hidden one carries the id, and only the
 * autocomplete's onSelection puts the id there. A PHPUnit test posts the id
 * directly and passes whether or not any of that still works.
 */
export async function pickAutocomplete(page, inputId, term) {
  await page.fill(`#${inputId}`, term);

  const suggestion = page.locator('.autocomplete-results li', { hasText: term }).first();
  await suggestion.click();

  // The companion hidden field is what the controller actually reads.
  const companion = await page.locator(`#${inputId}`).getAttribute('data-autocomplete-companion');
  await expect(page.locator(`input[name="${companion}"]`)).not.toHaveValue('');
}

/**
 * Type into one of the BBCode editors that replace a textarea.sceditor.
 *
 * SCEditor hides the textarea behind a contenteditable iframe and copies the
 * content back on submit, so page.fill() on the textarea would post an empty
 * body. Reaching through the frame is also the only way to find out that the
 * editor booted at all.
 *
 * Several of these forms have three of them (intro, chapters, text), so the
 * textarea's id picks one: SCEditor inserts its container immediately before
 * the element it replaced.
 */
export async function fillEditor(page, textareaId, text) {
  const container = page
    .locator(`#${textareaId}`)
    .locator('xpath=preceding-sibling::div[contains(@class, "sceditor-container")][1]');

  const body = container.frameLocator('iframe').locator('body');

  await body.click();
  await body.fill(text);
}

/**
 * Every row of the Livewire table on screen that represents a record.
 *
 * Filtering on the delete form rather than counting <tr>s: an empty table
 * still renders one row, carrying the "no items found" message, and counting
 * that as a result would make a search that found nothing look like a hit.
 */
function tableRows(page) {
  return page.locator('table tbody tr').filter({ has: page.locator('form') });
}

/**
 * Filter a Livewire table and wait for it to hold the expected number of rows.
 *
 * The search box is bound with wire:model.live, so it filters on every
 * keystroke - but only once Livewire has attached its listener. These specs
 * arrive here straight after a form redirect, early enough that a value set
 * before that lands in the box and filters nothing. Hence the retry around
 * the typing rather than a bare wait: if the first attempt was too early, the
 * next one types into a booted component.
 *
 * The search box is the text input with a Search placeholder; the one in
 * the site nav is type="search" and belongs to the games search. We use
 * .first() to select the table search input if multiple search fields are present.
 */
async function searchTable(page, term, expectedRows) {
  const search = page.locator('input[type="text"][placeholder="Search"]').first();
  const rows = tableRows(page);

  await expect(async () => {
    await search.fill(term);
    await expect(rows).toHaveCount(expectedRows, { timeout: 3000 });
  }).toPass({ timeout: 15000 });

  return rows;
}

/**
 * Search a Livewire table for one record and return its row.
 *
 * The term has to be something the table's searchable() looks at, which is
 * not always something it displays - a review has no title, and is found by
 * its body text. So this asserts on the number of rows rather than on their
 * content, and the caller identifies the record by what it searched for.
 */
export async function findRow(page, term) {
  return searchTable(page, term, 1);
}

/**
 * Delete a record that is listed on its parent's page rather than in a table.
 *
 * Releases, menus and disks have no index of their own; they are shown as
 * cards on the record above them, with a plain delete form each. There is
 * nothing to search, so the form is identified by the id in its action.
 *
 * `/9` does not match a form ending `/19`, so the suffix is unambiguous.
 */
export async function deleteByAction(page, actionSuffix) {
  acceptConfirms(page);

  const form = page.locator(`form[action$="${actionSuffix}"]`);
  await expect(form).toHaveCount(1);
  await form.locator('button').click();

  await expect(page.locator(`form[action$="${actionSuffix}"]`)).toHaveCount(0);
}

/**
 * Delete a record from the Livewire table currently on screen.
 *
 * Assumes the caller is on the index page.
 */
export async function deleteRow(page, term) {
  acceptConfirms(page);

  const row = await findRow(page, term);
  await row.locator('form button').click();
  await page.waitForLoadState('domcontentloaded');

  // Deleting is a plain form POST, not a Livewire call, so the page reloads
  // with the table unfiltered. Search again rather than assuming the filter
  // survived - otherwise this passes on whatever happens to be on page one.
  await searchTable(page, term, 0);
}
