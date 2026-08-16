import { expect } from '@playwright/test';
import { FIXTURE } from './fixture.js';
import { pickSuggestion } from './autocomplete.js';

/**
 * Helpers for the admin-write project.
 *
 * These specs create everything they modify - the parent as well as the child -
 * rather than hanging something off a seeded row. Nothing they do is then
 * visible to a spec reading the fixture at the same time, which is what lets
 * this project run without waiting for the read ones. See playwright.config.js.
 *
 * Everything here exists to make that round-trip - create, find, delete - a
 * one-liner.
 */

/**
 * A name no other row will have.
 *
 * The 'E2E ' prefix is what you grep the database for when a run is killed
 * halfway through and leaves something behind.
 *
 * The random suffix is not decoration: these specs run in parallel across
 * workers, and a timestamp alone collides whenever two of them reach this line
 * in the same millisecond. A game's slug is validated unique, so a collision
 * surfaces as a validation error several steps later.
 */
export function uniqueName(label) {
  return `E2E ${label} ${Date.now()}${Math.random().toString(36).slice(1, 5)}`;
}

/**
 * A slug for a name from uniqueName().
 *
 * App\Rules\Slug accepts lower-case letters, digits and hyphens, and insists on
 * at least one letter.
 */
export function uniqueSlug(name) {
  return name.toLowerCase().replace(/[^0-9a-z]+/g, '-');
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
 *
 * The typing itself lives in support/autocomplete.js, which the public specs
 * use too; what this adds is the assertion that the id arrived, which is what
 * an admin form posts.
 */
export async function pickAutocomplete(page, inputId, term) {
  await pickAutocompleteBy(page, `#${inputId}`, term);
}

/**
 * The same, for a field that has no id to address it by.
 *
 * The magazine index editor renders three autocompletes per row and gives none
 * of them an id, so they have to be reached through their row - and the game
 * field carries the same `name` on every row, so even that is not enough on its
 * own. Hence a selector rather than an id.
 *
 * There the companion assertion is load-bearing twice over: the hidden field is
 * what the form posts elsewhere, but in the editor it is also what carries
 * wire:change, so a value arriving there is what proves the Livewire round trip
 * fired.
 */
export async function pickAutocompleteBy(page, selector, term) {
  await pickSuggestion(page, selector, term);

  // The companion hidden field is what the controller actually reads.
  const companion = await page.locator(selector).getAttribute('data-autocomplete-companion');
  await expect(page.locator(`input[name="${companion}"]`)).not.toHaveValue('');
}

/**
 * Type into an autocomplete field and pick a suggestion, asserting nothing.
 *
 * Re-exported for the same reason as fillEditor below: a spec creating a
 * record imports everything from here.
 */
export { pickSuggestion };

/**
 * Type into one of the BBCode editors that replace a textarea.sceditor.
 *
 * Re-exported so that a spec creating a record imports everything it needs
 * from here; the editor helpers themselves live in support/editor.js, because
 * tests/e2e/admin/editor.spec.js drives them without writing anything.
 */
export { fillEditor } from './editor.js';

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

/**
 * The parents a write spec attaches things to.
 *
 * A spec that needs a game to hang a release off creates the game too, rather
 * than borrowing the seeded one. Two reasons, and the second is the load-
 * bearing one:
 *
 * - A seeded row is what a read spec asserts on. Adding a child to it makes
 *   this project's work visible over there, which is why admin-write used to
 *   have to wait for the read projects to finish.
 * - Anything created here is deleted here, so nothing accumulates.
 *
 * Reference data is the exception, and the line is mutation rather than
 * reference: these forms still *select* a seeded crew, genre or condition.
 * Menu conditions and content types come from migrations and have no create
 * form at all, and un-ticking every checkbox is not what these specs are for.
 *
 * Every create returns an object carrying the id its children need; pass the
 * same object back to the matching delete. Delete children before parents -
 * Game::getIsDeletableAttribute() refuses a game that still has a release, a
 * review, a fact, a credit or a similar-game link.
 */

/**
 * Create a game, and land on its edit screen.
 *
 * The slug is optional to the validator, but the games table links every row
 * through route('games.show'), which throws on a null slug - so the row this
 * returns would break the very table its delete goes through.
 */
export async function createGame(page) {
  const name = uniqueName('Game');
  const slug = uniqueSlug(name);

  await page.goto('/admin/games/games/create');
  await page.fill('#name', name);
  await page.fill('#slug', slug);
  await page.getByRole('button', { name: 'Save' }).first().click();

  await expect(page).toHaveURL(/\/admin\/games\/games\/\d+\/edit$/);

  // The slug comes back too: route('games.show') builds its URL from it, so a
  // spec checking that something links to this game needs it.
  return { id: page.url().split('/').at(-2), name, slug };
}

export async function deleteGame(page, game) {
  await page.goto('/admin/games/games');
  await deleteRow(page, game.name);
}

/**
 * Create an individual - the person an interview is with.
 */
export async function createIndividual(page) {
  const name = uniqueName('Individual');

  await page.goto('/admin/games/individuals/create');
  await page.fill('#name', name);
  await page.getByRole('button', { name: 'Save' }).first().click();

  await expect(page).toHaveURL(/\/admin\/games\/individuals\/\d+\/edit$/);

  return { id: page.url().split('/').at(-2), name };
}

export async function deleteIndividual(page, individual) {
  await page.goto('/admin/games/individuals');
  await deleteRow(page, individual.name);
}

/**
 * Create a magazine - the parent an issue belongs to.
 */
export async function createMagazine(page) {
  const name = uniqueName('Magazine');

  await page.goto('/admin/magazines/magazines/create');
  await page.fill('#name', name);
  await page.getByRole('button', { name: 'Save' }).click();

  await expect(page).toHaveURL(/\/admin\/magazines\/magazines\/\d+\/edit$/);

  return { id: page.url().split('/').at(-2), name };
}

export async function deleteMagazine(page, magazine) {
  await page.goto('/admin/magazines/magazines');
  await deleteRow(page, magazine.name);
}

/**
 * Create an issue of a magazine - the parent an index belongs to.
 *
 * "Save", not "Save & Close": staying on the record is what puts its id in the
 * URL, and the issues table on the magazine has no searchable column to find it
 * by afterwards.
 */
export async function createMagazineIssue(page, magazine) {
  const label = uniqueName('Issue');

  await page.goto(`/admin/magazines/magazines/${magazine.id}/issues/create`);
  await page.fill('#issue', '9999');
  await page.fill('#label', label);
  await page.getByRole('button', { name: 'Save', exact: true }).click();

  await expect(page).toHaveURL(
    new RegExp(`/admin/magazines/magazines/${magazine.id}/issues/\\d+/edit$`)
  );

  return { id: page.url().split('/').at(-2), magazineId: magazine.id, label };
}

/**
 * Issues are cards on their magazine rather than rows in a table of their own,
 * so the form is identified by the id in its action.
 *
 * The index rows go with the issue: magazine_indices.magazine_issue_id cascades
 * on delete.
 */
export async function deleteMagazineIssue(page, issue) {
  await page.goto(`/admin/magazines/magazines/${issue.magazineId}/edit`);
  await deleteByAction(page, `/issues/${issue.id}`);
}

/**
 * Create a menu software - one of the three things a magazine index row can
 * point at.
 *
 * Unlike the other factories this one has no id to return: store() redirects to
 * the index rather than to the new record's edit screen. Nothing needs it - the
 * autocomplete matches on the name, and the table is searchable by it.
 *
 * The type select is left alone deliberately. It has no blank option, so the
 * browser posts the first one, and MenuSoftwareController::edit() dereferences
 * the content type unguarded - a software without one 500s its own edit page.
 */
export async function createMenuSoftware(page) {
  const name = uniqueName('Software');

  await page.goto('/admin/menus/software/create');
  await page.fill('#name', name);
  await page.getByRole('button', { name: 'Save' }).click();

  await expect(page).toHaveURL(/\/admin\/menus\/software$/);

  return { name };
}

export async function deleteMenuSoftware(page, software) {
  await page.goto('/admin/menus/software');
  await deleteRow(page, software.name);
}

/**
 * Create a menu set - the top of the menus hierarchy.
 *
 * A set has to belong to at least one crew: the validation rules make `crews`
 * required, and the field is a multi-select rather than an autocomplete. The
 * crew is only selected, never written to, so the seeded one is fair game.
 */
export async function createMenuSet(page) {
  const name = uniqueName('Menu Set');

  await page.goto('/admin/menus/sets/create');
  await page.fill('#name', name);
  await page.selectOption('select[name="crews[]"]', String(FIXTURE.crew.id));
  await page.getByRole('button', { name: 'Save' }).click();

  await expect(page).toHaveURL(/\/admin\/menus\/sets\/\d+\/edit$/);

  return { id: page.url().split('/').at(-2), name };
}

/**
 * Sets have an index of their own, but a plain table rather than a Livewire
 * one - so there is no search box, and the row is found by the id in its
 * delete form's action.
 */
export async function deleteMenuSet(page, set) {
  await page.goto('/admin/menus/sets');
  await deleteByAction(page, `/sets/${set.id}`);
}

/**
 * Create a menu within a set - the parent a disk belongs to.
 *
 * A menu is identified by its number within its set rather than by a name, and
 * the set is new here, so 1 is free.
 */
export async function createMenu(page, set) {
  await page.goto(`/admin/menus/menus/create?set=${set.id}`);
  await page.fill('#number', '1');
  await page.getByRole('button', { name: 'Save' }).click();

  await expect(page).toHaveURL(/\/admin\/menus\/menus\/\d+\/edit$/);

  return { id: page.url().split('/').at(-2), setId: set.id };
}

export async function deleteMenu(page, menu) {
  await page.goto(`/admin/menus/sets/${menu.setId}/edit`);
  await deleteByAction(page, `/menus/${menu.id}`);
}
