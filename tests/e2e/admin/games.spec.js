import { test, expect } from '../support/test.js';
import { FIXTURE } from '../support/fixture.js';
import { expectPageRenders } from '../support/assertions.js';

test.describe('Admin games', () => {
  test('lists games', async ({ page }) => {
    const response = await page.goto('/admin/games/games');

    await expectPageRenders(page, response, '/admin/games/games');
    await expect(page.getByText(FIXTURE.game.name).first()).toBeVisible();
  });

  test('opens the edit form for a game', async ({ page }) => {
    const path = `/admin/games/games/${FIXTURE.game.id}/edit`;
    const response = await page.goto(path);

    await expectPageRenders(page, response, path);
    await expect(page.locator(`input[value="${FIXTURE.game.name}"]`).first()).toBeVisible();
  });

  // Create renders the same Blade view as edit with no model behind it, so it
  // exercises a different branch of every `isset($game)` in the template and
  // can break entirely on its own. That is true of every create form below.
  const createForms = [
    { name: 'a game', path: '/admin/games/games/create' },
    { name: 'a release', path: `/admin/games/${FIXTURE.game.id}/releases/create` },
    { name: 'a fact', path: `/admin/games/${FIXTURE.game.id}/facts/create` },
  ];

  for (const form of createForms) {
    test(`opens the create form for ${form.name}`, async ({ page }) => {
      await expectPageRenders(page, await page.goto(form.path), form.path);
    });
  }

  test('opens the edit form for a fact', async ({ page }) => {
    // No /edit suffix on this one - the route is a bare {fact}, declared after
    // facts/create so that 'create' still resolves. See routes/admin.php.
    const path = `/admin/games/${FIXTURE.game.id}/facts/${FIXTURE.game.factId}`;

    await expectPageRenders(page, await page.goto(path), path);
  });

  // The per-game panels all hang off the same edit screen. They are separate
  // controllers reading different corners of the schema, so a broken one
  // takes out only its own tab - which is why they are listed rather than
  // represented by one of them.
  const gamePanels = [
    { name: 'releases', path: `/admin/games/${FIXTURE.game.id}/releases` },
    { name: 'credits', path: `/admin/games/${FIXTURE.game.id}/credits` },
    { name: 'facts', path: `/admin/games/${FIXTURE.game.id}/facts` },
    { name: 'screenshots', path: `/admin/games/${FIXTURE.game.id}/screenshots` },
    { name: 'videos', path: `/admin/games/${FIXTURE.game.id}/videos` },
    { name: 'similar games', path: `/admin/games/${FIXTURE.game.id}/similar` },
    { name: 'music', path: `/admin/games/${FIXTURE.game.id}/music` },
  ];

  for (const panel of gamePanels) {
    test(`opens the ${panel.name} panel`, async ({ page }) => {
      await expectPageRenders(page, await page.goto(panel.path), panel.path);
    });
  }

  // Release sub-panels use a bare {release} segment rather than
  // releases/{release} - see routes/admin.php.
  const releasePanels = [
    { name: 'release', path: `/admin/games/${FIXTURE.game.id}/releases/${FIXTURE.release.id}` },
    { name: 'release scene info', path: `/admin/games/${FIXTURE.game.id}/${FIXTURE.release.id}/scene` },
    { name: 'release system info', path: `/admin/games/${FIXTURE.game.id}/${FIXTURE.release.id}/system` },
    { name: 'release medias', path: `/admin/games/${FIXTURE.game.id}/${FIXTURE.release.id}/medias` },
    { name: 'release scans', path: `/admin/games/${FIXTURE.game.id}/${FIXTURE.release.id}/scans` },
  ];

  for (const panel of releasePanels) {
    test(`opens the ${panel.name} panel`, async ({ page }) => {
      await expectPageRenders(page, await page.goto(panel.path), panel.path);
    });
  }

  test('lists the data-quality issues', async ({ page }) => {
    await expectPageRenders(page, await page.goto('/admin/games/issues'), '/admin/games/issues');
  });

  test('opens the music association screen', async ({ page }) => {
    await expectPageRenders(page, await page.goto('/admin/games/music'), '/admin/games/music');
  });

  // TODO: creating a game, adding a release, uploading a screenshot, and the
  // changelog rows every one of those is supposed to write.
});

