import { expect } from '@playwright/test';

/**
 * Helpers for the BBCode editors that replace a textarea.sceditor.
 *
 * SCEditor hides the textarea behind a contenteditable iframe and copies the
 * content back on submit, so nothing here can go through the textarea in the
 * markup: page.fill() on it would post an empty body, and reading it back
 * would tell you what the page was served rather than what the editor holds.
 * Everything below reaches through the container SCEditor built instead, which
 * is also the only way to find out that it booted at all.
 *
 * The custom commands and BBCodes these drive are defined in
 * resources/js/admin/sceditor.js.
 */

/**
 * The editor that replaced a given textarea.
 *
 * Several forms have three of them (intro, chapters, text), so the textarea's
 * id picks one: SCEditor inserts its container immediately before the element
 * it replaced.
 */
export function editorFor(page, textareaId) {
  return page
    .locator(`#${textareaId}`)
    .locator('xpath=preceding-sibling::div[contains(@class, "sceditor-container")][1]');
}

/**
 * The WYSIWYG surface of an editor - a document in an iframe, not an element.
 */
export function editorBody(container) {
  return container.frameLocator('iframe').locator('body');
}

/**
 * Type into one of the editors, replacing whatever it holds.
 */
export async function fillEditor(page, textareaId, text) {
  const body = editorBody(editorFor(page, textareaId));

  await body.click();
  await body.fill(text);
}

/**
 * Switch an editor between WYSIWYG and source, and return the source textarea.
 *
 * That textarea is SCEditor's own, created inside the container, and is not
 * the one from the markup. It is where the BBCode is visible, so it is how a
 * test reads what the editor would post - toggling back and forth is also what
 * runs the content through both halves of a format.
 */
export async function toggleSource(container) {
  await container.locator('.sceditor-button-source').click();

  return container.locator('textarea');
}

/**
 * Insert a custom BBCode through its toolbar button.
 *
 * `customCommand()` and `releaseYearCommand()` open a small popup asking for an
 * id and the text to link, so the fields differ per command: pass them as
 * {inputName: value}.
 *
 * The click on the editor body first is not decoration - exec() starts by
 * asking the range helper what is selected, and there is no range until the
 * WYSIWYG document has been focused once.
 */
export async function insertViaButton(container, code, fields) {
  await editorBody(container).click();
  await container.locator(`.sceditor-button-${code}`).click();

  const dropdown = container.locator(`.sceditor-dropdown.sceditor-insert${code}`);
  for (const [name, value] of Object.entries(fields)) {
    await dropdown.locator(`input[name="${name}"]`).fill(String(value));
  }

  await dropdown.getByRole('button', { name: 'Insert' }).click();

  // exec() closes the dropdown by removing it, so this is the signal that the
  // command ran rather than a fixed wait.
  await expect(dropdown).toHaveCount(0);
}

/**
 * Assert that every editor this page asked for actually exists.
 *
 * Comparing the two counts rather than hard-coding one: a form that gains a
 * BBCode field is then covered without anyone remembering to come back here,
 * and a page that stops rendering an editor still fails.
 *
 * The toolbar check is what tells the two failure modes apart. If
 * command.set() throws there are no containers either, but a container without
 * the custom buttons means SCEditor loaded and our own configuration did not.
 */
export async function expectEditorsBooted(page) {
  const textareas = page.locator('textarea.sceditor');
  const containers = page.locator('.sceditor-container');

  const expected = await textareas.count();
  expect(expected, 'the page renders at least one BBCode editor').toBeGreaterThan(0);
  await expect(containers).toHaveCount(expected);

  await expect(containers.first().locator('.sceditor-button-game')).toBeVisible();
}
