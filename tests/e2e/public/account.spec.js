import { test, expect } from '../support/test.js';
import { FIXTURE } from '../support/fixture.js';
import { expectPageRenders } from '../support/assertions.js';
import { signIn, signOut } from '../support/auth.js';

// The pages a signed-in visitor - not an admin - can reach.
//
// These live in the 'public' project and sign in for themselves rather than
// borrowing the admin storage state, because the thing worth testing is what
// an ordinary account can do. Signing in through the form each time also means
// the login flow itself is covered by something other than auth.setup.js.

test.describe('Account', () => {
  test('signs in and reaches the profile page', async ({ page }) => {
    await signIn(page, FIXTURE.user);

    const response = await page.goto('/profile');

    await expectPageRenders(page, response, '/profile');
    await expect(page.getByText(FIXTURE.user.userid).first()).toBeVisible();
  });

  test('opens the review submission form for a game', async ({ page }) => {
    await signIn(page, FIXTURE.user);

    // The form is always about a specific game; without one the controller
    // deliberately aborts with a 400.
    const response = await page.goto(`/reviews/submit?game=${FIXTURE.game.id}`);

    await expectPageRenders(page, response, '/reviews/submit');
    await expect(page.getByText(FIXTURE.game.name).first()).toBeVisible();
  });

  test('rejects the review form with no game', async ({ page }) => {
    await signIn(page, FIXTURE.user);

    const response = await page.request.get('/reviews/submit');

    expect(response.status()).toBe(400);
  });

  test('opens the password confirmation page', async ({ page }) => {
    await signIn(page, FIXTURE.user);

    const response = await page.goto('/password/confirm');

    await expectPageRenders(page, response, '/password/confirm');
    await expect(page.locator('input[name="password"]')).toBeVisible();
  });

  test('signs out again', async ({ page }) => {
    await signIn(page, FIXTURE.user);
    await page.goto('/');

    await signOut(page);

    // Back to a guest: the profile page no longer resolves.
    await page.goto('/profile');
    await expect(page).not.toHaveURL(/\/profile$/);
  });

  test('keeps a guest out of the profile page', async ({ page }) => {
    await page.goto('/profile');

    await expect(page).not.toHaveURL(/\/profile$/);
  });

  // TODO: updating the profile, changing the password, uploading an avatar,
  // voting on a game, and posting a comment. Those write, so they belong in
  // the admin-write project's sibling - a public-write project - rather than
  // here; see tests/e2e/README.md.
});

// An account that never confirmed its address. Every route in the app sits
// behind the `verified` middleware, so this is the one user for whom the app
// is a single page - which makes it the only way to prove that middleware
// still turns anyone away.
test.describe('Unverified account', () => {
  test('is sent to the verification notice', async ({ page }) => {
    await signIn(page, FIXTURE.unverifiedUser);

    await page.goto('/profile');

    await expect(page).toHaveURL(/\/email\/verify$/);
    await expect(page.getByText(/verif/i).first()).toBeVisible();
  });
});
