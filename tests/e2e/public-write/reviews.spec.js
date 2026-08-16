import { test, expect } from '../support/public-write.js';
import { FIXTURE } from '../support/fixture.js';
import { expectPageRenders } from '../support/assertions.js';
import { signIn } from '../support/auth.js';
import { postComment, deleteComment } from '../support/comments.js';
import {
  uniqueName, deleteRow, expectNoRow, createGame, deleteGame,
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

// TODO: the scores as they end up on the published review, which needs an
// editor to approve the submission first.
