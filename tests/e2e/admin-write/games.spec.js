import { test, expect } from '../support/test.js';
import { FIXTURE } from '../support/fixture.js';
import {
  uniqueName, uniqueSlug, acceptConfirms, createGame, deleteGame, deleteByAction,
  deleteRow, pickAutocomplete, fillEditor, PNG,
} from '../support/write.js';

/**
 * A row in one of the twenty tables behind /admin/games/config/{type}.
 *
 * Every select on the release system and scene panels is fed by one of these,
 * and the e2e database ships with none of them - the fixture seeds a copy
 * protection and nothing else, because these tables are production data rather
 * than migration data. So a spec that writes to those panels has to bring its
 * own reference row, which is the same rule as everywhere else here: create
 * what you modify, delete it again.
 *
 * They live in this file rather than in support/write.js because nothing
 * outside the release panels needs one yet.
 *
 * The id comes back with the name because a multi-select is asserted on by
 * value - toHaveValues() reads the option's value, not its label - and it is
 * also what makes the delete unambiguous when the name is not unique.
 */
async function createConfigItem(page, type, label, fixedName = null) {
  const name = fixedName ?? uniqueName(label);

  await page.goto(`/admin/games/config/${type}`);

  // '#name' is on the add form *and* on every row's rename form, so the add
  // one has to be reached through the form its button belongs to.
  const add = page.locator('form').filter({
    has: page.getByRole('button', { name: 'Add', exact: true }),
  });
  await add.locator('#name').fill(name);
  await add.getByRole('button', { name: 'Add', exact: true }).click();

  // The new row's two forms both carry the id in their action, and that is the
  // only place on the page it appears.
  const row = page.locator(`form:has(button[title="Delete '${name}'"])`);
  await expect(row).toHaveCount(1);
  const id = (await row.getAttribute('action')).split('/').pop();

  return { type, id, name };
}

/**
 * The rename and the delete form of a config row post to the same URL, so the
 * destructive one is picked by its method override rather than by its action -
 * which is why this is not deleteByAction().
 */
async function deleteConfigItem(page, item) {
  acceptConfirms(page);

  await page.goto(`/admin/games/config/${item.type}`);

  const action = `/config/${item.type}/${item.id}`;
  const form = page.locator(`form[action$="${action}"]:has(input[name="_method"][value="DELETE"])`);
  await expect(form).toHaveCount(1);
  await form.locator('button').click();

  await expect(page.locator(`form[action$="${action}"]`)).toHaveCount(0);
}

/**
 * A release on a game this spec created, landing on the release itself.
 *
 * Saving is what puts the id in the URL, and a release has no name of its own
 * to find it by afterwards - it is a card on its game rather than a row in a
 * searchable table.
 */
async function createRelease(page, game) {
  const name = uniqueName('Release');

  await page.goto(`/admin/games/${game.id}/releases/create`);
  await page.fill('#name', name);
  await page.getByRole('button', { name: 'Save' }).first().click();

  await expect(page).toHaveURL(new RegExp(`/admin/games/${game.id}/releases/\\d+$`));

  return { id: page.url().split('/').pop(), name, gameId: game.id };
}

async function deleteRelease(page, release) {
  await page.goto(`/admin/games/${release.gameId}/releases`);
  await deleteByAction(page, `/releases/${release.id}`);
}

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

      // Assert the save came back, not just that it was made: these selects
      // write game.game_progress_system_id and game.game_series_id, and a
      // dropped key leaves the page rendering happily with an empty field.
      await expect(page.locator('select[name="progress"]'))
        .toHaveValue(String(FIXTURE.progressSystem.id));
      await expect(page.locator('select[name="series"]'))
        .toHaveValue(String(FIXTURE.series.id));

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
      //
      // The one place the autocomplete wire format is asserted end to end: the
      // id written into the hidden companion field comes from the endpoint's
      // JSON, and this is what proves the *right* game was associated rather
      // than merely that a name was rendered. Hence asserting the link's href
      // carries the fixture's id, not just its title.
      await page.goto(`/admin/games/${gameId}/similar`);
      await pickAutocomplete(page, 'similar_name', FIXTURE.game.name);
      await page.getByRole('button', { name: 'Add similar game' }).click();
      await expect(page.getByRole('link', { name: FIXTURE.game.name }))
        .toHaveAttribute('href', new RegExp(`/admin/games/games/${FIXTURE.game.id}/edit$`));

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

