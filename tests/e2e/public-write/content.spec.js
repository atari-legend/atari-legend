import { test, expect } from '../support/public-write.js';
import { FIXTURE } from '../support/fixture.js';
import { expectPageRenders } from '../support/assertions.js';
import { signIn } from '../support/auth.js';
import { postComment, deleteComment } from '../support/comments.js';
import {
  uniqueName, createInterview, deleteInterview, createArticle, deleteArticle,
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
