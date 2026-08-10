import { test, expect } from '../support/test.js';
import { FIXTURE } from '../support/fixture.js';
import { expectPageRenders } from '../support/assertions.js';

// The pages a signed-in visitor - not an admin - can reach.
//
// These live in the 'public' project and sign in for themselves rather than
// borrowing the admin storage state, because the thing worth testing is what
// an ordinary account can do. Signing in through the form each time also means
// the login flow itself is covered by something other than auth.setup.js.

async function signIn(page) {
  await page.goto('/login');
  await page.fill('input[name="userid"]', FIXTURE.user.userid);
  await page.fill('input[name="password"]', FIXTURE.user.password);
  await Promise.all([
    page.waitForNavigation(),
    page.click('form[action*="login"] button[type="submit"]'),
  ]);
  await expect(page).not.toHaveURL(/\/login$/);
}

test.describe('Account', () => {
  test('signs in and reaches the profile page', async ({ page }) => {
    await signIn(page);

    const response = await page.goto('/profile');

    await expectPageRenders(page, response, '/profile');
    await expect(page.getByText(FIXTURE.user.userid).first()).toBeVisible();
  });

  test('opens the review submission form for a game', async ({ page }) => {
    await signIn(page);

    // The form is always about a specific game; without one the controller
    // deliberately aborts with a 400.
    const response = await page.goto(`/reviews/submit?game=${FIXTURE.game.id}`);

    await expectPageRenders(page, response, '/reviews/submit');
    await expect(page.getByText(FIXTURE.game.name).first()).toBeVisible();
  });

  test('rejects the review form with no game', async ({ page }) => {
    await signIn(page);

    const response = await page.request.get('/reviews/submit');

    expect(response.status()).toBe(400);
  });

  test('keeps a guest out of the profile page', async ({ page }) => {
    await page.goto('/profile');

    await expect(page).not.toHaveURL(/\/profile$/);
  });

  // TODO: updating the profile, changing the password, uploading an avatar,
  // voting on a game, and posting a comment - all of which write, so they need
  // their own fixtures before they can run alongside the read-only specs.
});