/**
 * The panels hanging off a release: /system, /scene, /scans and the scans of a
 * media.
 *
 * Everything here needs a game and a release of its own, and most of it needs a
 * reference row the e2e database does not have - see createConfigItem() above.
 * So the teardown has four layers to undo - the panel row, the release, the
 * game and the config rows - and it runs in a finally, so a failed assertion
 * still leaves the database as it found it.
 */
test.describe('Admin release panels', () => {
  test('adds and removes every row on a release system panel', async ({ page }) => {
    test.setTimeout(180000);
    acceptConfirms(page);

    // A reference row per select this test writes through. The TOS card's
    // Language select is left at '-' deliberately: languages are ISO codes in a
    // table of their own, which the config screen has no form for.
    const items = [];
    let game = null;
    let release = null;

    try {
      const diskProtection = await createConfigItem(page, 'disk-protection', 'Disk Protection');
      const system = await createConfigItem(page, 'system', 'System');
      const enhancement = await createConfigItem(page, 'enhancement', 'Enhancement');
      const memory = await createConfigItem(page, 'memory', 'Memory');
      const tos = await createConfigItem(page, 'tos', 'TOS');
      items.push(diskProtection, system, enhancement, memory, tos);

      game = await createGame(page);
      release = await createRelease(page, game);

      const systemPanel = `/admin/games/${game.id}/${release.id}/system`;
      await page.goto(systemPanel);

      // 1. Disk protection - the one panel here that also stores a free-text
      // note, carried on the pivot rather than on a row of its own.
      const notes = uniqueName('Fuzzy bits');
      await page.selectOption('#disk_protection', { label: diskProtection.name });
      await page.fill('#disk_protection_notes', notes);
      await page.getByRole('button', { name: 'Add disk protection' }).click();

      await expect(page.getByRole('cell', { name: diskProtection.name })).toBeVisible();
      await expect(page.getByRole('cell', { name: notes })).toBeVisible();

      // 2. System enhancement. Addressed by its form rather than by its name,
      // which it does not have: all five add buttons on this page carry
      // id="add" and a spacer <label for="add">&nbsp;</label>, so every one of
      // those labels resolves to the *first* button with that id - this one -
      // and an nbsp label wins over the button's own text. The result is an
      // add button a screen reader announces as blank; the other four keep
      // their names only because nothing points at them. Give the five ids of
      // their own and this can go back to getByRole('button', { name }).
      await page.selectOption('#system', { label: system.name });
      await page.selectOption('#enhancement', { label: enhancement.name });
      await page.locator('form[action$="/system-enhancement"]')
        .getByRole('button').click();

      await expect(page.getByRole('cell', { name: system.name })).toBeVisible();

      // 3. Memory. A PUT rather than an add/remove pair: both selects are
      // multiple, and unselecting everything is how a memory is taken off.
      await page.selectOption('#minimum_memory', { label: memory.name });
      await page.selectOption('#incompatible_memory', { label: memory.name });
      // '/system-memory' exactly - '/system-memory-enhancement' is a card of
      // its own, with a Save button that would match just as well.
      await page.locator('form[action$="/system-memory"]')
        .getByRole('button', { name: 'Save' }).click();

      await expect(page.locator('#minimum_memory')).toHaveValues([memory.id]);
      await expect(page.locator('#incompatible_memory')).toHaveValues([memory.id]);

      // 4. Memory enhancement - a memory plus an optional enhancement, stored
      // as a row rather than as a pivot.
      await page.selectOption('#memory', { label: memory.name });
      await page.selectOption('#memory_enhancement', { label: enhancement.name });
      await page.getByRole('button', { name: 'Add memory enhancement' }).click();

      await expect(page.getByRole('cell', { name: memory.name })).toBeVisible();

      // 5. TOS incompatibility.
      await page.selectOption('#tos', { label: tos.name });
      await page.getByRole('button', { name: 'Add incompatibility' }).click();

      await expect(page.getByRole('cell', { name: tos.name })).toBeVisible();

      // Now take all five away again. Each delete redirects back to this page,
      // so the next locator is resolved against a freshly rendered panel.
      await page.getByRole('button', { name: `Delete protection '${diskProtection.name}'` }).click();
      await page.waitForLoadState('domcontentloaded');
      await expect(page.getByRole('cell', { name: diskProtection.name })).toHaveCount(0);

      // 'system-memory-enhancement/' does not contain 'system-enhancement/',
      // so the trailing slash is what tells the two cards' delete forms apart -
      // and it also excludes the add form, whose action has no id on the end.
      await page.locator('form[action*="/system-enhancement/"] button').click();
      await page.waitForLoadState('domcontentloaded');
      await expect(page.getByRole('cell', { name: system.name })).toHaveCount(0);

      await page.locator('form[action*="/system-memory-enhancement/"] button').click();
      await page.waitForLoadState('domcontentloaded');
      await expect(page.getByRole('cell', { name: memory.name })).toHaveCount(0);

      await page.getByRole('button', { name: `Delete incompatibility '${tos.name}'` }).click();
      await page.waitForLoadState('domcontentloaded');
      await expect(page.getByRole('cell', { name: tos.name })).toHaveCount(0);

      // The memory panel has no delete button - clearing both selects is it.
      await page.selectOption('#minimum_memory', []);
      await page.selectOption('#incompatible_memory', []);
      await page.locator('form[action$="/system-memory"]')
        .getByRole('button', { name: 'Save' }).click();

      await expect(page.locator('#minimum_memory')).toHaveValues([]);
      await expect(page.locator('#incompatible_memory')).toHaveValues([]);
    } finally {
      if (release) {
        await deleteRelease(page, release).catch(() => {});
      }
      if (game) {
        await deleteGame(page, game).catch(() => {});
      }
      for (const item of items) {
        await deleteConfigItem(page, item).catch(() => {});
      }
    }
  });

  test('sets and clears the trainers on a release', async ({ page }) => {
    test.setTimeout(120000);
    acceptConfirms(page);

    let trainer = null;
    let game = null;
    let release = null;

    try {
      trainer = await createConfigItem(page, 'trainer', 'Trainer');
      game = await createGame(page);
      release = await createRelease(page, game);

      // The scene panel is one multi-select and one Save, and the suite has
      // only ever read it. Like the memory panel, unselecting everything is
      // how a trainer comes off again.
      const scene = `/admin/games/${game.id}/${release.id}/scene`;
      await page.goto(scene);
      await page.selectOption('#trainers', { label: trainer.name });
      await page.getByRole('button', { name: 'Save' }).click();

      await expect(page).toHaveURL(new RegExp(`${scene}$`));
      await expect(page.locator('#trainers')).toHaveValues([trainer.id]);

      await page.selectOption('#trainers', []);
      await page.getByRole('button', { name: 'Save' }).click();

      await expect(page.locator('#trainers')).toHaveValues([]);
    } finally {
      if (release) {
        await deleteRelease(page, release).catch(() => {});
      }
      if (game) {
        await deleteGame(page, game).catch(() => {});
      }
      if (trainer) {
        await deleteConfigItem(page, trainer).catch(() => {});
      }
    }
  });

  test('uploads, retypes and deletes a release scan', async ({ page }) => {
    test.setTimeout(120000);
    acceptConfirms(page);

    let game = null;
    let release = null;

    try {
      game = await createGame(page);
      release = await createRelease(page, game);

      await page.goto(`/admin/games/${game.id}/${release.id}/scans`);

      // FilePond replaces the plain file input with a browse input of its own,
      // and only that one is wired to the pond that uploads the file. See the
      // dump upload in the full-game test above for the same trick.
      const fileInput = page.locator('form[action$="/scans"] input.filepond--browser').first();
      await expect(fileInput).toBeAttached();
      await fileInput.setInputFiles({
        name: 'e2e-box-front.png',
        mimeType: 'image/png',
        buffer: PNG,
      });

      // Enabled by FilePond's 'processfiles', i.e. once the file has reached
      // the temporary store.
      const upload = page.locator('#upload');
      await expect(upload).toBeEnabled({ timeout: 30000 });
      await upload.click();

      // The type is inferred from the filename by the controller, which is the
      // only reason the file above is called 'front' rather than anything.
      await expect(page.getByRole('heading', { name: '1 scans' })).toBeVisible();
      const scan = page.locator('form:has(select[name="type"])');
      await expect(scan.locator('select[name="type"]')).toHaveValue('Box front');

      // The update form: a type and a note, on the row rather than in a modal.
      const notes = uniqueName('Publisher catalog');
      await scan.locator('select[name="type"]').selectOption('Goodie');
      await scan.locator('input[name="notes"]').fill(notes);
      await scan.getByRole('button', { name: 'Update' }).click();

      await expect(page.locator('select[name="type"]')).toHaveValue('Goodie');
      await expect(page.locator('input[name="notes"]')).toHaveValue(notes);

      // Not exact: the trash icon is a font glyph rendered through ::before,
      // and it lands in the button's accessible name as a leading character -
      // so the name is ' Delete' rather than 'Delete'.
      await page.getByRole('button', { name: 'Delete' }).click();
      await page.waitForLoadState('domcontentloaded');
      await expect(page.getByText('No scans for the release.')).toBeVisible();
    } finally {
      if (release) {
        await deleteRelease(page, release).catch(() => {});
      }
      if (game) {
        await deleteGame(page, game).catch(() => {});
      }
    }
  });

  test('uploads, retypes and deletes a media scan', async ({ page }) => {
    test.setTimeout(120000);
    acceptConfirms(page);

    const items = [];
    let game = null;
    let release = null;

    try {
      // ReleaseMediasScansController::store() types every upload by looking up
      // the media scan type *named* 'Other' - MediaScanType::TYPE_OTHER - so
      // that row is not decoration here, it is what makes the POST work at all.
      // Hence the fixed name rather than a uniqueName one.
      const otherType = await createConfigItem(page, 'media-scan-type', 'Media Scan Type', 'Other');
      const scanType = await createConfigItem(page, 'media-scan-type', 'Media Scan Type');
      items.push(otherType, scanType);

      game = await createGame(page);
      release = await createRelease(page, game);

      const medias = `/admin/games/${game.id}/${release.id}/medias`;
      await page.goto(medias);
      await page.getByRole('button', { name: 'Add media' }).click();
      await expect(page.getByRole('heading', { name: '1 media' })).toBeVisible();

      // Each media renders its own pond and its own upload button, keyed on
      // the media id - which is also the only place that id is readable from.
      const mediaId = await page.locator('[data-upload-media]').getAttribute('data-upload-media');

      const fileInput = page
        .locator(`form[action$="/${mediaId}/scans"] input.filepond--browser`)
        .first();
      await expect(fileInput).toBeAttached();
      await fileInput.setInputFiles({
        name: 'e2e-media-label.png',
        mimeType: 'image/png',
        buffer: PNG,
      });

      const upload = page.locator(`[data-upload-media="${mediaId}"]`);
      await expect(upload).toBeEnabled({ timeout: 30000 });
      await upload.click();

      await expect(page.getByRole('button', { name: 'Remove scan' })).toBeVisible();

      // The scan's own forms are the ones with an id after '/scans/'; the
      // media's info card posts to '/medias/{media}' and carries a
      // select[name="type"] of its own, so the action is what separates them.
      const scan = page.locator('form[action*="/scans/"]:has(select[name="type"])');
      await expect(scan.locator('select[name="type"]')).toHaveValue(otherType.id);

      await scan.locator('select[name="type"]').selectOption(scanType.id);
      await scan.getByRole('button', { name: 'Update' }).click();

      await expect(
        page.locator('form[action*="/scans/"] select[name="type"]')
      ).toHaveValue(scanType.id);

      await page.getByRole('button', { name: 'Remove scan' }).click();
      await page.waitForLoadState('domcontentloaded');
      await expect(page.getByText('No scans for this media.')).toBeVisible();

      // The media goes too: it is a child of the release, and the release
      // cannot be tidied away underneath it.
      await page.getByRole('button', { name: 'Delete media' }).click();
      await page.waitForLoadState('domcontentloaded');
      await expect(page.getByRole('heading', { name: '0 media' })).toBeVisible();
    } finally {
      if (release) {
        await deleteRelease(page, release).catch(() => {});
      }
      if (game) {
        await deleteGame(page, game).catch(() => {});
      }
      for (const item of items) {
        await deleteConfigItem(page, item).catch(() => {});
      }
    }
  });

  // TODO: the release system info card (resolutions, systems, emulators), the
  // dump update form, several scans in one upload.
});

