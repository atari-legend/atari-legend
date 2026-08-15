import { test, expect } from '../support/test.js';
import { FIXTURE } from '../support/fixture.js';
import { uniqueName, acceptConfirms, deleteByAction, deleteRow } from '../support/write.js';

test.describe('Admin games', () => {
  test('adds and removes an AKA on a game', async ({ page }) => {
    const aka = uniqueName('AKA');
    const edit = `/admin/games/games/${FIXTURE.game.id}/edit`;

    // The AKA card is a second form on the game's edit screen, posting to its
    // own route. It is the game section's smallest complete round-trip, and
    // unlike the game itself it can be undone - see the TODO below.
    await page.goto(edit);
    await page.fill('#aka', aka);
    await page.getByRole('button', { name: 'Add AKA' }).click();

    await expect(page).toHaveURL(new RegExp(`${edit}$`));
    await expect(page.getByText(aka)).toBeVisible();

    // The delete buttons here are icons with a descriptive title, which is
    // what gives them an accessible name to find them by.
    acceptConfirms(page);
    await page.getByRole('button', { name: `Delete AKA '${aka}'` }).click();

    await expect(page.getByText(aka)).toHaveCount(0);
  });

  test('creates and deletes a release', async ({ page }) => {
    const name = uniqueName('Release');
    const releases = `/admin/games/${FIXTURE.game.id}/releases`;

    await page.goto(`${releases}/create`);
    await page.fill('#name', name);
    await page.getByRole('button', { name: 'Save' }).first().click();

    // Saving lands on the release, which is where its id comes from - a
    // release has no name of its own to find it by afterwards.
    await expect(page).toHaveURL(new RegExp(`${releases}/\\d+$`));
    const releaseId = page.url().split('/').pop();

    // Releases are listed as cards on the game, not in a searchable table.
    await page.goto(releases);
    await deleteByAction(page, `/releases/${releaseId}`);
  });

  test('creates and deletes a game', async ({ page }) => {
    const name = uniqueName('Game');

    await page.goto('/admin/games/games/create');
    await page.fill('#name', name);
    // The slug is optional to the validator, but the games table links every
    // row to its public page, and route('games.show') on a null slug throws.
    await page.fill('#slug', `e2e-game-${Date.now()}`);
    await page.getByRole('button', { name: 'Save' }).first().click();

    await expect(page).toHaveURL(/\/admin\/games\/games\/\d+\/edit$/);

    // A game is deletable only while nothing references it, which is exactly
    // what a game created a moment ago looks like. On any other game the
    // button is disabled, and this would time out rather than quietly pass.
    await page.goto('/admin/games/games');
    await deleteRow(page, name);
  });

  // TODO: the panels that hang off a release - medias, dumps, scans - and the
  // screenshot upload, which goes through Filepond.
});
