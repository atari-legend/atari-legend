import { test, expect } from '@playwright/test';
import { expectPageRenders } from '../support/assertions.js';

// Detail pages are reached the way a visitor reaches them: by following links
// from a starting page. Nothing here names an id or a slug, so these specs work
// against the thin CI fixture and against a full database alike, and they stay
// read-only and safe to run in parallel.
//
// Each entity gives a page to start from and the shape of the links to follow
// from there. Shapes are matched against the anchor's pathname, so query
// strings and the absolute URLs that route() emits do not matter.
const entities = [
  {
    name: 'game',
    start: '/',
    hops: [{ path: /^\/games\/[^/]+$/, ignore: ['/games/search'] }],
  },
  {
    name: 'review',
    start: '/',
    hops: [{ path: /^\/reviews\/\d+$/ }],
  },
  {
    name: 'interview',
    start: '/',
    hops: [{ path: /^\/interviews\/\d+$/ }],
  },
  {
    name: 'article',
    start: '/articles',
    hops: [{ path: /^\/articles\/\d+$/ }],
  },
  {
    name: 'magazine',
    start: '/magazines',
    hops: [{ path: /^\/magazines\/\d+$/ }],
  },
  {
    name: 'menu set',
    start: '/menusets',
    hops: [{ path: /^\/menusets\/\d+$/ }],
  },
  {
    // Releases are only listed on their game's page, so this one takes two
    // hops. It assumes the first game linked from the home page has a release,
    // which the fixture guarantees.
    name: 'game release',
    start: '/',
    hops: [
      { path: /^\/games\/[^/]+$/, ignore: ['/games/search'] },
      { path: /^\/games\/release\/\d+$/ },
    ],
  },
];

/**
 * Click the first link on the page whose path matches `hop`, and return where
 * it landed.
 */
async function followLink(page, hop, from) {
  const links = await page.locator('a[href]').evaluateAll(anchors =>
    anchors.map(anchor => ({ path: anchor.pathname, href: anchor.getAttribute('href') }))
  );
  const target = links.find(
    link => hop.path.test(link.path) && !(hop.ignore ?? []).includes(link.path)
  );

  // Not a soft skip: each starting page is supposed to surface these, so
  // nothing to click means either the page or the fixture is broken.
  expect(target, `${from} should link to ${hop.path}`).toBeTruthy();

  const [response] = await Promise.all([
    page.waitForNavigation(),
    page.locator(`a[href="${target.href}"]`).first().click(),
  ]);

  return { response, path: target.path };
}

test.describe('Entity pages reached by following links', () => {
  for (const entity of entities) {
    test(`opens a ${entity.name} from ${entity.start}`, async ({ page }) => {
      const uncaughtErrors = [];
      page.on('pageerror', exception => {
        uncaughtErrors.push(exception.message);
      });

      await page.goto(entity.start);

      let from = entity.start;
      for (const hop of entity.hops) {
        const landed = await followLink(page, hop, from);
        await expectPageRenders(page, landed.response, landed.path);
        from = landed.path;
      }

      expect(uncaughtErrors).toEqual([]);
    });
  }
});
