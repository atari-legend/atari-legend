import { test, expect } from '../support/test.js';
import { expectValues } from '../support/assertions.js';
import {
  uniqueName,
  createMagazine,
  deleteMagazine,
  createMagazineIssue,
  deleteMagazineIssue,
  createGame,
  deleteGame,
  createIndividual,
  deleteIndividual,
  createMenuSoftware,
  deleteMenuSoftware,
  pickAutocompleteBy,
} from '../support/write.js';

/**
 * The Livewire index editor on an issue's edit screen.
 *
 * Wraps the whole component: the issue's own form on the same page has a Save
 * button of its own, so nothing here can be reached from the page root.
 */
function indexEditor(page) {
  return page.locator('.magazine-index-editor');
}

function indexRows(page) {
  return indexEditor(page).locator('tbody tr');
}

/**
 * Every field of one row, keyed on the row's database id.
 *
 * The cells are addressed by position because that is what the header row
 * documents - Page, Type, Title, Game, Software, Individual, Score - and
 * because the inputs themselves are not distinguishable otherwise: title and
 * score are both a bare text input, and the three clear buttons are identical.
 *
 * The row itself is found through a hidden input rather than through
 * wire:key, whose colon needs escaping in a CSS selector.
 */
function indexRow(page, id) {
  const row = indexEditor(page).locator(`tr:has(input[name="${id}_game_id"])`);
  const cell = (n) => row.locator('td').nth(n);

  return {
    row,
    pageNumber: cell(0).locator('input'),
    type: cell(1).locator('select'),
    title: cell(2).locator('input'),
    // A selector, not a locator: pickAutocompleteBy needs one to build the
    // sibling match that scopes the suggestion list to this field. The game
    // input carries the same name on every row, so it only becomes unique once
    // it is qualified by the row.
    gameField: `.magazine-index-editor tr:has(input[name="${id}_game_id"]) input[name="game_name"]`,
    softwareField: `input[name="${id}_software_name"]`,
    softwareId: page.locator(`input[name="${id}_menu_software_id"]`),
    // One clear button per link cell; only the software one is driven, and the
    // three are the same button beside the same shape of field.
    clearSoftware: cell(4).getByRole('button'),
    individualField: `input[name="${id}_individual_name"]`,
    score: cell(6).locator('input'),
    remove: cell(7).getByRole('button', { name: 'Delete' }),
  };
}

/**
 * Do something that talks to Livewire, and wait for the answer.
 *
 * Every edit in this editor is a round trip, and none of them is synchronous
 * with the gesture that starts it. The page field is bound
 * wire:model.live.debounce.750ms, so filling it and clicking Save saves the
 * value the row arrived with. Selecting from an autocomplete is worse: the
 * hidden input carries the id immediately, so the pick looks finished while
 * updateGame() is still running - and it ends in $issue->refresh(), which
 * throws away whatever was typed into the next field in the meantime.
 *
 * Takes a function rather than a promise so the listener is attached before the
 * request it is waiting for can be made.
 */
async function live(page, action) {
  const settled = page.waitForResponse((response) => response.url().includes('/livewire/update'));

  await action();
  await settled;
}

/**
 * Add a row and return its database id.
 *
 * addRow() saves first and appends, so the new row is the last one; its id is
 * in the wire:key the component uses to keep the rows apart across renders.
 */
async function addIndexRow(page) {
  const rows = indexRows(page);
  const before = await rows.count();

  await live(page, () => indexEditor(page).getByRole('button', { name: 'Add row' }).click());
  await expect(rows).toHaveCount(before + 1);

  const key = await rows.nth(before).getAttribute('wire:key');

  return key.replace('index-field-', '');
}

async function saveIndex(page) {
  await live(page, () => indexEditor(page).getByRole('button', { name: 'Save', exact: true }).click());
}

/**
 * The issue's card on the public magazine page.
 *
 * There is no page per issue: every issue of a magazine is a card on
 * /magazines/{magazine}, anchored on its own id.
 */
async function openPublicIssue(page, issue) {
  await page.goto(`/magazines/${issue.magazineId}`);

  return page.locator(`#magazine-issue-${issue.id}`);
}

/** One row of the rendered index, found by the title cell's text. */
function publicRow(card, text) {
  return card.locator('tbody tr').filter({ hasText: text });
}

