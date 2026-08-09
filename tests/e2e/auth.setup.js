import { test as setup, expect } from '@playwright/test';
import path from 'path';

const authFile = path.join(process.cwd(), 'tests/e2e/.auth/admin.json');

setup('authenticate as admin', async ({ page }) => {
  await page.goto('/login');

  // Fill in login credentials (login field uses name="userid")
  await page.fill('input[name="userid"]', 'admin');
  await page.fill('input[name="password"]', 'password');

  // Submit login form and wait for navigation response
  const [response] = await Promise.all([
    page.waitForNavigation(),
    page.click('form[action*="login"] button[type="submit"]'),
  ]);

  expect(response?.status()).toBeLessThan(400);

  // A failed login re-renders the form, so we must have left /login
  await expect(page).not.toHaveURL(/\/login$/);

  // Prove the session can actually reach the admin area before saving it.
  // A request that is unauthenticated, unverified or not an admin is
  // *redirected* rather than refused, so it still answers 200 - without this
  // check every admin spec would silently assert against the home page.
  await page.goto('/admin');
  await expect(page).toHaveURL(/\/admin$/);

  // Save authenticated state
  await page.context().storageState({ path: authFile });
});
