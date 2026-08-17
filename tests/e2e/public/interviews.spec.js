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

  // A hotspot is two halves of one feature, and Helper::bbCode renders each
  // separately: [hotspotUrl=#1]Title[/hotspotUrl] in the chapters column
  // becomes the link, [hotspot=1]Question[/hotspot] in the text becomes what
  // it jumps to. Asserting the link alone would pass with the target tag
  // silently dropped, so this follows it and looks for the anchor.
  test('links a chapter to its hotspot in the text', async ({ page }) => {
    await page.goto(`/interviews/${FIXTURE.interview.id}`);

    const chapter = page.getByRole('link', { name: FIXTURE.interview.chapter });
    await expect(chapter).toHaveAttribute('href', '#1');

    await chapter.click();
    await expect(page).toHaveURL(new RegExp(`/interviews/${FIXTURE.interview.id}#1$`));

    // The element the link points at, carrying the question it marks.
    // [id="1"] rather than #1: the ids Helper::bbCode emits for hotspots are
    // numbers, which are valid HTML but not valid CSS id selectors.
    await expect(page.locator('[id="1"]')).toContainText(FIXTURE.interview.chapter);
  });

  test('shows a screenshot with its caption', async ({ page }) => {
    await page.goto(`/interviews/${FIXTURE.interview.id}`);

    const screenshot = page.getByRole('img', { name: FIXTURE.interview.screenshotCaption });
    await expect(screenshot).toBeVisible();

    // A storage URL rather than a route, unlike the avatar above - so the
    // image is fetched rather than left to the guard in support/test.js, which
    // exempts /storage/ on purpose.
    const path = new URL(await screenshot.getAttribute('src')).pathname;
    expect(path).toContain(`/interview_screenshots/${FIXTURE.interview.screenshotId}.`);
    await expectResourceLoads(await page.request.get(path), path, { magic: 'PNG' });

    await expect(page.getByText(FIXTURE.interview.screenshotCaption).last()).toBeVisible();
  });

  // Commenting on an interview is public-write/content.spec.js.
});