test.describe('Admin magazines', () => {
  test('creates and deletes a magazine', async ({ page }) => {
    const magazine = await createMagazine(page);

    await deleteMagazine(page, magazine);
  });

  test('creates and deletes an issue', async ({ page }) => {
    // Its own magazine rather than the seeded one: an issue is a card on its
    // magazine, which is a page the read specs open.
    const magazine = await createMagazine(page);
    const issue = await createMagazineIssue(page, magazine);

    await deleteMagazineIssue(page, issue);
    await deleteMagazine(page, magazine);
  });

  test('updates a magazine and its issue', async ({ page }) => {
    const magazine = await createMagazine(page);
    const issue = await createMagazineIssue(page, magazine);
    const renamed = uniqueName('Magazine');
    const relabelled = uniqueName('Issue');

    // The magazine. Its location is left alone: the location table ships empty
    // and E2ESeeder adds nothing to it, so the select has only its blank option.
    await page.goto(`/admin/magazines/magazines/${magazine.id}/edit`);
    await page.fill('#name', renamed);
    await page.getByRole('button', { name: 'Save' }).first().click();
    await expect(page).toHaveURL(/\/admin\/magazines\/magazines$/);

    // The issue, through every field the form validates.
    await page.goto(`/admin/magazines/magazines/${magazine.id}/issues/${issue.id}/edit`);
    await page.fill('#issue', '42');
    await page.fill('#label', relabelled);
    await page.fill('#published', '1991-07-01');
    await page.fill('#page_count', '84');
    await page.fill('#circulation', '25000');
    await page.fill('#alternate_url', 'https://example.com/issue');
    // Scoped to the issue's own form: the index editor below it has a Save of
    // its own, and the two are only told apart by the form they are in.
    await page.locator(`form[action$="/issues/${issue.id}"]`)
      .getByRole('button', { name: 'Save', exact: true })
      .click();

    await expect(page).toHaveURL(
      new RegExp(`/admin/magazines/magazines/${magazine.id}/issues/${issue.id}/edit$`)
    );
    await expect(page.locator('#issue')).toHaveValue('42');
    await expect(page.locator('#label')).toHaveValue(relabelled);
    await expect(page.locator('#published')).toHaveValue('1991-07-01');
    await expect(page.locator('#page_count')).toHaveValue('84');
    await expect(page.locator('#circulation')).toHaveValue('25000');

    // The card header is assembled from three of those fields plus the date.
    const card = await openPublicIssue(page, issue);
    await expect(card.getByRole('heading', { level: 3 }))
      .toContainText(`${renamed} #42 ${relabelled}`);
    await expect(card).toContainText('Jul 1991');

    await deleteMagazineIssue(page, issue);
    await deleteMagazine(page, { ...magazine, name: renamed });
  });

  /**
   * The index editor, end to end.
   *
   * tests/Feature/Admin/Magazines/MagazineIndexTest.php drives the same
   * component through Livewire's own test helpers, so it passes whether or not
   * any of the browser wiring still works: three autocompletes per row that are
   * re-registered by hand after every row change
   * (resources/js/admin/magazines/magazines.js), a hidden companion input
   * carrying wire:change, and a debounced binding on the page number.
   *
   * So each edit is checked twice - once in the editor after a reload, which is
   * what proves it reached the database, and once on the public card, which is
   * what the index is for.
   */
  test('builds an index for an issue and checks it on the site', async ({ page }) => {
    test.setTimeout(180000);

    // Everything this touches is its own, parents included: an index row on the
    // seeded game would surface on /games/xenon-2-megablast, which
    // games/card_magazines.blade.php renders and a read spec opens.
    const magazine = await createMagazine(page);
    const issue = await createMagazineIssue(page, magazine);
    const game = await createGame(page);
    const otherGame = await createGame(page);
    const individual = await createIndividual(page);
    const software = await createMenuSoftware(page);

    const editPath = `/admin/magazines/magazines/${magazine.id}/issues/${issue.id}/edit`;
    let testPassed = false;

    try {
      // 1. An issue starts with no index at all.
      await page.goto(editPath);
      await expect(indexEditor(page)).toBeVisible();
      await expect(indexRows(page)).toHaveCount(0);
      await expect(await openPublicIssue(page, issue)).toContainText('No index for this magazine');

      // 2. A game row: the shape that carries a score and no title of its own,
      //    where the public view falls back to the game's name.
      await page.goto(editPath);
      const gameRowId = await addIndexRow(page);
      const gameRow = indexRow(page, gameRowId);

      await live(page, () => gameRow.pageNumber.fill('12'));
      await live(page, () => gameRow.type.selectOption({ label: 'Review' }));
      await live(page, () => pickAutocompleteBy(page, gameRow.gameField, game.name));
      await live(page, () => gameRow.score.fill('90%'));
      await saveIndex(page);

      let card = await openPublicIssue(page, issue);
      let row = publicRow(card, game.name);
      await expect(row.getByRole('link', { name: game.name })).toHaveAttribute(
        'href',
        new RegExp(`/games/${game.slug}$`)
      );
      await expect(row).toContainText('90%');
      await expect(row).toContainText('Review');
      await expect(row.locator('td').last()).toHaveText('12');

      // 3. A software row.
      await page.goto(editPath);
      const softwareRowId = await addIndexRow(page);
      const softwareRow = indexRow(page, softwareRowId);
      const softwareTitle = uniqueName('Cover disk');

      await live(page, () => softwareRow.pageNumber.fill('30'));
      await live(page, () => softwareRow.type.selectOption({ label: 'Tutorial' }));
      await live(page, () => softwareRow.title.fill(softwareTitle));
      await live(page, () => pickAutocompleteBy(page, softwareRow.softwareField, software.name));
      await saveIndex(page);

      card = await openPublicIssue(page, issue);
      row = publicRow(card, softwareTitle);
      await expect(row.getByRole('link', { name: software.name })).toHaveAttribute(
        'href',
        /\/menusets\/software\/\d+$/
      );
      // A title alongside a link is rendered after it, not instead of it.
      await expect(row.locator('td').first()).toContainText(`: ${softwareTitle}`);

      // 4. An individual row.
      await page.goto(editPath);
      const individualRowId = await addIndexRow(page);
      const individualRow = indexRow(page, individualRowId);
      const individualTitle = uniqueName('Profile');

      await live(page, () => individualRow.pageNumber.fill('44'));
      await live(page, () => individualRow.type.selectOption({ label: 'Interview' }));
      await live(page, () => individualRow.title.fill(individualTitle));
      await live(page, () => pickAutocompleteBy(page, individualRow.individualField, individual.name));
      await saveIndex(page);

      card = await openPublicIssue(page, issue);
      row = publicRow(card, individualTitle);
      await expect(row.getByRole('link', { name: individual.name })).toHaveAttribute(
        'href',
        /\/games\/search\?individual_id=\d+$/
      );

      // 5. A row that links to nothing, and is only its title.
      await page.goto(editPath);
      const textRowId = await addIndexRow(page);
      const textRow = indexRow(page, textRowId);
      const textTitle = uniqueName('Editorial');

      await live(page, () => textRow.pageNumber.fill('3'));
      await live(page, () => textRow.type.selectOption({ label: 'Column' }));
      await live(page, () => textRow.title.fill(textTitle));
      await saveIndex(page);

      card = await openPublicIssue(page, issue);
      row = publicRow(card, textTitle);
      await expect(row.getByRole('link')).toHaveCount(0);

      // 6. Four rows entered in a different order than they are read in: the
      //    public view sorts by page, and the pages were 12, 30, 44, 3.
      await expect(card.locator('tbody tr td:last-child')).toHaveText(['3', '12', '30', '44']);

      // 7. Updating a row that already has values, rather than filling a blank
      //    one. Giving the game row a title of its own also changes how it is
      //    rendered - the game's name stops being the whole cell.
      const gameTitle = uniqueName('Reviewed');
      await page.goto(editPath);
      await live(page, () => indexRow(page, gameRowId).pageNumber.fill('20'));
      await live(page, () => indexRow(page, gameRowId).title.fill(gameTitle));
      await live(page, () => indexRow(page, gameRowId).score.fill('75%'));
      await saveIndex(page);

      await page.reload();
      await expect(indexRow(page, gameRowId).pageNumber).toHaveValue('20');
      await expect(indexRow(page, gameRowId).title).toHaveValue(gameTitle);
      await expect(indexRow(page, gameRowId).score).toHaveValue('75%');

      card = await openPublicIssue(page, issue);
      row = publicRow(card, gameTitle);
      await expect(row.getByRole('link', { name: game.name })).toBeVisible();
      await expect(row.locator('td').first()).toContainText(`: ${gameTitle}`);
      await expect(row).toContainText('75%');
      await expect(row.locator('td').last()).toHaveText('20');

      // 8. Clearing a link with the button beside its field. The row keeps its
      //    title and degrades to the plain-text shape.
      await page.goto(editPath);
      await live(page, () => indexRow(page, softwareRowId).clearSoftware.click());
      await expect(indexRow(page, softwareRowId).softwareId).toHaveValue('');

      card = await openPublicIssue(page, issue);
      row = publicRow(card, softwareTitle);
      await expect(row.getByRole('link')).toHaveCount(0);
      await expect(row.locator('td').first()).toHaveText(softwareTitle);

      // 8b. Clearing the type back to the blank option, which the public view
      //     renders as a dash.
      await page.goto(editPath);
      await live(page, () => indexRow(page, softwareRowId).type.selectOption({ label: '-' }));
      await saveIndex(page);

      await page.reload();
      await expect(indexRow(page, softwareRowId).type).toHaveValue('');

      card = await openPublicIssue(page, issue);
      await expect(publicRow(card, softwareTitle).locator('td').nth(1)).toHaveText('-');

      // 9. Picking into a field that already holds a value, which is the one
      //    autocomplete mode a blank create form never reaches.
      await page.goto(editPath);
      await live(page, () => pickAutocompleteBy(page, indexRow(page, gameRowId).gameField, otherGame.name));
      await saveIndex(page);

      card = await openPublicIssue(page, issue);
      row = publicRow(card, gameTitle);
      await expect(row.getByRole('link', { name: otherGame.name })).toBeVisible();
      await expect(row.getByRole('link', { name: game.name })).toHaveCount(0);

      // 10. Auto-sort reorders what is on screen. It is a display option, so
      //     the order it shows is not the order the next visit starts from.
      await page.goto(editPath);
      const pagesAsEntered = ['20', '30', '44', '3'];
      const pageInputs = indexRows(page).locator('td:first-child input');
      await expectValues(pageInputs, pagesAsEntered);

      await live(page, () => page.locator('#autosort').check());
      await expectValues(pageInputs, ['3', '20', '30', '44']);

      await page.reload();
      await expect(page.locator('#autosort')).not.toBeChecked();
      await expectValues(pageInputs, pagesAsEntered);

      // 11. Deleting a row.
      await live(page, () => indexRow(page, textRowId).remove.click());
      await expect(indexRows(page)).toHaveCount(3);
      await expect(indexRow(page, textRowId).row).toHaveCount(0);

      card = await openPublicIssue(page, issue);
      await expect(card.locator('tbody tr')).toHaveCount(3);
      await expect(card).not.toContainText(textTitle);

      // 12. The index reads from the game's side too, which is most of why it
      //     is worth entering.
      await page.goto(`/games/${otherGame.slug}`);
      await expect(page.getByRole('heading', { name: 'Magazines' })).toBeVisible();
      await expect(page.getByRole('link', { name: new RegExp(magazine.name) })).toBeVisible();

      testPassed = true;
    } finally {
      // Best-effort: a failure above should report itself, not be buried under
      // the teardown failing on a record that was never created.
      const cleanUp = async (label, remove) => {
        try {
          await remove();
        } catch (error) {
          if (testPassed) {
            throw error;
          }
          console.warn(`cleanup: could not delete the ${label}: ${error.message}`);
        }
      };

      // The issue first: its index rows cascade with it, and a magazine that
      // still has issues has its delete button disabled.
      await cleanUp('issue', () => deleteMagazineIssue(page, issue));
      await cleanUp('magazine', () => deleteMagazine(page, magazine));
      await cleanUp('game', () => deleteGame(page, game));
      await cleanUp('other game', () => deleteGame(page, otherGame));
      await cleanUp('individual', () => deleteIndividual(page, individual));
      await cleanUp('software', () => deleteMenuSoftware(page, software));
    }
  });

  // TODO: uploading an issue cover, which goes through a plain file field but
  // shares its slot with the archive.org fetch below.
  //
  // TODO: the archive.org cover fetch behind #fetch-thumbnail, which calls out
  // to a live third party from the server.
});
