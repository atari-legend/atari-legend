import { test, expect } from '../support/public-write.js';
import { FIXTURE } from '../support/fixture.js';
import { signIn } from '../support/auth.js';
import { uniqueName, deleteRow, findRow, DELETE_FORM } from '../support/write.js';

// Submitting a news item.
//
// No parent to build: the form is on /news itself and a submission belongs to
// nothing but its author, so this is the one public write that needs the admin
// context for teardown alone.

test.describe('News submissions', () => {
  test('submits a news item to the moderation queue', async ({ page, adminPage }) => {
    const headline = uniqueName('News');

    await signIn(page, FIXTURE.contributor);
    await page.goto('/news');

    const form = page.locator('form[action$="/news/submit"]');
    await form.locator('input[name="title"]').fill(headline);
    await form.locator('textarea[name="text"]').fill('Submitted by the e2e suite.');
    await form.getByRole('button', { name: 'Submit' }).click();

    await expect(page.getByRole('alert')).toContainText('Thanks for your submission');
    // A submission is queued rather than published, so it must *not* have
    // joined the list it was posted from.
    await expect(page.getByRole('heading', { name: headline })).toHaveCount(0);

    // The queue is admin-only, and so is deleting from it.
    await adminPage.goto('/admin/news/submissions');
    const row = await findRow(adminPage, headline);
    await expect(row).toContainText(FIXTURE.contributor.userid);

    // A submission row offers two actions - approve and delete - so the delete
    // form has to be named. Approving would publish it onto the home page and
    // the news list, where the read specs are looking.
    await deleteRow(adminPage, headline, { form: DELETE_FORM });
  });
});

// TODO: approving a submission, which turns it into a news item - and so needs
// somewhere to put the news item that is not the real front page.
