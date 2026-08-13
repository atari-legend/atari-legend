import { test } from '../support/test.js';
import { expectResourceLoads } from '../support/assertions.js';

// Everything a crawler reads. None of these are HTML, so they are fetched
// rather than navigated to - see expectResourceLoads().

test.describe('Crawler endpoints', () => {
  const endpoints = [
    { name: 'the sitemap index', path: '/sitemap', contentType: 'xml' },
    { name: 'the general sitemap', path: '/sitemap/general', contentType: 'xml' },
    { name: 'a per-letter game sitemap', path: '/sitemap/games/x', contentType: 'xml' },
    { name: 'robots.txt', path: '/robots.txt', contentType: 'text/plain' },
    { name: 'the main feed', path: '/feed', contentType: 'xml' },
    { name: 'the changelog feed', path: '/feed/changelog', contentType: 'xml' },
    // Not a crawler endpoint, but the same shape of test and the same reason
    // for existing: it is what the deployment asks whether the app is up.
    { name: 'the health check', path: '/up', contentType: 'text/html' },
  ];

  for (const endpoint of endpoints) {
    test(`serves ${endpoint.name}`, async ({ page }) => {
      const response = await page.request.get(endpoint.path);

      await expectResourceLoads(response, endpoint.path, { contentType: endpoint.contentType });
    });
  }

  // TODO: assert the sitemaps actually list the seeded entities, and that
  // unpublished content stays out of them.
});
