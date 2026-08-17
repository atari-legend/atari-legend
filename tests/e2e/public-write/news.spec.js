import { test, expect } from '../support/public-write.js';
import { FIXTURE } from '../support/fixture.js';
import { signIn } from '../support/auth.js';
import { uniqueName, deleteRow, expectNoRow, findRow, DELETE_FORM } from '../support/write.js';

// Submitting a news item.
//
// No parent to build: the form is on /news itself and a submission belongs to
// nothing but its author, so this is the one public write that needs the admin
// context for teardown alone.

/**
 * Submit a news item through the form on /news, as whoever `page` is signed in
 * as.
 *
 * Local to this spec rather than in support/write.js: it is the only public
 * form in the suite that posts a news submission, and both tests here start
 * with it - one to delete what it queued, one to approve it.
 */
async function submitNews(page, headline) {
  await page.goto('/news');

  const form = page.locator('form[action$="/news/submit"]');
  await form.locator('input[name="title"]').fill(headline);
  await form.locator('textarea[name="text"]').fill(`Submitted by the e2e suite: ${headline}.`);
  await form.getByRole('button', { name: 'Submit' }).click();

  await expect(page.getByRole('alert')).toContainText('Thanks for your submission');
}

test.describe('News submissions', () => {
  test('submits a news item to the moderation queue', async ({ page, adminPage }) => {
    const headline = uniqueName('News');

    await signIn(page, FIXTURE.contributor);
    await submitNews(page, headline);

    // A submission is queued rather than published, so it must *not* have
    // joined the list it was posted from.
    await expect(page.getByRole('heading', { name: headline })).toHaveCount(0);

    // The queue is admin-only, and so is deleting from it.
    await adminPage.goto('/admin/news/submissions');
    const row = await findRow(adminPage, headline);
    await expect(row).toContainText(FIXTURE.contributor.userid);

    // A submission row offers two actions - approve and delete - so the delete
    // form has to be named. What approving does is the test below.
    await deleteRow(adminPage, headline, { form: DELETE_FORM });
  });

  test('approves a submission, which publishes it as a news item', async ({ page, adminPage }) => {
    const headline = uniqueName('News');

    await signIn(page, FIXTURE.contributor);
    await submitNews(page, headline);

    // Queued, so not on the list it was posted from - yet.
    await expect(page.getByRole('heading', { name: headline })).toHaveCount(0);

    // Approving publishes onto the real news list, which is why this was left
    // out for so long. It is safe because the item is named with uniqueName()
    // and deleted again below: public/news.spec.js asserts that the *seeded*
    // headline is there, never a count and never a row position, so one extra
    // item passing through is exactly the transient row it is written to
    // survive.
    await adminPage.goto('/admin/news/submissions');
    const row = await findRow(adminPage, headline);
    await row.getByRole('button', { name: `Approve submission '${headline}'` }).click();

    // approve() creates the news item and lands on it, which is the only place
    // its id appears.
    await expect(adminPage).toHaveURL(/\/admin\/news\/news\/\d+\/edit$/);
    await expect(adminPage.locator('#headline')).toHaveValue(headline);
    // The submission's author came with it rather than the moderator who
    // pressed the button.
    await expect(adminPage.locator('#author_name')).toHaveValue(FIXTURE.contributor.userid);

    // And the queue is empty again: approve() destroys the submission it read.
    await adminPage.goto('/admin/news/submissions');
    await expectNoRow(adminPage, headline);

    // The half nothing covered: the item is now published, and the visitor who
    // submitted it can read it on the page they submitted it from, credited to
    // them.
    await page.goto('/news');
    const published = page.locator('.card-title', { hasText: headline });
    await expect(published).toBeVisible();
    await expect(published.locator('xpath=following-sibling::p[1]'))
      .toContainText(FIXTURE.contributor.userid);
    await expect(page.getByText(`Submitted by the e2e suite: ${headline}.`)).toBeVisible();

    // Put the front page back: a news item is not a submission any more, so it
    // is deleted from the news table rather than from the queue.
    await adminPage.goto('/admin/news/news');
    await deleteRow(adminPage, headline);

    await page.goto('/news');
    await expect(page.getByRole('heading', { name: headline })).toHaveCount(0);
  });
});

// TODO: pagination of the queue, and a submission with an image - the admin's
// news form takes one, the public form does not.
