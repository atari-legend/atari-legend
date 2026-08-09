import { test, expect } from '@playwright/test';
import { expectPageRenders } from './assertions.js';

// Detail pages are reached the way a visitor reaches them: by following a link
// from the home page. Nothing here names an id or a slug, so these specs work
// against the thin CI fixture and against a full database alike, and they stay
// read-only and safe to run in parallel.
//
// Each entry says what a link to that entity's page looks like. The paths are
// matched against the anchor's pathname, so query strings and the absolute
// URLs that route() emits do not matter.
const entities = [
  { name: 'game', path: /^\/games\/[^/]+$/, ignore: ['/games/search'] },
  { name: 'review', path: /^\/reviews\/\d+$/ },
  { name: 'interview', path: /^\/interviews\/\d+$/ },
];

test.describe('Entity pages reached from the home page', () => {
  for (const entity of entities) {
    test(`opens a ${entity.name} from the home page`, async ({ page }) => {
      const uncaughtErrors = [];
      page.on('pageerror', exception => {
        uncaughtErrors.push(exception.message);
      });

      await page.goto('/');

      const links = await page.locator('a[href]').evaluateAll(anchors =>
        anchors.map(anchor => ({ path: anchor.pathname, href: anchor.getAttribute('href') }))
      );
      const target = links.find(
        link => entity.path.test(link.path) && !(entity.ignore ?? []).includes(link.path)
      );

      // Not a soft skip: the home page is supposed to surface each of these,
      // so nothing to click means either the page or the fixture is broken.
      expect(target, `the home page should link to a ${entity.name}`).toBeTruthy();

      const [response] = await Promise.all([
        page.waitForNavigation(),
        page.locator(`a[href="${target.href}"]`).first().click(),
      ]);

      await expectPageRenders(page, response, target.path);
      expect(uncaughtErrors).toEqual([]);
    });
  }
});
