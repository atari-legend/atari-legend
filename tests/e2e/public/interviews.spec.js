import { test, expect } from '../support/test.js';
import { FIXTURE } from '../support/fixture.js';
import { expectPageRenders } from '../support/assertions.js';

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

  // TODO: the chapter hotspot links ([hotspotUrl] / [hotspot] BBCode),
  // commenting, and the interview screenshots.
});
