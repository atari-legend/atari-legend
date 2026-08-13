import { expect } from '@playwright/test';

/**
 * Sign in through the login form.
 *
 * The public specs use this rather than borrowing the admin storage state,
 * because what is worth testing on those pages is what an ordinary account
 * can do. Going through the form each time also means the login flow itself
 * is covered by something other than auth.setup.js.
 *
 * The login field is name="userid", not "email".
 */
export async function signIn(page, user) {
  await page.goto('/login');
  await page.fill('input[name="userid"]', user.userid);
  await page.fill('input[name="password"]', user.password);

  await Promise.all([
    page.waitForNavigation(),
    page.click('form[action*="login"] button[type="submit"]'),
  ]);

  // A failed login re-renders the form, so leaving /login is the assertion
  // that the credentials were accepted.
  await expect(page).not.toHaveURL(/\/login$/);
}

/**
 * Sign out through the nav.
 *
 * Worth driving rather than posting to /logout directly: the link is a GET
 * href that an onclick rewrites into a POST of the hidden #logout-form, so a
 * JavaScript failure here logs nobody out and answers 405 instead. The
 * dropdown has to be opened first or Bootstrap keeps the item unclickable.
 */
export async function signOut(page) {
  await page.click('#user-menu');

  await Promise.all([
    page.waitForNavigation(),
    page.getByRole('link', { name: 'Log out' }).click(),
  ]);
}