/**
 * The music panel on a game: the SNDH picker, the candidates the archive
 * suggests, and taking a song off again.
 *
 * The seeded tune is referenced rather than written to. The game is the parent
 * here and the tune is a peer - the same bargain the full-game test above
 * strikes with the seeded company and individual.
 */
test.describe('Admin game music', () => {
  test('associates a song through the picker and removes it', async ({ page }) => {
    const game = await createGame(page);
    const music = `/admin/games/${game.id}/music`;

    try {
      await page.goto(music);
      await expect(page.getByText('No music associated with the game yet.')).toBeVisible();

      // The picker is the point: `song` is what the admin types into, and the
      // controller reads the hidden `sndh` that its onSelection fills. Typing
      // the title and pressing Add without choosing a suggestion posts nothing.
      await pickAutocomplete(page, 'song', FIXTURE.sndh.title);
      await page.getByRole('button', { name: 'Add song' }).click();

      await expect(page).toHaveURL(new RegExp(`${music}$`));
      await expect(page.getByRole('cell', { name: FIXTURE.sndh.id })).toBeVisible();

      acceptConfirms(page);
      await page.getByRole('button', { name: `Delete song '${FIXTURE.sndh.id}'` }).click();

      await expect(page).toHaveURL(new RegExp(`${music}$`));
      await expect(page.getByText('No music associated with the game yet.')).toBeVisible();
    } finally {
      await deleteGame(page, game).catch(() => {});
    }
  });

  test('associates a song the candidates card suggests', async ({ page }) => {
    // The candidates are a full-text match of the *game name* against the SNDH
    // titles, so this game has to be named after the tune to be offered it -
    // which is the only way to reach the associate route through the UI.
    const name = `${FIXTURE.sndh.title} ${uniqueName('Music Game')}`;
    let game = null;

    try {
      await page.goto('/admin/games/games/create');
      await page.fill('#name', name);
      await page.fill('#slug', uniqueSlug(name));
      await page.getByRole('button', { name: 'Save' }).first().click();

      await expect(page).toHaveURL(/\/admin\/games\/games\/\d+\/edit$/);
      game = { id: page.url().split('/').at(-2), name };

      const music = `/admin/games/${game.id}/music`;
      await page.goto(music);

      // Checked by its value rather than by its label: the label is the
      // title, and the archive shipped by the migrations has titles of its own
      // that the same match can turn up.
      await page.locator(`input[name="associations[]"][value="${FIXTURE.sndh.id}"]`).check();
      await page.getByRole('button', { name: 'Associate' }).click();

      await expect(page).toHaveURL(new RegExp(`${music}$`));
      await expect(page.getByRole('cell', { name: FIXTURE.sndh.id })).toBeVisible();

      acceptConfirms(page);
      await page.getByRole('button', { name: `Delete song '${FIXTURE.sndh.id}'` }).click();

      await expect(page.getByText('No music associated with the game yet.')).toBeVisible();
    } finally {
      if (game) {
        await deleteGame(page, game).catch(() => {});
      }
    }
  });
});
