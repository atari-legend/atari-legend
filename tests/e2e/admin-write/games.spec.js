import { test, expect } from '../support/test.js';
import { FIXTURE } from '../support/fixture.js';
import {
  uniqueName, uniqueSlug, acceptConfirms, createGame, deleteGame, deleteByAction,
  deleteRow, pickAutocomplete, fillEditor,
} from '../support/write.js';

test.describe('Admin games', () => {
  test('adds and removes an AKA on a game', async ({ page }) => {
    const aka = uniqueName('AKA');
    const game = await createGame(page);
    const edit = `/admin/games/games/${game.id}/edit`;

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

    // An AKA is not one of the things Game::getIsDeletableAttribute() blocks
    // on, so this would pass with the AKA still attached. Deleting it is the
    // assertion above; deleting the game is only tidying up.
    await deleteGame(page, game);
  });

  test('creates and deletes a release', async ({ page }) => {
    const name = uniqueName('Release');
    const game = await createGame(page);
    const releases = `/admin/games/${game.id}/releases`;

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

    // Only now: a game with a release on it is not deletable.
    await deleteGame(page, game);
  });

  test('creates and deletes a game', async ({ page }) => {
    const name = uniqueName('Game');

    await page.goto('/admin/games/games/create');
    await page.fill('#name', name);
    // The slug is optional to the validator, but the games table links every
    // row to its public page, and route('games.show') on a null slug throws.
    await page.fill('#slug', uniqueSlug(name));
    await page.getByRole('button', { name: 'Save' }).first().click();

    await expect(page).toHaveURL(/\/admin\/games\/games\/\d+\/edit$/);

    // A game is deletable only while nothing references it, which is exactly
    // what a game created a moment ago looks like. On any other game the
    // button is disabled, and this would time out rather than quietly pass.
    await page.goto('/admin/games/games');
    await deleteRow(page, name);
  });

  test('completely creates a game with all details and relations, then deletes everything', async ({ page }) => {
    test.setTimeout(180000);
    acceptConfirms(page);

    // The video step embeds a real YouTube URL. The title and author are
    // fetched by the server, not the browser, so there is nothing to stub here
    // - the controller falls back to the video ID when YouTube is unreachable,
    // and the embed itself is blocked so the test never waits on the network.
    await page.route('**youtube**', (route) => route.abort());

    // The seeded company, individual and game below are associated *to* the
    // game this test creates, not written to: the new game is the parent and
    // they are peers, so nothing appears under them for a read spec to trip
    // over. That is the line the rule draws - see tests/e2e/README.md.
    const gameName = uniqueName('Full Game');
    const slug = uniqueSlug(gameName);
    let testPassed = false;
    let gameId = null;
    let releaseId = null;
    let akaName = null;
    let releaseAka = null;
    let factText = null;

    try {
      // 1. Create Game
      await page.goto('/admin/games/games/create');
      await page.fill('#name', gameName);
      await page.fill('#slug', slug);
      await page.getByRole('button', { name: 'Save' }).first().click();

      await expect(page).toHaveURL(/\/admin\/games\/games\/\d+\/edit$/);
      gameId = page.url().split('/').at(-2);

      // 2. Base Info & Multiplayer & AKA & VS
      await page.selectOption('select[name="port"]', { label: FIXTURE.port.name });
      await page.selectOption('select[name="progress"]', { label: FIXTURE.progressSystem.name });
      await page.selectOption('select[name="series"]', { label: FIXTURE.series.name });
      await page.selectOption('select[name="genres[]"]', { label: FIXTURE.genre.name });
      await page.getByRole('button', { name: 'Save' }).first().click();

      await page.fill('input[name="players"]', '2');
      await page.getByRole('button', { name: 'Save' }).nth(1).click();

      akaName = uniqueName('Game AKA');
      await page.fill('#aka', akaName);
      await page.getByRole('button', { name: 'Add AKA' }).click();
      await expect(page.getByRole('cell', { name: akaName })).toBeVisible();

      await page.fill('#amiga_id', '99988');
      await page.fill('#lemon64_slug', 'e2e-vs-slug');
      await page.getByRole('button', { name: 'Add Versus' }).click();
      await expect(page.getByRole('cell', { name: '99988' })).toBeVisible();

      // 3. Credits & Developers
      await page.goto(`/admin/games/${gameId}/credits`);
      await pickAutocomplete(page, 'developer_name', FIXTURE.company.name);
      await page.getByRole('button', { name: 'Add developer' }).click();
      await expect(page.getByRole('link', { name: FIXTURE.company.name })).toBeVisible();

      await pickAutocomplete(page, 'individual_name', FIXTURE.individual.name);
      await page.getByRole('button', { name: 'Add credit' }).click();
      await expect(page.getByRole('link', { name: FIXTURE.individual.name })).toBeVisible();

      // 4. Facts
      await page.goto(`/admin/games/${gameId}/facts/create`);
      factText = uniqueName('Fact text');
      await fillEditor(page, 'content', factText);
      await page.getByRole('button', { name: 'Save' }).click();
      await expect(page).toHaveURL(new RegExp(`/admin/games/${gameId}/facts$`));
      await expect(page.getByText(factText).first()).toBeVisible();

      // 5. Videos
      await page.goto(`/admin/games/${gameId}/videos`);
      await page.fill('input[name="video"]', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ');
      await page.getByRole('button', { name: 'Add video' }).click();
      await expect(page.locator('iframe[src*="youtube"]')).toBeVisible();

      // 6. Similar Games
      await page.goto(`/admin/games/${gameId}/similar`);
      await pickAutocomplete(page, 'similar_name', FIXTURE.game.name);
      await page.getByRole('button', { name: 'Add similar game' }).click();
      await expect(page.getByRole('link', { name: FIXTURE.game.name })).toBeVisible();

      // 7. Release Creation & System details
      const releaseName = uniqueName('Full Release');
      await page.goto(`/admin/games/${gameId}/releases/create`);
      await page.fill('#name', releaseName);
      await page.selectOption('select[name="year"]', '1990');
      await page.getByRole('button', { name: 'Save' }).first().click();

      await expect(page).toHaveURL(new RegExp(`/admin/games/${gameId}/releases/\\d+$`));
      releaseId = page.url().split('/').pop();

      releaseAka = uniqueName('Release AKA');
      await page.fill('#aka', releaseAka);
      await page.getByRole('button', { name: 'Add AKA' }).click();
      await expect(page.getByRole('cell', { name: releaseAka })).toBeVisible();

      await page.goto(`/admin/games/${gameId}/${releaseId}/system`);
      await page.selectOption('#copy_protection', { label: FIXTURE.copyProtection.name });
      await page.getByRole('button', { name: 'Add copy protection' }).click();
      await expect(page.getByRole('button', { name: /Delete protection/ })).toBeVisible();

      // 8. Release Media & Dump
      await page.goto(`/admin/games/${gameId}/${releaseId}/medias`);
      await page.getByRole('button', { name: 'Add media' }).click();
      await expect(page.getByRole('heading', { name: '1 media' })).toBeVisible();

      // FilePond replaces the plain file input with a browse input of its own,
      // and only that one is wired to the pond that uploads the file. The
      // original is still in the DOM for a frame or two after the first pond
      // has rendered, so waiting on '.filepond--root' is not enough: files set
      // on the input FilePond is about to discard go nowhere, and the upload
      // button below then never enables.
      const dumpPayload = Buffer.from('Atari ST dummy dump content for testing e2e');
      const fileInput = page.locator('form[action*="dumps"] input.filepond--browser').first();
      await expect(fileInput).toBeAttached();
      await fileInput.setInputFiles({
        name: 'test_dump.st',
        mimeType: 'application/octet-stream',
        buffer: dumpPayload,
      });
      // Enabled by FilePond's 'processfiles', i.e. once the dump has been
      // uploaded to the temporary store - a second or two locally, and this is
      // the one step of this spec that waits on a real upload.
      const uploadBtn = page.locator('button[data-upload-dump]').first();
      await expect(uploadBtn).toBeEnabled({ timeout: 30000 });
      await uploadBtn.click();
      await expect(page.getByRole('button', { name: 'Delete dump' }).first()).toBeVisible();

      // Delete Dump
      await page.getByRole('button', { name: 'Delete dump' }).first().click();
      await page.waitForLoadState('domcontentloaded');
      await expect(page.getByText('No dumps for this media.')).toBeVisible();

      // 9. Teardown / Cleanup
      // Delete Media
      await page.getByRole('button', { name: 'Delete media' }).first().click();
      await page.waitForLoadState('domcontentloaded');
      await expect(page.getByRole('heading', { name: '0 media' })).toBeVisible();

      // Delete System protection
      await page.goto(`/admin/games/${gameId}/${releaseId}/system`);
      await page.getByRole('button', { name: /Delete protection/ }).first().click();
      await page.waitForLoadState('domcontentloaded');

      // Delete Release AKA
      await page.goto(`/admin/games/${gameId}/releases/${releaseId}`);
      await page.getByRole('button', { name: `Delete AKA '${releaseAka}'` }).click();
      await page.waitForLoadState('domcontentloaded');
      await expect(page.getByText(releaseAka)).toHaveCount(0);

      // Delete Release
      await page.goto(`/admin/games/${gameId}/releases`);
      await deleteByAction(page, `/releases/${releaseId}`);

      // Delete Game AKA & VS
      await page.goto(`/admin/games/games/${gameId}/edit`);
      await page.getByRole('button', { name: `Delete AKA '${akaName}'` }).click();
      await page.waitForLoadState('domcontentloaded');
      await expect(page.getByText('No AKA for this game yet.')).toBeVisible();

      await page.getByRole('button', { name: 'Delete Versus' }).click();
      await page.waitForLoadState('domcontentloaded');
      await expect(page.getByText('No Versus for this game yet.')).toBeVisible();

      // Delete Credits & Developers
      await page.goto(`/admin/games/${gameId}/credits`);
      await page.getByRole('button', { name: `Delete credit '${FIXTURE.individual.name}'` }).click();
      await page.waitForLoadState('domcontentloaded');
      await expect(page.getByRole('link', { name: FIXTURE.individual.name })).toHaveCount(0);

      await page.getByRole('button', { name: `Delete developer '${FIXTURE.company.name}'` }).click();
      await page.waitForLoadState('domcontentloaded');
      await expect(page.getByRole('link', { name: FIXTURE.company.name })).toHaveCount(0);

      // Delete Fact
      await page.goto(`/admin/games/${gameId}/facts`);
      await page.getByRole('button', { name: 'Delete fact' }).click();
      await page.waitForLoadState('domcontentloaded');
      await expect(page.getByText(factText)).toHaveCount(0);

      // Delete Video
      await page.goto(`/admin/games/${gameId}/videos`);
      await page.getByRole('button', { name: 'Remove video' }).click();
      await page.waitForLoadState('domcontentloaded');
      await expect(page.locator('iframe[src*="youtube"]')).toHaveCount(0);

      // Delete Similar Game
      await page.goto(`/admin/games/${gameId}/similar`);
      await page.getByRole('button', { name: `Delete similar game '${FIXTURE.game.name}'` }).click();
      await page.waitForLoadState('domcontentloaded');

      // 10. Delete Game
      await page.goto('/admin/games/games');
      await deleteRow(page, gameName);
      testPassed = true;
    } finally {
      if (!testPassed && gameId) {
        // Fallback cleanup if test failed midway
        if (releaseId) {
          try {
            await page.goto(`/admin/games/${gameId}/${releaseId}/medias`);
            const dumpBtn = page.locator('button[title*="Delete dump"]').first();
            if (await dumpBtn.isVisible().catch(() => false)) {
              await dumpBtn.click();
              await page.waitForLoadState('domcontentloaded');
            }
            const mediaBtn = page.locator('button[title*="Delete media"]').first();
            if (await mediaBtn.isVisible().catch(() => false)) {
              await mediaBtn.click();
              await page.waitForLoadState('domcontentloaded');
            }
          } catch {}

          try {
            await page.goto(`/admin/games/${gameId}/${releaseId}/system`);
            const protBtn = page.getByRole('button', { name: /Delete protection/ }).first();
            if (await protBtn.isVisible().catch(() => false)) {
              await protBtn.click();
              await page.waitForLoadState('domcontentloaded');
            }
          } catch {}

          try {
            await page.goto(`/admin/games/${gameId}/releases/${releaseId}`);
            if (releaseAka) {
              const relAkaBtn = page.getByRole('button', { name: `Delete AKA '${releaseAka}'` });
              if (await relAkaBtn.isVisible().catch(() => false)) {
                await relAkaBtn.click();
                await page.waitForLoadState('domcontentloaded');
              }
            }
          } catch {}

          try {
            await page.goto(`/admin/games/${gameId}/releases`);
            await deleteByAction(page, `/releases/${releaseId}`).catch(() => {});
          } catch {}
        }

        try {
          await page.goto(`/admin/games/games/${gameId}/edit`);
          if (akaName) {
            const akaBtn = page.getByRole('button', { name: `Delete AKA '${akaName}'` });
            if (await akaBtn.isVisible().catch(() => false)) {
              await akaBtn.click();
              await page.waitForLoadState('domcontentloaded');
            }
          }
          const vsBtn = page.getByRole('button', { name: 'Delete Versus' });
          if (await vsBtn.isVisible().catch(() => false)) {
            await vsBtn.click();
            await page.waitForLoadState('domcontentloaded');
          }
        } catch {}

        try {
          await page.goto(`/admin/games/${gameId}/credits`);
          const creditBtn = page.getByRole('button', { name: `Delete credit '${FIXTURE.individual.name}'` });
          if (await creditBtn.isVisible().catch(() => false)) {
            await creditBtn.click();
            await page.waitForLoadState('domcontentloaded');
          }
          const devBtn = page.getByRole('button', { name: `Delete developer '${FIXTURE.company.name}'` });
          if (await devBtn.isVisible().catch(() => false)) {
            await devBtn.click();
            await page.waitForLoadState('domcontentloaded');
          }
        } catch {}

        try {
          await page.goto(`/admin/games/${gameId}/facts`);
          const factBtn = page.getByRole('button', { name: 'Delete fact' });
          if (await factBtn.isVisible().catch(() => false)) {
            await factBtn.click();
            await page.waitForLoadState('domcontentloaded');
          }
        } catch {}

        try {
          await page.goto(`/admin/games/${gameId}/videos`);
          const videoBtn = page.getByRole('button', { name: 'Remove video' });
          if (await videoBtn.isVisible().catch(() => false)) {
            await videoBtn.click();
            await page.waitForLoadState('domcontentloaded');
          }
        } catch {}

        try {
          await page.goto(`/admin/games/${gameId}/similar`);
          const simBtn = page.getByRole('button', { name: `Delete similar game '${FIXTURE.game.name}'` });
          if (await simBtn.isVisible().catch(() => false)) {
            await simBtn.click();
            await page.waitForLoadState('domcontentloaded');
          }
        } catch {}

        try {
          await page.goto('/admin/games/games');
          await deleteRow(page, gameName).catch(() => {});
        } catch {}
      }
    }
  });
});
