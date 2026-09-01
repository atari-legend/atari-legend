import { test, expect } from '../support/public-write.js';
import { FIXTURE } from '../support/fixture.js';
import { expectPageRenders } from '../support/assertions.js';
import { signIn } from '../support/auth.js';
import { postComment, deleteComment } from '../support/comments.js';
import {
  uniqueName, deleteRow, expectNoRow, findRow, createGame, deleteGame,
  addGameScreenshot, deleteGameScreenshot, createReview, deleteReview,
} from '../support/write.js';

// Writing a review, and commenting on one.
//
// The submission form is the most JavaScript-heavy page on the public site and
// the only one with two separate scripts on it: bbcode.js wraps the selection
// in the textarea when a toolbar button is clicked, and review/submit.js
// rebuilds the whole Preview tab from the fields on Bootstrap's show.bs.tab.
// tests/Feature/Public/ReviewPagesTest posts the finished form and asserts what
// was stored - it cannot see either script, and neither can the visitor if one
// of them throws, because the tab just stays empty.

test.describe('Review submissions', () => {
  test('writes a review with the toolbar, previews it and submits it', async ({ page, adminPage }) => {
    const game = await createGame(adminPage);
    // A screenshot so the form renders its per-screenshot comment field. Those
    // are the only .previewable elements whose preview counterpart is built
    // from an id rather than fixed in the markup, and review/submit.js does an
    // unguarded querySelector on it - so this is the half of that script a
    // bare game never reaches.
    const screenshot = await addGameScreenshot(adminPage, game);
    const body = uniqueName('Review');
    const caption = uniqueName('Caption');

    await signIn(page, FIXTURE.contributor);

    const response = await page.goto(`/reviews/submit?game=${game.id}`);
    await expectPageRenders(page, response, '/reviews/submit');

    // Scoped to the form rather than the page: /reviews/submit also renders the
    // author's other reviews and the latest comments down either side, and both
    // of those name games.
    const form = page.locator('form[action*="/reviews/submit"]');
    await expect(page.getByRole('heading', { name: game.name })).toBeVisible();

    // 1. The toolbar. Select the text first, which is the branch worth taking:
    //    with a selection bbcode.js wraps it, with none it inserts an empty
    //    pair. The assertion is on the textarea's value, so a listener that
    //    never attached is a failure rather than a no-op.
    await page.fill('#text', body);
    await page.locator('#text').press('Control+a');
    await page.locator('button[data-bbcode-target="#text"][data-bbcode-tag="b"]').click();

    await expect(page.locator('#text')).toHaveValue(`[b]${body}[/b]`);

    await page.fill('#graphics', '8');
    await page.fill('#sound', '7');
    await page.fill('#gameplay', '9');
    await page.fill('#overall', '8');
    await page.fill(`#screenshot-comment-${screenshot.id}`, caption);

    // 2. The preview, which exists only in the browser - nothing is posted to
    //    render it.
    //
    //    Addressed by its href rather than by role, which is the one place in
    //    the suite that is worth doing: the markup is a plain <a href="#preview">
    //    and Bootstrap 5.2 rewrites it to role="tab" as it boots, so the role
    //    the element answers to depends on whether the script has run yet.
    //    Waiting for that attribute is the wait for Bootstrap - and review
    //    /submit.js hangs off Bootstrap's own show.bs.tab, so a click landing
    //    before it would only jump the anchor.
    const preview = form.locator('a[href="#preview"]');
    await expect(preview).toHaveAttribute('role', 'tab');
    await preview.click();

    // The BBCode was parsed rather than echoed: the tag became an element.
    await expect(page.locator('#preview-text b')).toHaveText(body);
    await expect(page.locator('#preview-graphics')).toHaveText('8');
    await expect(page.locator('#preview-overall')).toHaveText('8');
    await expect(page.locator(`#preview-screenshot-comment-${screenshot.id}`))
      .toHaveText(caption);

    // 3. Back to the edit tab, which is where the Submit button lives -
    //    Bootstrap gives an inactive pane `display: none`, so the button is out
    //    of the accessibility tree entirely while the preview is up. Going
    //    back is what a visitor does anyway.
    await form.locator('a[href="#edit"]').click();

    // 4. Submit. A submitted review is unpublished, so the visitor is sent
    //    back to the game rather than to a review page that would 404.
    await form.getByRole('button', { name: 'Submit' }).click();

    await expect(page).toHaveURL(new RegExp(`/games/${game.slug}$`));
    await expect(page.getByRole('alert')).toContainText('Thanks for your submission');

    // It is waiting in the moderation queue, which is also the only screen it
    // can be deleted from.
    await adminPage.goto('/admin/reviews/submissions');
    await deleteRow(adminPage, game.name);

    // Then look at the queue again. deleteRow checks the table it lands on, and
    // ReviewsController::destroy() redirects to the *published* index - where an
    // unpublished submission was never listed, so its check passes either way.
    await adminPage.goto('/admin/reviews/submissions');
    await expectNoRow(adminPage, game.name);

    await deleteGameScreenshot(adminPage, game, screenshot);
    await deleteGame(adminPage, game);
  });

  test('publishes a submitted review, which puts it on the public list', async ({ page, adminPage }) => {
    const game = await createGame(adminPage);
    const body = uniqueName('Review');

    await signIn(page, FIXTURE.contributor);
    await page.goto(`/reviews/submit?game=${game.id}`);

    // The form itself is driven field by field in the test above; this one is
    // about what happens to the review afterwards, so it fills the minimum the
    // controller stores and gets out.
    const form = page.locator('form[action*="/reviews/submit"]');
    await page.fill('#text', body);
    await page.fill('#graphics', '8');
    await page.fill('#sound', '7');
    await page.fill('#gameplay', '9');
    await page.fill('#overall', '6');
    await form.getByRole('button', { name: 'Submit' }).click();

    await expect(page.getByRole('alert')).toContainText('Thanks for your submission');

    // Not published, so not on /reviews. Filtered by its author rather than
    // read off page one: the list paginates by five and anything else running
    // at the same time would push it off - and the filter is a real GET form,
    // so this drives it rather than hand-writing the query string.
    await page.goto('/reviews');
    await page.locator('#author').selectOption(String(FIXTURE.contributor.id));
    await page.getByRole('button', { name: 'Search' }).click();
    await expect(page.getByRole('link', { name: game.name })).toHaveCount(0);

    // The URL that search produced, to come back to once it is published -
    // read off the browser rather than written out here, so this is still the
    // form's own query string and not a guess at it.
    const listedByAuthor = page.url();

    // Approving a review is not a button of its own: an editor opens the
    // submission and turns the Submission switch off, which is what moves it
    // from reviews.edit = REVIEW_UNPUBLISHED to REVIEW_PUBLISHED.
    await adminPage.goto('/admin/reviews/submissions');
    const row = await findRow(adminPage, game.name);
    await row.getByRole('link', { name: game.name }).click();

    await expect(adminPage).toHaveURL(/\/admin\/reviews\/reviews\/\d+\/edit$/);
    const review = { id: adminPage.url().split('/').at(-2) };

    // The scores the visitor typed are what the editor is looking at.
    await expect(adminPage.locator('#graphics')).toHaveValue('8');
    await expect(adminPage.locator('#overall')).toHaveValue('6');

    await adminPage.locator('#submission').uncheck();
    // update() sends a published review to the published index, which is the
    // first sign the switch was read.
    await adminPage.getByRole('button', { name: 'Save & Close' }).click();
    await expect(adminPage).toHaveURL(/\/admin\/reviews\/reviews$/);

    // The half nothing covered. On the list first - the same search as before,
    // which found nothing then.
    await page.goto(listedByAuthor);
    await expect(page.getByRole('link', { name: game.name }).first()).toBeVisible();

    // Then on its own page, where the scores finally render - which is the one
    // thing about a review that only exists once it has been published.
    const response = await page.goto(`/reviews/${review.id}`);
    await expectPageRenders(page, response, `/reviews/${review.id}`);

    // The heading is the card's own rather than the page's: the page also
    // renders a visually-hidden <h1> carrying the game name, so an unqualified
    // heading locator matches twice.
    const heading = page.getByRole('heading', { name: game.name, level: 2 });
    await expect(heading).toBeVisible();

    // Everything else is read inside that card. Publishing this review also
    // put it at the top of the "In-Depth Reviews" card down the side, which
    // prints the same text again - so `getByText(body)` on the page has two
    // matches whenever this is the newest published review, which is most of
    // the time and not all of it.
    const card = page.locator('.card').filter({ has: heading });
    await expect(card.getByText(`Written by ${FIXTURE.contributor.userid}`)).toBeVisible();
    await expect(card.getByText(body)).toBeVisible();
    await expect(card.getByText('Graphics: 8')).toBeVisible();
    await expect(card.getByText('Sound: 7')).toBeVisible();
    await expect(card.getByText('Gameplay: 9')).toBeVisible();
    await expect(card.getByText('Overall: 6')).toBeVisible();

    // A published review is deleted from the published index, not from the
    // queue it arrived in.
    await adminPage.goto('/admin/reviews/reviews');
    await deleteRow(adminPage, game.name);

    // Only now: a game with a review on it is not deletable.
    await deleteGame(adminPage, game);
  });
});

test.describe('Review comments', () => {
  test('comments on a review and deletes the comment', async ({ page, adminPage }) => {
    const review = await createReview(adminPage);
    const body = uniqueName('Comment');

    await signIn(page, FIXTURE.contributor);
    await page.goto(`/reviews/${review.id}`);

    const comment = await postComment(page, `form[action$="/reviews/${review.id}/comment"]`, body);
    await expect(comment.text).toBeVisible();

    await deleteComment(page, comment);

    await deleteReview(adminPage, review);
  });
});

// TODO: a review with screenshot comments as published - the submit form takes
// one per screenshot, and card_review renders them beside the text.
