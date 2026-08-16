import { expect } from '@playwright/test';

/**
 * Driving the comment box, which four pages share.
 *
 * A game, a review, an interview and an article all render the same form and
 * the same components.cards.partial_comment beneath it, so the round trip -
 * post, edit, delete - is written once here rather than four times.
 *
 * Editing and deleting are the reason this is worth a browser at all. Both
 * controls are JavaScript: resources/js/comments.js toggles `d-none` on the
 * comment text, on a form that is hidden in the markup and on the save button,
 * and both the save and the delete links are anchors whose onclick submits a
 * form by id. tests/Feature/Public/ContentPagesTest posts at /comments/update
 * and /comments/delete directly and passes whether or not any of that still
 * works.
 */

/**
 * Post a comment through the form on the page.
 *
 * `form` is a selector rather than a locator so a caller can point at the one
 * form on the page by its action - each of the four posts somewhere different.
 */
export async function postComment(page, form, body) {
  const box = page.locator(form);

  await box.locator('textarea[name="comment"]').fill(body);
  await box.getByRole('button', { name: 'Submit' }).click();
  await page.waitForLoadState('domcontentloaded');

  return findComment(page, body);
}

/**
 * A comment on screen, by the text it carries.
 *
 * Found through its `div#comment-{id}` rather than by text alone: the hidden
 * edit form holds a second copy of the same words - a textarea's value is its
 * text content in the markup - so `hasText` on its own matches twice and fails
 * on strict mode.
 *
 * Returns the id, the text block, and the `.card-body` that wraps both the
 * text and its controls. Every control is reached through that wrapper, so a
 * page showing more than one comment cannot cross the wires.
 */
export async function findComment(page, body) {
  const text = page.locator('div[id^="comment-"]', { hasText: body });
  await expect(text).toHaveCount(1);

  const id = (await text.getAttribute('id')).replace('comment-', '');

  return {
    id,
    text,
    block: page.locator('.card-body').filter({ has: page.locator(`div#comment-${id}`) }),
  };
}

/**
 * Edit a comment in place, asserting the toggle on the way.
 *
 * The assertions are the point rather than a courtesy: if comments.js has not
 * run, the form stays `d-none`, filling it still works and the save link still
 * submits it - so a test that only checked the text afterwards would pass with
 * the JavaScript removed.
 */
export async function editComment(page, comment, body) {
  const form = page.locator(`#comment-edit-${comment.id}`);
  const save = comment.block.locator(`[data-comment-save="${comment.id}"]`);

  await expect(form).toBeHidden();
  await expect(save).toBeHidden();

  await comment.block.locator(`[data-comment-edit="${comment.id}"]`).click();

  await expect(comment.text).toBeHidden();
  await expect(form).toBeVisible();
  await expect(save).toBeVisible();

  await form.locator('textarea[name="comment"]').fill(body);
  await save.getByRole('link').click();
  await page.waitForLoadState('domcontentloaded');

  return findComment(page, body);
}

/**
 * Delete a comment through its own trash link.
 *
 * The link is an anchor with the delete route as its href and an onclick that
 * submits the hidden form instead - so a JavaScript failure here navigates to
 * /comments/delete as a GET and answers 405 rather than deleting anything.
 *
 * No acceptConfirms(): unlike every delete in the admin, this one is not
 * wrapped in a confirm().
 */
export async function deleteComment(page, comment) {
  await comment.block.locator('a[href$="/comments/delete"]').click();
  await page.waitForLoadState('domcontentloaded');

  await expect(page.locator(`div#comment-${comment.id}`)).toHaveCount(0);
}
