import { test, expect } from '@playwright/test';
import { expectPageRenders } from './assertions.js';

const adminRoutes = [
  { name: 'Admin Dashboard', path: '/admin' },
  { name: 'Admin Games Index', path: '/admin/games/games' },
  { name: 'Admin Games Issues', path: '/admin/games/issues' },
  { name: 'Admin Games Music', path: '/admin/games/music' },
  { name: 'Admin Games Submissions', path: '/admin/games/submissions' },
  { name: 'Admin Games Individuals', path: '/admin/games/individuals' },
  { name: 'Admin Games Companies', path: '/admin/games/companies' },
  { name: 'Admin Games Series', path: '/admin/games/series' },
  // routes/admin.php sends the bare config route to the first section
  { name: 'Admin Games Configuration', path: '/admin/games/config', redirectsTo: '/admin/games/config/engine' },
  { name: 'Admin Users Index', path: '/admin/users/users' },
  { name: 'Admin Users Comments', path: '/admin/users/comments' },
  { name: 'Admin News Index', path: '/admin/news/news' },
  { name: 'Admin News Submissions', path: '/admin/news/submissions' },
  { name: 'Admin Links Index', path: '/admin/links/links' },
  { name: 'Admin Links Categories', path: '/admin/links/categories' },
  { name: 'Admin Reviews Index', path: '/admin/reviews/reviews' },
  { name: 'Admin Reviews Submissions', path: '/admin/reviews/submissions' },
  { name: 'Admin Interviews Index', path: '/admin/interviews/interviews' },
  { name: 'Admin Articles Index', path: '/admin/articles/articles' },
  { name: 'Admin Article Types', path: '/admin/articles/types' },
  { name: 'Admin Statistics', path: '/admin/others/statistics' },
  { name: 'Admin Changelog', path: '/admin/others/changelog' },
  { name: 'Admin Trivias', path: '/admin/others/trivias' },
  { name: 'Admin Quotes', path: '/admin/others/quotes' },
  { name: 'Admin Spotlights', path: '/admin/others/spotlights' },
  { name: 'Admin Menu Sets', path: '/admin/menus/sets' },
  { name: 'Admin Menu Conditions', path: '/admin/menus/conditions' },
  { name: 'Admin Menu Content Types', path: '/admin/menus/content-types' },
  { name: 'Admin Menu Software', path: '/admin/menus/software' },
  { name: 'Admin Menu Crews', path: '/admin/menus/crews' },
  { name: 'Admin Magazines Index', path: '/admin/magazines/magazines' },
  { name: 'Admin Magazine Index Types', path: '/admin/magazines/index-types' },
];

test.describe('Admin Pages Sanity Checks', () => {
  for (const route of adminRoutes) {
    test(`renders ${route.name} (${route.path}) as authenticated admin`, async ({ page }) => {
      const uncaughtErrors = [];
      page.on('pageerror', exception => {
        uncaughtErrors.push(exception.message);
      });

      const response = await page.goto(route.path);

      // Routes that redirect on purpose declare where they land, so that an
      // unintended redirect - being bounced to '/' when the session is not an
      // admin - still fails.
      await expectPageRenders(page, response, route.redirectsTo ?? route.path);
      expect(uncaughtErrors).toEqual([]);
    });
  }
});
