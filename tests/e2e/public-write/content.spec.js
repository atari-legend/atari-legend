import { test, expect } from '../support/public-write.js';
import { FIXTURE } from '../support/fixture.js';
import { expectPageRenders } from '../support/assertions.js';
import { signIn } from '../support/auth.js';
import { postComment, deleteComment, findComment } from '../support/comments.js';
import {
  uniqueName, deleteRow, fillEditor,
  createInterview, deleteInterview, createArticle, deleteArticle,
} from '../support/write.js';

// Commenting on an interview and on an article.
//
// The same box as on a game, on two more pages - which is the point: it is one
// partial, included four times, and a page that forgets to pass `context` and
// `id` to it silently stops recording what an edit was on. Both of these
// comment on something this spec created, because a comment shows up on its
// parent's page and public/interviews.spec.js and public/articles.spec.js are
// reading the seeded ones.

test.describe('Interview comments', () => {
  test('comments on an interview and deletes the comment', async ({ page, adminPage }) => {
    const interview = await createInterview(adminPage);
    const body = uniqueName('Comment');

    await signIn(page, FIXTURE.contributor);

    const response = await page.goto(`/interviews/${interview.id}`);
    await expectPageRenders(page, response, `/interviews/${interview.id}`);

    const comment = await postComment(
      page,
      `form[action$="/interviews/${interview.id}/comment"]`,
      body
    );
    await expect(comment.text).toBeVisible();

    await deleteComment(page, comment);

    await deleteInterview(adminPage, interview);
  });
});

test.describe('Article comments', () => {
  test('comments on an article and deletes the comment', async ({ page, adminPage }) => {
    const article = await createArticle(adminPage);
    const body = uniqueName('Comment');

    await signIn(page, FIXTURE.contributor);

    const response = await page.goto(`/articles/${article.id}`);
    await expectPageRenders(page, response, `/articles/${article.id}`);

    const comment = await postComment(
      page,
      `form[action$="/articles/${article.id}/comment"]`,
      body
    );
    await expect(comment.text).toBeVisible();

    await deleteComment(page, comment);

    await deleteArticle(adminPage, article);
  });
});

// Moderating somebody else's comment.
//
// The visitor's own controls are covered above and on a game; these are the two
// routes only a moderator can reach - PUT and DELETE on
// /admin/users/comments/{comment} - and nothing in the suite touched either.
// The pair matters together: an edit that a visitor cannot see is not
// moderation, so both halves are read back from the public page rather than
// from the admin's own table.
test.describe('Comment moderation', () => {
  test('edits and deletes a visitor comment from the admin', async ({ page, adminPage }) => {
    const article = await createArticle(adminPage);
    const body = uniqueName('Comment');
    const moderated = `${body} moderated`;

    await signIn(page, FIXTURE.contributor);
    await page.goto(`/articles/${article.id}`);

    const comment = await postComment(
      page,
      `form[action$="/articles/${article.id}/comment"]`,
      body
    );

    // Straight to the form rather than through the table: admin/users.spec.js
    // already loads the comments list, and what has no coverage is what the
    // two buttons on this screen do.
    await adminPage.goto(`/admin/users/comments/${comment.id}/edit`);

    // A comment knows nothing about what it is on: both the author and the
    // article are worked out from the pivot tables, so a subtitle naming them
    // is the round trip through Comment::getTargetAttribute().
    await expect(adminPage.locator('.card-subtitle')).toContainText(FIXTURE.contributor.userid);
    await expect(adminPage.locator('.card-subtitle')).toContainText(article.title);

    // The moderator's box is a BBCode editor rather than the plain textarea
    // the visitor gets, so the text goes in through SCEditor - filling the
    // textarea in the markup would post the comment unchanged.
    await fillEditor(adminPage, 'content', moderated);
    await adminPage.getByRole('button', { name: 'Save' }).click();
    await expect(adminPage).toHaveURL(/\/admin\/users\/comments$/);

    // What the visitor now reads, on the same comment - the id has not moved.
    await page.goto(`/articles/${article.id}`);
    const edited = await findComment(page, moderated);
    await expect(edited.text).toBeVisible();
    expect(edited.id).toBe(comment.id);
    await expect(page.getByText(body, { exact: true })).toHaveCount(0);

    // And deleting it, which is this test's cleanup as well as its second
    // route. update() has already landed on the comments table, which is the
    // only screen a comment can be deleted from - the visitor's own trash link
    // is not rendered for anyone else.
    await deleteRow(adminPage, moderated);

    await page.goto(`/articles/${article.id}`);
    await expect(page.locator(`div#comment-${comment.id}`)).toHaveCount(0);

    await deleteArticle(adminPage, article);
  });
});
