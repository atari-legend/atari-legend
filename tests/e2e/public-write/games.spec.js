import { test, expect } from '../support/public-write.js';
import { FIXTURE } from '../support/fixture.js';
import { expectPageRenders, expectResourceLoads } from '../support/assertions.js';
import { signIn } from '../support/auth.js';
import { postComment, editComment, deleteComment, findComment } from '../support/comments.js';
import {
  PNG, uniqueName, findRow, expectNoRow, createGame, deleteGame,
} from '../support/write.js';

// What a signed-in visitor can do to a game: rate it, comment on it, correct
// it.
//
// tests/Feature/Public/GamePageTest and ReleasePageTest already post at all
// three routes and assert what the controller wrote. None of that goes near a
// browser, and two of these widgets have JavaScript between the visitor and the
// POST - a Bootstrap dropdown over the scores, and comments.js over an editor
// that is `d-none` in the markup. Break either and the PHPUnit suite stays
// green while nobody can rate a game or fix a typo.
//
// Each test works on a game it created through the admin, never the seeded one:
// /games/{slug} is what public/games.spec.js asserts on, and a score or a
// comment appearing there mid-run is exactly the transient row the read specs
// are written not to see.

/** The vote widget on a game page, scoped to its own form. */
function voteForm(page, game) {
  return page.locator(`form[action$="/${game.slug}/vote"]`);
}

test.describe('Game votes', () => {
  test('rates a game and withdraws the rating again', async ({ page, adminPage }) => {
    const game = await createGame(adminPage);
    await signIn(page, FIXTURE.contributor);

    const response = await page.goto(`/games/${game.slug}`);
    await expectPageRenders(page, response, `/games/${game.slug}`);

    await expect(voteForm(page, game).getByRole('button', { name: 'Your rating: ?' })).toBeVisible();
    await expect(page.getByText('No votes yet - be the first!')).toBeVisible();

    // The scores sit behind a Bootstrap dropdown, which is the whole reason
    // this is worth driving rather than posting: the buttons are in the markup
    // either way, and a click on one Bootstrap has not opened is not
    // actionable.
    await voteForm(page, game).getByRole('button', { name: 'Your rating: ?' }).click();
    await voteForm(page, game).locator('.dropdown-menu')
      .getByRole('button', { name: 'Good' }).click();

    await expect(page).toHaveURL(new RegExp(`/games/${game.slug}$`));
    // 'Good' is 3 on a scale of 0-4, and GameHelper::normalizeScore() restates
    // it out of 5. The number is what proves the vote was read back, rather
    // than only that something was stored.
    await expect(page.getByText('3.75')).toBeVisible();
    await expect(page.getByText('1 vote')).toBeVisible();
    // GameVoteHelper draws the distribution inline once there is anything to
    // draw: five bars, and only the one that was voted for has any height.
    await expect(page.locator('svg.votes rect')).toHaveCount(5);
    await expect(page.locator('svg.votes rect.score-3')).not.toHaveAttribute('height', '0');

    // The toggle now carries the vote, and the menu has grown a discard entry
    // it did not have a moment ago.
    await voteForm(page, game).getByRole('button', { name: 'Good' }).click();
    await voteForm(page, game).getByRole('button', { name: 'Discard my vote' }).click();

    await expect(page.getByText('No votes yet - be the first!')).toBeVisible();
    await expect(voteForm(page, game).getByRole('button', { name: 'Your rating: ?' })).toBeVisible();

    await deleteGame(adminPage, game);
  });
});

test.describe('Game comments', () => {
  test('comments on a game, edits the comment and deletes it', async ({ page, adminPage }) => {
    const game = await createGame(adminPage);
    const body = uniqueName('Comment');
    const edited = `${body} rewritten`;

    await signIn(page, FIXTURE.contributor);
    await page.goto(`/games/${game.slug}`);

    let comment = await postComment(page, `form[action$="/${game.slug}/comment"]`, body);
    await expect(comment.text).toBeVisible();

    comment = await editComment(page, comment, edited);
    await expect(comment.text).toBeVisible();
    await expect(page.getByText(body, { exact: true })).toHaveCount(0);

    await deleteComment(page, comment);

    // A game is deletable with comments still on it, so this is cleanup the
    // test has to do rather than cleanup the admin would refuse to skip.
    await deleteGame(adminPage, game);
  });
});

