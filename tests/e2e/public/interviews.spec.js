import { test, expect } from '../support/test.js';
import { FIXTURE } from '../support/fixture.js';
import { expectPageRenders, expectResourceLoads } from '../support/assertions.js';

test.describe('Interviews', () => {
  test('lists interviews', async ({ page }) => {
    const response = await page.goto('/interviews');

    await expectPageRenders(page, response, '/interviews');
    await expect(page.getByRole('heading', { name: 'Interviews', level: 1 })).toBeVisible();
    // An interview is titled after the person it is with.
    await expect(page.getByRole('heading', { name: FIXTURE.individual.name })).toBeVisible();
  });

  test('displays one interview', async ({ page }) => {
    const response = await page.goto(`/interviews/${FIXTURE.interview.id}`);

    await expectPageRenders(page, response, `/interviews/${FIXTURE.interview.id}`);
    await expect(page.getByRole('heading', { name: FIXTURE.individual.name, level: 1 })).toBeVisible();
  });

  test("serves the individual's avatar", async ({ page }) => {
    // Lives here rather than in a spec of its own: an interview is the only
    // page that renders it, individuals having no public page.
    const path = `/individuals/${FIXTURE.individual.id}/avatar.webp`;

    await expectResourceLoads(await page.request.get(path), path, {
      contentType: 'image/webp',
      magic: 'WEBP',
    });
  });

  // TODO: the chapter hotspot links ([hotspotUrl] / [hotspot] BBCode),
  // commenting, and the interview screenshots.
});
