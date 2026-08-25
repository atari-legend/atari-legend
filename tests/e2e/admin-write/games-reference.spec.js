import { test, expect } from '../support/test.js';
import { FIXTURE } from '../support/fixture.js';
import { expectResourceLoads } from '../support/assertions.js';
import {
  PNG, uniqueName, acceptConfirms, createGame, deleteGame, addGameScreenshot,
  deleteGameScreenshot, createIndividual, deleteIndividual, deleteRow,
  pickAutocomplete,
} from '../support/write.js';

/**
 * The reference data behind a game: individuals, companies, series, and the
 * configuration tables every game and release form selects from.
 *
 * The admin project loads all of these screens and asserts they render; what
 * happens when you actually submit one of their forms is what lives here. Each
 * test creates its own individual, company, series or config row - never the
 * seeded ones, which public/interviews.spec.js, public/games.spec.js and the
 * game search specs all assert on.
 */
test.describe('Admin games reference data', () => {
  test('adds and removes a nickname on an individual', async ({ page }) => {
    const individual = await createIndividual(page);
    const nickname = uniqueName('Nick');
    const edit = `/admin/games/individuals/${individual.id}/edit`;

    // The nicknames card is a second form on the individual's edit screen.
    // A nickname is itself a row in `individuals`, linked back through
    // individual_nicks, which is why it has to be a unique name of its own.
    await page.goto(edit);
    await page.fill('#nickname', nickname);
    await page.getByRole('button', { name: 'Add nickname' }).click();

    await expect(page).toHaveURL(new RegExp(`${edit}$`));
    await expect(page.getByText(nickname)).toBeVisible();

    // The delete control is an icon-only <a> inside the form it submits, so
    // there is no accessible name to find it by - the id in the action is the
    // only thing that identifies it, and this individual has exactly one.
    acceptConfirms(page);
    const remove = page.locator('form[action*="/nickname/"]');
    await expect(remove).toHaveCount(1);
    await remove.locator('a').click();

    await expect(page).toHaveURL(new RegExp(`${edit}$`));
    await expect(page.getByText(nickname)).toHaveCount(0);

    await deleteIndividual(page, individual);
  });

  test('uploads and removes an individual avatar', async ({ page }) => {
    const individual = await createIndividual(page);
    const edit = `/admin/games/individuals/${individual.id}/edit`;
    const avatar = page.getByRole('img', { name: 'Individual avatar' });

    // The avatar rides on the individual's own form rather than on a route of
    // its own: update() stores the file and writes the extension it sniffed
    // into individuals.ind_imgext, and Individual::getAvatarAttribute()
    // builds the URL back out of it. So the assertion is on the src, which is
    // the only place the round trip is visible.
    await page.goto(edit);
    await expect(avatar).toHaveAttribute('src', /images\/unknown\.jpg$/);

    await page.locator('input[name="avatar"]').setInputFiles({
      name: 'avatar.png',
      mimeType: 'image/png',
      buffer: PNG,
    });
    await page.getByRole('button', { name: 'Save' }).click();

    // update() redirects to the index, so the record has to be re-opened.
    await expect(page).toHaveURL(/\/admin\/games\/individuals$/);
    await page.goto(edit);
    await expect(avatar).toHaveAttribute(
      'src',
      new RegExp(`images/individual_screenshots/${individual.id}\\.png$`)
    );

    // And the file itself, fetched rather than left to the <img>: the guard in
    // support/test.js exempts /storage/, because those files belong to rows the
    // write projects are creating and deleting all around this one.
    const stored = new URL(await avatar.getAttribute('src')).pathname;
    await expectResourceLoads(await page.request.get(stored), stored, { magic: 'PNG' });

    // Saving anything else must not take the avatar with it. The form only
    // sends the file field when a file was chosen, so update() has to keep the
    // extension already on record - it used to write null instead, which
    // dropped the avatar on the next unrelated edit and stranded the file.
    await page.fill('#name', `${individual.name} edited`);
    await page.getByRole('button', { name: 'Save' }).click();
    await page.goto(edit);
    await expect(avatar).toHaveAttribute(
      'src',
      new RegExp(`images/individual_screenshots/${individual.id}\\.png$`)
    );

    // The trash next to the avatar is a button with an icon and no title, whose
    // onclick submits the hidden DELETE form - which is what names it here.
    acceptConfirms(page);
    await page.locator('button[onclick*="delete-avatar"]').click();

    await expect(page).toHaveURL(new RegExp(`${edit}$`));
    await expect(avatar).toHaveAttribute('src', /images\/unknown\.jpg$/);

    await deleteIndividual(page, individual);
  });

  test('uploads and removes a company logo', async ({ page }) => {
    const name = uniqueName('Company');
    const logo = page.getByRole('img', { name: 'Company logo' });

    await page.goto('/admin/games/companies/create');
    await page.fill('#name', name);
    await page.getByRole('button', { name: 'Save' }).click();

    await expect(page).toHaveURL(/\/admin\/games\/companies\/\d+\/edit$/);
    const edit = new URL(page.url()).pathname;
    const id = edit.split('/').at(-2);

    await expect(logo).toHaveAttribute('src', /images\/image-placeholder\.png$/);

    await page.locator('input[name="logo"]').setInputFiles({
      name: 'logo.png',
      mimeType: 'image/png',
      buffer: PNG,
    });
    await page.getByRole('button', { name: 'Save' }).click();

    await expect(page).toHaveURL(/\/admin\/games\/companies$/);
    await page.goto(edit);
    await expect(logo).toHaveAttribute(
      'src',
      new RegExp(`images/company_logos/${id}\\.png$`)
    );

    const stored = new URL(await logo.getAttribute('src')).pathname;
    await expectResourceLoads(await page.request.get(stored), stored, { magic: 'PNG' });

    // Same trap as the avatar above: a save that is not about the logo must
    // leave the logo alone.
    await page.fill('#name', `${name} edited`);
    await page.getByRole('button', { name: 'Save' }).click();
    await page.goto(edit);
    await expect(logo).toHaveAttribute(
      'src',
      new RegExp(`images/company_logos/${id}\\.png$`)
    );

    acceptConfirms(page);
    await page.locator('button[onclick*="delete-logo"]').click();

    await expect(page).toHaveURL(new RegExp(`${edit}$`));
    await expect(logo).toHaveAttribute('src', /images\/image-placeholder\.png$/);

    await page.goto('/admin/games/companies');
    await deleteRow(page, name);
  });

  test('adds a game to a series and removes it', async ({ page }) => {
    const game = await createGame(page);
    const name = uniqueName('Series');

    await page.goto('/admin/games/series/create');
    await page.fill('#name', name);
    await page.getByRole('button', { name: 'Save' }).click();

    await expect(page).toHaveURL(/\/admin\/games\/series\/\d+\/edit$/);
    const edit = new URL(page.url()).pathname;

    // Its own series and its own game: a series is shown on every game page in
    // it, and FIXTURE.series is what the games search spec browses by.
    await expect(page.getByText('No games')).toBeVisible();
    await pickAutocomplete(page, 'game_name', game.name);
    await page.getByRole('button', { name: 'Add game' }).click();

    await expect(page).toHaveURL(new RegExp(`${edit}$`));
    await expect(page.getByRole('link', { name: game.name })).toBeVisible();

    acceptConfirms(page);
    await page.getByRole('button', { name: `Remove game '${game.name}'` }).click();

    await expect(page).toHaveURL(new RegExp(`${edit}$`));
    await expect(page.getByText('No games')).toBeVisible();

    // Only now: GameSeriesController::destroy() refuses a series that still
    // has games, and the table renders a disabled button rather than a form -
    // so deleteRow() would not even find a row to click.
    await page.goto('/admin/games/series');
    await deleteRow(page, name);

    await deleteGame(page, game);
  });

  test('sets the genres of a game from the issues screen', async ({ page }) => {
    const game = await createGame(page);
    const screenshot = await addGameScreenshot(page, game);

    // The card picks *one* game at random out of every game that has a
    // screenshot and no genres - IssuesController::index() ends in
    // ->shuffle()->first() - so which one is on screen is a coin toss whenever
    // another write spec has a game in the same state. Reloading until ours
    // comes up is the only way to drive this form; a game that never appears
    // would fail here rather than quietly pass on somebody else's row.
    const heading = page.getByRole('heading', { name: new RegExp(game.name) });
    await expect(async () => {
      await page.goto('/admin/games/issues');
      await expect(heading).toBeVisible({ timeout: 2000 });
    }).toPass({ timeout: 60000 });

    // The genre is only selected, never written to, which is what reference
    // data is for - see tests/e2e/README.md.
    await page.locator(`#genre_${FIXTURE.genre.id}`).check();
    await page.getByRole('button', { name: 'Set genres' }).click();

    await expect(page).toHaveURL(/\/admin\/games\/issues$/);

    // The card has no "saved" state of its own; the genre shows up on the
    // game's own form, which is where it is asserted.
    await page.goto(`/admin/games/games/${game.id}/edit`);
    await expect(page.locator('select[name="genres[]"]')).toHaveValues([
      String(FIXTURE.genre.id),
    ]);

    // Un-set it again: a genre does not block Game::getIsDeletableAttribute(),
    // but leaving the cross row behind would outlive the game.
    await page.selectOption('select[name="genres[]"]', []);
    await page.getByRole('button', { name: 'Save' }).first().click();

    await expect(page).toHaveURL(new RegExp(`/admin/games/games/${game.id}/edit$`));
    await expect(page.locator('select[name="genres[]"]')).toHaveValues([]);

    // A game with a screenshot is not deletable.
    await deleteGameScreenshot(page, game, screenshot);
    await deleteGame(page, game);
  });

  test('adds, edits inline and deletes a configuration row', async ({ page }) => {
    // One config type stands in for all twenty: GameConfigurationController is
    // generic over CONFIG_TYPES_TABLES, and 'trainer' is one of the few whose
    // table nothing in FIXTURE points at. Its rows have a name and nothing
    // else, so this is the editing idiom rather than the fields.
    const type = 'trainer';
    const index = `/admin/games/config/${type}`;
    const name = uniqueName('Trainer');
    const renamed = `${name} edited`;

    // Unlike every other admin screen in this suite the rows here are edited
    // in place: each one is its own PUT form, with the value in an input and
    // an Update button beside it. The add form is the only one posting to the
    // bare index, which is what tells the two apart - every other form on the
    // page carries a row id.
    const row = (value) => page.locator(`form[action*="/config/${type}/"]`)
      .filter({ has: page.locator(`input[name="name"][value="${value}"]`) });

    await page.goto(index);
    const add = page.locator(`form[action$="/config/${type}"]`);
    await add.locator('#name').fill(name);
    await add.getByRole('button', { name: 'Add' }).click();

    await expect(page).toHaveURL(new RegExp(`${index}$`));
    await expect(row(name)).toHaveCount(1);

    // The inline edit. The `value` attribute is what the server rendered, so
    // it still reads `name` after the fill and only changes once the reload
    // has been through the controller - which is exactly the assertion.
    await row(name).locator('input[name="name"]').fill(renamed);
    await row(name).getByRole('button', { name: 'Update' }).click();

    await expect(page).toHaveURL(new RegExp(`${index}$`));
    await expect(row(renamed)).toHaveCount(1);
    await expect(row(name)).toHaveCount(0);

    acceptConfirms(page);
    await page.getByRole('button', { name: `Delete '${renamed}'` }).click();

    await expect(page).toHaveURL(new RegExp(`${index}$`));
    await expect(row(renamed)).toHaveCount(0);
  });

  // TODO: the crew genealogy picker on an individual, a company's developer and
  // publisher cards, the other four cards on /admin/games/issues.
});