test.describe('Game info submissions', () => {
  test('submits a correction with a screenshot', async ({ page, adminPage }) => {
    const game = await createGame(adminPage);
    const info = uniqueName('Submission');

    await signIn(page, FIXTURE.contributor);
    await page.goto(`/games/${game.slug}`);

    const form = page.locator(`form[action$="/${game.slug}/submitInfo"]`);
    await form.locator('textarea[name="info"]').fill(info);
    // The one upload on the public side of the site, and a plain multipart
    // input rather than a Filepond pond. submitInfo() takes the extension from
    // the file's own magic bytes, so this has to be a real image.
    await form.locator('input[name="files[]"]').setInputFiles({
      name: 'correction.png',
      mimeType: 'image/png',
      buffer: PNG,
    });
    await form.getByRole('button', { name: 'Submit' }).click();

    await expect(page.getByRole('alert')).toContainText('Thanks for your submission');

    // A submission is only visible in the admin, which is also the only place
    // it can be deleted again - so checking it arrived and cleaning it up are
    // the same trip.
    await adminPage.goto('/admin/games/submissions');
    const row = await findRow(adminPage, game.name);
    await row.getByRole('link', { name: game.name }).click();

    await expect(adminPage).toHaveURL(/\/admin\/games\/submissions\/\d+$/);
    await expect(adminPage.getByText(info)).toBeVisible();

    // The upload landed and is served back as an image, rather than as a 404
    // or as a broken path that only looks right in the markup.
    const screenshot = adminPage.locator('form[action*="screenshots"] img');
    await expect(screenshot)
      .toHaveAttribute('src', /\/storage\/images\/game_submit_screenshots\/\d+\.png$/);
    const path = new URL(await screenshot.getAttribute('src')).pathname;
    await expectResourceLoads(await adminPage.request.get(path), path, { magic: 'PNG' });

    await adminPage.getByRole('button', { name: 'Delete', exact: true }).click();
    await expect(adminPage).toHaveURL(/\/admin\/games\/submissions$/);

    // Only now: Game::getIsDeletableAttribute() refuses a game that still has
    // an info submission, and the button is disabled rather than absent - so
    // this would time out on the actionability check rather than say what was
    // wrong.
    await deleteGame(adminPage, game);
  });

  test('approves a correction, which turns it into a comment on the game', async ({ page, adminPage }) => {
    const game = await createGame(adminPage);
    const info = uniqueName('Submission');

    await signIn(page, FIXTURE.contributor);
    await page.goto(`/games/${game.slug}`);

    // No attachment this time: the upload is the test above, and a screenshot
    // here would leave an orphan screenshots row behind for nothing - see
    // follow-up 11 in the README.
    const form = page.locator(`form[action$="/${game.slug}/submitInfo"]`);
    await form.locator('textarea[name="info"]').fill(info);
    await form.getByRole('button', { name: 'Submit' }).click();

    await expect(page.getByRole('alert')).toContainText('Thanks for your submission');

    await adminPage.goto('/admin/games/submissions');
    const row = await findRow(adminPage, game.name);
    await row.getByRole('link', { name: game.name }).click();

    await expect(adminPage).toHaveURL(/\/admin\/games\/submissions\/\d+$/);
    const submission = { id: adminPage.url().split('/').at(-1) };
    await expect(adminPage.locator('.card-subtitle')).toContainText('Reviewed No');

    // 1. Mark as reviewed - the flag a moderator sets on the way past. It is
    //    read back from the submission rather than from the queue: the table
    //    means to hide reviewed rows by default and does not, because its
    //    default is keyed 'processed' while the filter's own key is 'reviewed'
    //    - see the note at the bottom of this file.
    await adminPage.getByRole('button', { name: 'Mark as reviewed' }).click();
    await expect(adminPage).toHaveURL(/\/admin\/games\/submissions$/);

    await adminPage.goto(`/admin/games/submissions/${submission.id}`);
    await expect(adminPage.locator('.card-subtitle')).toContainText('Reviewed Yes');

    // 2. Approve it. There is no "publish" for a correction: what an editor
    //    does with one worth keeping is convert it, which copies the text into
    //    a comment on the game - author and timestamp included - and destroys
    //    the submission.
    await adminPage.getByRole('button', { name: 'Convert to comment' }).click();
    await expect(adminPage).toHaveURL(/\/admin\/games\/submissions$/);
    await expectNoRow(adminPage, game.name);

    // The half nothing covered: the correction is now public, on the game the
    // visitor was reading, under their own name.
    await page.goto(`/games/${game.slug}`);
    const comment = await findComment(page, info);
    await expect(comment.text).toBeVisible();
    await expect(comment.block).toContainText(FIXTURE.contributor.userid);

    // The comment approval created is this test's row too, so it goes as well.
    // The author is the visitor, so their own trash link is the cheapest way
    // out - the admin's screens for a comment are driven from
    // public-write/content.spec.js instead.
    await deleteComment(page, comment);

    await deleteGame(adminPage, game);
  });
});

// TODO: several files in one correction, and a non-image attachment - the admin
// renders those as a link rather than an <img>.
//
// Found writing the approval test, and left alone because fixing it is not a
// test's job: App\Livewire\Admin\Games\GameSubmissionsTable declares
// `public array $filters = ['processed' => 'no']` to open the queue on the
// submissions nobody has looked at yet, but a filter's key is Str::snake() of
// its *name* unless one is passed to make() - so the two filters there are
// keyed 'reviewed' and 'has_attachments', 'processed' matches neither, and the
// default is silently dropped. The queue has always opened on every submission
// ever made, reviewed or not.
