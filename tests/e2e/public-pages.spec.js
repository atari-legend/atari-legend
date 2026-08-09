import { test, expect } from '@playwright/test';
import { expectPageRenders } from './assertions.js';

// Runs unauthenticated: the 'public' project supplies an empty storage state
// and, unlike 'admin', does not depend on the login setup.

const publicRoutes = [
  { name: 'Home', path: '/' },
  { name: 'News Index', path: '/news' },
  { name: 'Games Index', path: '/games' },
  { name: 'Games Search', path: '/games/search' },
  { name: 'Menu Sets Index', path: '/menusets' },
  { name: 'Reviews Index', path: '/reviews' },
  { name: 'Interviews Index', path: '/interviews' },
  { name: 'Articles Index', path: '/articles' },
  { name: 'Links Index', path: '/links' },
  { name: 'About Page', path: '/about' },
  { name: 'About Andreas Page', path: '/about/andreas' },
  { name: 'Magazines Index', path: '/magazines' },
  { name: 'Changelog Index', path: '/changelog' },
  { name: 'Sitemap Index', path: '/sitemap' },
  { name: 'Sitemap General', path: '/sitemap/general' },
  { name: 'Robots.txt', path: '/robots.txt' },
];

test.describe('Public Pages Sanity Checks', () => {
  for (const route of publicRoutes) {
    test(`renders ${route.name} (${route.path})`, async ({ page }) => {
      const uncaughtErrors = [];
      page.on('pageerror', exception => {
        uncaughtErrors.push(exception.message);
      });

      const response = await page.goto(route.path);

      await expectPageRenders(page, response, route.path);
      expect(uncaughtErrors).toEqual([]);
    });
  }
});
