import { test, expect } from '../support/test.js';
import { FIXTURE } from '../support/fixture.js';
import { expectPageRenders } from '../support/assertions.js';

test.describe('Admin others', () => {
  // Trivia and quotes are edited inline in their table, so they have no
  // create or edit route - hence only an index each.
  const indexes = [
    { name: 'trivia', path: '/admin/others/trivias' },
    { name: 'quotes', path: '/admin/others/quotes' },
    { name: 'spotlights', path: '/admin/others/spotlights' },
    { name: 'the statistics', path: '/admin/others/statistics' },
    { name: 'the changelog', path: '/admin/others/changelog' },
  ];

  for (const index of indexes) {
    test(`lists ${index.name}`, async ({ page }) => {
      await expectPageRenders(page, await page.goto(index.path), index.path);
    });
  }

  test('opens the edit form for a spotlight', async ({ page }) => {
    const path = `/admin/others/spotlights/${FIXTURE.spotlight.id}/edit`;

    await expectPageRenders(page, await page.goto(path), path);
  });

  test('opens the create form for a spotlight', async ({ page }) => {
    // The only create form in this section - trivia and quotes are added
    // inline in their table.
    const path = '/admin/others/spotlights/create';

    await expectPageRenders(page, await page.goto(path), path);
  });

  test('draws every chart on the statistics page', async ({ page }) => {
    await page.goto('/admin/others/statistics');

    // Chart.js adds chartjs-render-monitor to each canvas it attaches to, so
    // comparing the two counts asks whether every chart the page asked for was
    // actually drawn. Before charts.js owned the drawing, a page whose bundle
    // never arrived looked exactly like one with no data to plot.
    const canvases = page.locator('canvas[data-chart-config]');
    const drawn = page.locator('canvas.chartjs-render-monitor');

    const expected = await canvases.count();
    expect(expected, 'the statistics page renders charts').toBeGreaterThan(0);
    await expect(drawn).toHaveCount(expected);
  });

  // TODO: the statistics page runs the heaviest queries in the admin and is
  // the most likely to regress on a schema change - it deserves assertions on
  // the numbers, not just that it renders.
});

// The three /admin/ajax endpoints used to be asserted here, as a sweep. They
// now live with the section whose forms call them: games and sndh in
// admin/games.spec.js, users in admin/users.spec.js.
