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

  // Comments are no longer a Users screen: each section moderates its own, and
  // those screens are covered in admin/games.spec.js and admin/content.spec.js.

  // TODO: changing a user's permission, deactivating an account, and deleting
  // an avatar.
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
