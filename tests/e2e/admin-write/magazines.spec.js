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
  acceptConfirms,
  DELETE_FORM,
  PNG,
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

const INDEX_TYPES = '/admin/magazines/index-types';

/**
 * One row of the index types table, found by the name it holds.
 *
 * Not a Livewire table: the whole list is rendered at once, every row is an
 * inline update form, and there is no search box to narrow it with. So the row
 * has to be identified by its own content - and the text input is the wrong
 * handle for that, because `value` in a CSS selector matches the *attribute*
 * the server rendered, which stops tracking the field the moment a test types
 * into it. The delete button's title is server-rendered too and does not
 * double as an input, so it survives the rename this test does.
 */
function indexTypeRow(page, name) {
  return page.locator(`tr:has(button[title="Delete '${name}'"])`);
}

/**
 * Collect every alert()/confirm() the page raises, and accept it.
 *
 * `acceptConfirms()` already accepts, but throws the message away - and the
 * message is the assertion for the two failure paths of the archive.org
 * fetch, which report themselves through alert() and nothing else. Registered
 * per test rather than per page, so the array only holds this test's dialogs.
 */
function captureDialogs(page) {
  const messages = [];

  page.on('dialog', (dialog) => {
    messages.push(dialog.message());
    dialog.accept().catch(() => {});
  });

  return messages;
}

/** The issue cover as it is rendered on the public magazine page. */
function publicCover(card) {
  return card.locator('img[alt^="Cover for"]');
}

/**
 * Assert the browser actually decoded the image, not just that the src is
 * right.
 *
 * The guard in support/test.js deliberately exempts /storage/, so nothing else
 * would notice a cover that never arrived - and a file that does arrive but is
 * not a valid image answers 200 either way. naturalWidth is 0 until the decode
 * succeeds, which is the difference between "the path is right" and "there is
 * an image there".
 */