test.describe('Admin game reference data', () => {
  const sections = [
    // Submissions are reviewed, never created, which is why it alone has no
    // create form - routes/admin.php prunes the action.
    { name: 'submissions', index: '/admin/games/submissions', detail: `/admin/games/submissions/${FIXTURE.submission.id}` },
    { name: 'individuals', index: '/admin/games/individuals', detail: `/admin/games/individuals/${FIXTURE.individual.id}/edit`, create: '/admin/games/individuals/create' },
    { name: 'companies', index: '/admin/games/companies', detail: `/admin/games/companies/${FIXTURE.company.id}/edit`, create: '/admin/games/companies/create' },
    { name: 'series', index: '/admin/games/series', detail: `/admin/games/series/${FIXTURE.series.id}/edit`, create: '/admin/games/series/create' },
  ];

  for (const section of sections) {
    test(`lists ${section.name}`, async ({ page }) => {
      await expectPageRenders(page, await page.goto(section.index), section.index);
    });

    test(`opens one of the ${section.name}`, async ({ page }) => {
      await expectPageRenders(page, await page.goto(section.detail), section.detail);
    });

    if (section.create) {
      test(`opens the create form for ${section.name}`, async ({ page }) => {
        await expectPageRenders(page, await page.goto(section.create), section.create);
      });
    }
  }

  test('redirects the bare configuration route to the first section', async ({ page }) => {
    const response = await page.goto('/admin/games/config');

    await expectPageRenders(page, response, '/admin/games/config/engine');
  });

  // One route, twenty tables, and two different form layouts
  // (CONFIG_HAS_DESCRIPTION). A renamed table breaks exactly one of them.
  const configTypes = [
    'engine', 'language', 'genre', 'port', 'progress', 'control',
    'individual-role', 'developer-role', 'sound', 'resolution', 'system',
    'emulator', 'tos', 'memory', 'enhancement', 'copy-protection',
    'disk-protection', 'trainer', 'media-type', 'media-scan-type',
  ];

  for (const type of configTypes) {
    test(`opens the ${type} configuration table`, async ({ page }) => {
      const path = `/admin/games/config/${type}`;

      await expectPageRenders(page, await page.goto(path), path);
    });
  }
});

// The two /admin/ajax endpoints this section's forms call: games behind the
// similar-games, series and review pickers, sndh behind the music one. Unlike
// their public counterparts these are behind the admin middleware - that they
// turn a guest away is asserted from the public project, which is the only one
// without a session.
test.describe('Admin games autocompletes', () => {
  test('serves the games autocomplete', async ({ page }) => {
    const response = await page.request.get('/admin/ajax/games.json');

    expect(response.status()).toBe(200);
    expect(response.headers()['content-type'] ?? '').toContain('application/json');
    expect(Array.isArray(await response.json())).toBe(true);
  });

  test('suggests a game, its AKAs and their developers', async ({ page }) => {
    // This endpoint differs from the public one in what it labels a game with
    // rather than in what it finds: an admin picking one needs the developer
    // to tell two identically named games apart. AKAs are merged in as well,
    // so a game can be found under any of its titles, and the merged list is
    // ranked shortest match first.
    const rows = await (await page.request.get('/admin/ajax/games.json?q=Xenon')).json();

    // A list, not an object keyed by position: sortBy() keeps its keys, and
    // autoComplete.js walks the response by .length - so an object here is a
    // dropdown that silently stays empty. Only a query the ranking reorders
    // can catch it, which is what the fixture's AKA is for.
    expect(Array.isArray(rows)).toBe(true);

    const names = rows.map((row) => row.game_name);
    const aka = `${FIXTURE.game.akaName} [${FIXTURE.company.name}]`;
    const game = `${FIXTURE.game.name} [${FIXTURE.company.name}]`;

    expect(names).toContain(aka);
    expect(names).toContain(game);
    // Relative rather than absolute: the shorter title ranks first, and that
    // stays true however many rows a write spec has in flight.
    expect(names.indexOf(aka)).toBeLessThan(names.indexOf(game));
  });

  test('serves the sndh autocomplete', async ({ page }) => {
    const response = await page.request.get('/admin/ajax/sndh.json');

    expect(response.status()).toBe(200);
    expect(Array.isArray(await response.json())).toBe(true);
  });

  test('finds a tune by its title', async ({ page }) => {
    // The field shows `display`, which the query builds by concatenating the
    // title with the id - the id being the tune's path inside the archive.
    const response = await page.request.get(
      `/admin/ajax/sndh.json?q=${encodeURIComponent(FIXTURE.sndh.title)}`
    );

    const rows = await response.json();
    expect(rows.map((row) => row.id)).toContain(FIXTURE.sndh.id);
    expect(rows.find((row) => row.id === FIXTURE.sndh.id).display)
      .toContain(FIXTURE.sndh.title);
  });
});
