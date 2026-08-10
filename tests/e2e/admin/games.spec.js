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
    { name: 'submissions', index: '/admin/games/submissions', detail: `/admin/games/submissions/${FIXTURE.submission.id}` },
    { name: 'individuals', index: '/admin/games/individuals', detail: `/admin/games/individuals/${FIXTURE.individual.id}/edit` },
    { name: 'companies', index: '/admin/games/companies', detail: `/admin/games/companies/${FIXTURE.company.id}/edit` },
    { name: 'series', index: '/admin/games/series', detail: `/admin/games/series/${FIXTURE.series.id}/edit` },
  ];

  for (const section of sections) {
    test(`lists ${section.name}`, async ({ page }) => {
      await expectPageRenders(page, await page.goto(section.index), section.index);
    });

    test(`opens one of the ${section.name}`, async ({ page }) => {
      await expectPageRenders(page, await page.goto(section.detail), section.detail);
    });
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