async function expectImageLoads(image) {
  await expect.poll(
    () => image.evaluate((img) => img.complete && img.naturalWidth)
  ).toBeGreaterThan(0);
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

  /**
   * An index type, created here rather than borrowed.
   *
   * The types the fixture's index rows point at come from the magazines
   * migration, and renaming or deleting one of those would change what
   * public/magazines.spec.js reads out of the Type column - so this makes its
   * own, uses it once, and takes it away again.
   *
   * The screen is the odd one out in this admin: a plain table of inline update
   * forms rather than a Livewire one, with the create form in a card above it.
   * Nothing about it is covered by a browser today, and the round trip it hides
   * is that a type created on this page is immediately an option in the index
   * editor - two screens and a Livewire component apart.
   */
  test('creates, renames and deletes a magazine index type', async ({ page }) => {
    test.setTimeout(120000);

    const name = uniqueName('Index type');
    const renamed = uniqueName('Index type');

    await page.goto(INDEX_TYPES);
    await page.fill('#name', name);
    await page.getByRole('button', { name: 'Add' }).click();

    await expect(page).toHaveURL(new RegExp(`${INDEX_TYPES}$`));
    await expect(indexTypeRow(page, name)).toHaveCount(1);
    // A brand new type is used by nothing, which is what makes it deletable
    // again at the end.
    await expect(indexTypeRow(page, name).locator('td').last()).toHaveText('0 indices');

    // The inline update form, which is the only way to rename one.
    await indexTypeRow(page, name).locator('input[name="name"]').fill(renamed);
    await indexTypeRow(page, name).getByRole('button', { name: 'Update' }).click();

    await expect(page).toHaveURL(new RegExp(`${INDEX_TYPES}$`));
    await expect(indexTypeRow(page, name)).toHaveCount(0);
    await expect(indexTypeRow(page, renamed)).toHaveCount(1);
    await expect(indexTypeRow(page, renamed).locator('input[name="name"]')).toHaveValue(renamed);

    // Its own magazine and issue: the type is only worth anything as an option
    // in the index editor, and an index row on a seeded issue would show up on
    // a page the read specs open.
    const magazine = await createMagazine(page);
    const issue = await createMagazineIssue(page, magazine);
    let testPassed = false;

    try {
      const title = uniqueName('Feature');

      await page.goto(`/admin/magazines/magazines/${magazine.id}/issues/${issue.id}/edit`);
      const rowId = await addIndexRow(page);
      const row = indexRow(page, rowId);

      await live(page, () => row.pageNumber.fill('7'));
      await live(page, () => row.title.fill(title));
      // By label rather than by id: what is being checked is that the name
      // typed into the other screen reached this select.
      await live(page, () => row.type.selectOption({ label: renamed }));
      await saveIndex(page);

      const card = await openPublicIssue(page, issue);
      await expect(publicRow(card, title).locator('td').nth(1)).toHaveText(renamed);

      // And the count on the types screen is the same relationship read from
      // the other end.
      await page.goto(INDEX_TYPES);
      await expect(indexTypeRow(page, renamed).locator('td').last()).toHaveText('1 index');

      testPassed = true;
    } finally {
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

      await cleanUp('issue', () => deleteMagazineIssue(page, issue));
      await cleanUp('magazine', () => deleteMagazine(page, magazine));
    }

    // The index row went with the issue, so the type is unused again.
    await page.goto(INDEX_TYPES);
    await expect(indexTypeRow(page, renamed).locator('td').last()).toHaveText('0 indices');

    acceptConfirms(page);
    await indexTypeRow(page, renamed).locator(`${DELETE_FORM} button`).click();

    await expect(page).toHaveURL(new RegExp(`${INDEX_TYPES}$`));
    await expect(indexTypeRow(page, renamed)).toHaveCount(0);
  });

  /**
   * The cover, through the file field.
   *
   * Three things happen in the browser before anything is posted, and none of
   * them is visible to MagazinesTest, which posts an UploadedFile straight at
   * the controller: resources/js/admin/magazines/magazines.js previews the
   * chosen file with a FileReader, and it owns the two hidden inputs -
   * useArchiveOrgCover and destroyImage - that decide which of the three
   * branches of addOrUpdateImage() runs. Picking a file has to clear the first
   * and the second, or a save would fetch from archive.org or throw the upload
   * away.
   */
  test('uploads a cover for an issue and removes it again', async ({ page }) => {
    test.setTimeout(120000);

    const magazine = await createMagazine(page);
    const issue = await createMagazineIssue(page, magazine);
    const editPath = `/admin/magazines/magazines/${magazine.id}/issues/${issue.id}/edit`;
    const cover = page.locator('#issue-cover');
    let testPassed = false;

    try {
      // 1. An issue starts with no cover at all, on both screens.
      await page.goto(editPath);
      await expect(cover).toHaveAttribute('src', /\/images\/no-cover\.svg$/);

      let card = await openPublicIssue(page, issue);
      await expect(publicCover(card)).toHaveAttribute('src', /\/images\/no-cover\.svg$/);

      // 2. Choosing a file previews it without posting anything, and settles
      //    the two hidden inputs on "upload this".
      await page.goto(editPath);
      await page.locator('#image').setInputFiles({
        name: 'cover.png',
        mimeType: 'image/png',
        buffer: PNG,
      });

      await expect(cover).toHaveAttribute('src', /^data:image\/png;base64,/);
      await expect(page.locator('#useArchiveOrgCover')).toHaveValue('');
      await expect(page.locator('#destroyImage')).toHaveValue('');

      // 3. Saved. The green Save keeps us on the issue, which is where the
      //    stored cover is rendered back.
      await page.locator(`form[action$="/issues/${issue.id}"]`)
        .getByRole('button', { name: 'Save', exact: true })
        .click();
      await expect(page).toHaveURL(new RegExp(`${editPath}$`));

      // The extension comes from UploadedFile::extension(), which sniffs the
      // content rather than trusting the name - hence a real PNG in the buffer.
      const stored = new RegExp(`/storage/images/magazine_scans/${issue.id}\\.png$`);
      await expect(cover).toHaveAttribute('src', stored);
      await expectImageLoads(cover);

      // 4. And it is the cover the public magazine page shows.
      card = await openPublicIssue(page, issue);
      await expect(publicCover(card)).toHaveAttribute('src', stored);
      await expectImageLoads(publicCover(card));

      // 5. The trash button beside the preview is JavaScript only: it swaps the
      //    placeholder back in and arms destroyImage, and nothing goes until
      //    the form is saved.
      await page.goto(editPath);
      await page.locator('#destroy-image-button').click();

      await expect(cover).toHaveAttribute('src', /\/images\/no-cover\.svg$/);
      await expect(page.locator('#destroyImage')).toHaveValue('true');
      await expect(page.locator('#useArchiveOrgCover')).toHaveValue('');

      await page.locator(`form[action$="/issues/${issue.id}"]`)
        .getByRole('button', { name: 'Save', exact: true })
        .click();
      await expect(page).toHaveURL(new RegExp(`${editPath}$`));
      await expect(cover).toHaveAttribute('src', /\/images\/no-cover\.svg$/);

      card = await openPublicIssue(page, issue);
      await expect(publicCover(card)).toHaveAttribute('src', /\/images\/no-cover\.svg$/);

      testPassed = true;
    } finally {
      // Deleting the issue would not have taken the file with it -
      // MagazineIssuesController::destroy() deletes the row and nothing else -
      // so removing the cover above is teardown as well as an assertion.
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

      await cleanUp('issue', () => deleteMagazineIssue(page, issue));
      await cleanUp('magazine', () => deleteMagazine(page, magazine));
    }
  });

  /**
   * The archive.org cover fetch, as far as the browser goes.
   *
   * It is half a browser feature and half a server one, and only the first half
   * is testable here:
   *
   * - In the browser, #fetch-thumbnail parses the archive.org URL out of the
   *   form, points the preview `img` straight at
   *   `https://archive.org/download/{id}/page/cover_w600.jpg`, and uses that
   *   image's own load/error events to restore the button and set
   *   useArchiveOrgCover. That is all interceptable with page.route(), because
   *   the request is one the page makes.
   * - On save, MagazineIssuesController::fetchImage() makes the *same* request
   *   again from PHP, with Http::get() against a hard-coded host. Nothing a
   *   browser test can do reaches that, so this test never submits the form
   *   with the flag set - see the TODO at the bottom of the file.
   *
   * So what is asserted here is the flag, the preview and the two failure
   * paths, all of which report themselves through alert() and nothing else.
   */
  test('fetches an issue cover from archive.org', async ({ page }) => {
    test.setTimeout(120000);

    const magazine = await createMagazine(page);
    const issue = await createMagazineIssue(page, magazine);
    const editPath = `/admin/magazines/magazines/${magazine.id}/issues/${issue.id}/edit`;
    const archiveUrl = 'https://archive.org/details/e2e-cover-fixture/';
    const cover = page.locator('#issue-cover');
    const fetchButton = page.locator('#fetch-thumbnail');
    const useArchiveOrgCover = page.locator('#useArchiveOrgCover');
    let testPassed = false;

    // Never a real request to archive.org: the point of the stub is that this
    // test says nothing about whether a third party is up today.
    let archiveResponds = 'image';
    await page.route('https://archive.org/download/**', (route) => {
      if (archiveResponds === 'image') {
        return route.fulfill({ contentType: 'image/png', body: PNG });
      }

      return route.fulfill({ status: 404, contentType: 'text/plain', body: 'Not found' });
    });

    try {
      const dialogs = captureDialogs(page);

      await page.goto(editPath);

      // 1. No URL to fetch from. The button is client-side validation and
      //    nothing else, so the complaint is an alert.
      await fetchButton.click();
      await expect.poll(() => dialogs).toContain('Missing or invalid archive.org URL');
      await expect(useArchiveOrgCover).toHaveValue('');
      await expect(cover).toHaveAttribute('src', /\/images\/no-cover\.svg$/);

      // 2. A URL that is not an archive.org details page is refused by the same
      //    check, before any request is made.
      await page.fill('#archiveorg_url', 'https://example.com/not-archive-org/');
      await fetchButton.click();
      await expect.poll(() => dialogs.length).toBe(2);
      await expect(useArchiveOrgCover).toHaveValue('');

      // 3. A valid URL whose cover is not there. The failure arrives as the
      //    img's error event, which is the only thing this feature listens to.
      archiveResponds = 'missing';
      await page.fill('#archiveorg_url', archiveUrl);
      await fetchButton.click();

      await expect.poll(() => dialogs).toContain('Error fetching cover from archive.org');
      // The button comes back either way, which is what makes a retry possible.
      await expect(fetchButton).toBeEnabled();
      await expect(fetchButton).toHaveText('Fetch from Archive.org');

      // 4. The cover is there. The preview points at archive.org, the button
      //    un-spins, and the hidden input tells the controller to fetch it
      //    server-side on the next save.
      archiveResponds = 'image';
      await page.reload();
      await page.fill('#archiveorg_url', archiveUrl);
      await fetchButton.click();

      await expect(useArchiveOrgCover).toHaveValue('true');
      await expect(cover).toHaveAttribute(
        'src',
        'https://archive.org/download/e2e-cover-fixture/page/cover_w600.jpg'
      );
      await expectImageLoads(cover);
      await expect(fetchButton).toBeEnabled();
      await expect(fetchButton).toHaveText('Fetch from Archive.org');
      await expect(page.locator('#destroyImage')).toHaveValue('');

      // 5. The file field and the fetch share one slot, and picking a file
      //    takes it back: without this the next save would ignore the upload
      //    and go to archive.org instead.
      await page.locator('#image').setInputFiles({
        name: 'cover.png',
        mimeType: 'image/png',
        buffer: PNG,
      });
      await expect(useArchiveOrgCover).toHaveValue('');
      await expect(cover).toHaveAttribute('src', /^data:image\/png;base64,/);

      // Deselecting leaves the flags where they are - the change handler does
      // nothing without a file - so the save below stores no cover and, with
      // useArchiveOrgCover clear, makes no request from the server either.
      await page.locator('#image').setInputFiles([]);
      await expect(useArchiveOrgCover).toHaveValue('');

      // 6. The URL itself is an ordinary field, and it is what the public page
      //    builds its "read this issue" link from.
      await page.locator(`form[action$="/issues/${issue.id}"]`)
        .getByRole('button', { name: 'Save', exact: true })
        .click();
      await expect(page).toHaveURL(new RegExp(`${editPath}$`));
      await expect(page.locator('#archiveorg_url')).toHaveValue(archiveUrl);
      await expect(cover).toHaveAttribute('src', /\/images\/no-cover\.svg$/);

      const card = await openPublicIssue(page, issue);
      await expect(card.locator('a[href="https://archive.org/stream/e2e-cover-fixture/"]'))
        .toHaveCount(1);

      testPassed = true;
    } finally {
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

      await cleanUp('issue', () => deleteMagazineIssue(page, issue));
      await cleanUp('magazine', () => deleteMagazine(page, magazine));
    }
  });

  // TODO: the server half of the archive.org fetch. MagazineIssuesController::
  // fetchImage() calls Http::get() against a hard-coded https://archive.org,
  // so saving an issue with useArchiveOrgCover set makes a real request to a
  // third party that page.route() cannot see - which is why the test above
  // stops at the flag. Extracting the host to config, as follow-up 5 proposes
  // for sndhrecord.atari.org, would make the whole round trip testable.
});
