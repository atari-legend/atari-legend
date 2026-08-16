import { test, expect } from '../support/public-write.js';
import { FIXTURE } from '../support/fixture.js';
import { signIn } from '../support/auth.js';
import { uniqueName } from '../support/write.js';

// Submitting a link.
//
// The one spec in this project that cannot clean up after itself, and the only
// exception anywhere to the rule that a write spec deletes everything it
// creates.
//
// LinkController::postLink() writes a WebsiteValidate row, and the admin in
// this repo has no screen for website_validate at all - link submissions are
// still approved in the legacy CPANEL. There is no route to delete one through,
// so the row stays.
//
// What makes that tolerable rather than a slow leak: the row is named
// `E2E Link <timestamp><random>`, it renders nowhere on the public site,
// nothing in tests/e2e reads that table, and it is greppable. It is recorded in
// tests/e2e/README.md so that the next person does not read it as an oversight.
// Give the admin a submissions screen and this becomes an ordinary spec.

test.describe('Link submissions', () => {
  test('submits a link to the moderation queue', async ({ page }) => {
    const name = uniqueName('Link');

    await signIn(page, FIXTURE.contributor);
    await page.goto('/links');

    const form = page.locator('form[action$="/links/submit"]');
    await form.locator('input[name="name"]').fill(name);
    await form.locator('input[name="url"]').fill('https://example.org/');
    await form.locator('textarea[name="description"]').fill('Submitted by the e2e suite.');
    await form.getByRole('button', { name: 'Submit' }).click();

    await expect(page.getByRole('alert')).toContainText('Thanks for your submission');
    // Queued, not published: it must not have joined the list it was posted
    // from, which is what public/links.spec.js is reading.
    await expect(page.getByRole('link', { name })).toHaveCount(0);
  });
});
