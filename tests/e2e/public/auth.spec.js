import { test, expect } from '../support/test.js';
import { expectPageRenders } from '../support/assertions.js';

// These belong to the 'public' project rather than 'admin': login and register
// carry RedirectIfAuthenticated, so an authenticated session never sees them.

test.describe('Authentication', () => {
  test('renders the login form', async ({ page }) => {
    const response = await page.goto('/login');

    await expectPageRenders(page, response, '/login');
    // The login field is name="userid", not "email".
    await expect(page.locator('input[name="userid"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
  });

  test('renders the registration form', async ({ page }) => {
    const response = await page.goto('/register');

    await expectPageRenders(page, response, '/register');
    await expect(page.locator('input[name="userid"]')).toBeVisible();
    await expect(page.locator('input[name="email"]')).toBeVisible();
  });

  test('renders the password reset request form', async ({ page }) => {
    const response = await page.goto('/password/reset');

    await expectPageRenders(page, response, '/password/reset');
    await expect(page.locator('input[name="email"]')).toBeVisible();
  });

  test('renders the password reset form for a token', async ({ page }) => {
    // The token is not looked up until the form is submitted, so any value
    // renders the page.
    const response = await page.goto('/password/reset/e2e-token');

    await expectPageRenders(page, response, '/password/reset/e2e-token');
    await expect(page.locator('input[name="password"]')).toBeVisible();
  });

  test('rejects a bad password', async ({ page }) => {
    await page.goto('/login');
    await page.fill('input[name="userid"]', 'admin');
    await page.fill('input[name="password"]', 'not-the-password');
    await page.click('form[action*="login"] button[type="submit"]');

    // A failed login re-renders the form rather than leaving /login.
    await expect(page).toHaveURL(/\/login$/);
  });

  // A successful login is already exercised by auth.setup.js, which the whole
  // admin project depends on.
  //
  // TODO: registration end to end (it is captcha-protected), email
  // verification, the profile page and password change, and that an
  // unverified account is held at /email/verify.
});
