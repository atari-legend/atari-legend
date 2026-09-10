import { test, expect } from '../support/test.js';
import { FIXTURE } from '../support/fixture.js';
import { expectPageRenders } from '../support/assertions.js';

test.describe('Admin users', () => {
  test('lists users', async ({ page }) => {
    const response = await page.goto('/admin/users/users');

    await expectPageRenders(page, response, '/admin/users/users');
    await expect(page.getByText(FIXTURE.user.userid).first()).toBeVisible();
  });

  test('opens the edit form for a user', async ({ page }) => {
    const path = `/admin/users/users/${FIXTURE.user.id}/edit`;

    await expectPageRenders(page, await page.goto(path), path);
  });

  // One screen per section since the comment tables were split: the table a
  // comment is in is what says which section it is on, so each has its own
  // list and its own edit form.
  for (const section of ['games', 'articles', 'interviews', 'reviews']) {
    test(`lists ${section} comments`, async ({ page }) => {
      const path = `/admin/users/comments/${section}`;

      await expectPageRenders(page, await page.goto(path), path);
    });
  }

  test('opens the edit form for a game comment', async ({ page }) => {
    // The form links back to the game the comment is on, through the foreign
    // key on game_comments.
    const path = `/admin/users/comments/games/${FIXTURE.comment.id}/edit`;

    await expectPageRenders(page, await page.goto(path), path);
  });

  // TODO: changing a user's permission, deactivating an account, deleting an
  // avatar, and moderating a comment.
});

// The author field on the news, review, interview and article forms picks a
// user through this. It hands back account names, so it is behind the admin
// middleware; the guest check for it lives in public/account.spec.js.
test.describe('Admin users autocomplete', () => {
  test('serves the users autocomplete', async ({ page }) => {
    const response = await page.request.get('/admin/ajax/users.json');

    expect(response.status()).toBe(200);
    expect(response.headers()['content-type'] ?? '').toContain('application/json');
    expect(Array.isArray(await response.json())).toBe(true);
  });

  test('filters on the query string', async ({ page }) => {
    const response = await page.request.get(
      `/admin/ajax/users.json?q=${encodeURIComponent(FIXTURE.user.userid)}`
    );

    expect((await response.json()).map((row) => row.userid)).toContain(FIXTURE.user.userid);
  });
});
