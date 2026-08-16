import { expect } from '@playwright/test';

/**
 * Driving the autocomplete fields (resources/js/autocomplete.js).
 *
 * Here rather than in support/write.js because the public specs drive these
 * fields too, and they may not write - the same reason support/editor.js is
 * its own module.
 */

/**
 * Type into an autocomplete field and click the first matching suggestion.
 *
 * Takes a selector rather than a locator so the suggestion can be scoped to
 * the field it belongs to: autoComplete.js renders its list with
 * insertAdjacentElement('afterend'), which makes it the input's next sibling.
 * A page-wide '.autocomplete-results li' matches every open list on the page,
 * and the game search alone has six fields.
 *
 * Asserts nothing after the click, deliberately: a field carrying
 * data-autocomplete-follow-url navigates on selection and one carrying
 * data-autocomplete-submit submits its form, so what to expect next is the
 * caller's to say.
 */
export async function pickSuggestion(page, selector, term) {
  const input = page.locator(selector);
  const suggestion = page.locator(`${selector} + .autocomplete-results li`, { hasText: term }).first();

  // The dropdown is built when the field is first focused, by a listener the
  // page's scripts attach on DOMContentLoaded. A spec that reaches this
  // straight after a form redirect can be early enough to type before that:
  // the assertion that the row was added is satisfied by the parsed HTML
  // alone. Nothing then requests the suggestions, and the click below waits
  // out the whole test timeout on a list that is never rendered.
  //
  // Hence waiting for the load event, and retrying the typing rather than the
  // click: filling again re-focuses a field that is wired up by now.
  await page.waitForLoadState('load');

  await expect(async () => {
    await input.blur();
    await input.fill('');
    await input.fill(term);
    await expect(suggestion).toBeVisible({ timeout: 5000 });
  }).toPass({ timeout: 30000 });

  await suggestion.click();
}
