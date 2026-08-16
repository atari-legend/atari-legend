import { test, expect } from '../support/test.js';
import { expectPageRenders } from '../support/assertions.js';

test.describe('Admin dashboard', () => {
  test('renders the dashboard', async ({ page }) => {
    const response = await page.goto('/admin');

    await expectPageRenders(page, response, '/admin');
  });

  test('turns away a session that is not an admin', async ({ page, browser }) => {
    // The admin middleware redirects rather than refusing, so the request
    // still answers 200 - which is exactly why every admin assertion checks
    // where it landed as well as the status.
    const guest = await browser.newContext({ storageState: { cookies: [], origins: [] } });
    const guestPage = await guest.newPage();

    await guestPage.goto('/admin');
    await expect(guestPage).not.toHaveURL(/\/admin$/);

    await guest.close();
  });

  // The other half of this - a signed-in non-admin, which is what actually
  // exercises the `admin` middleware rather than the `auth` in front of it -
  // is in public/account.spec.js, the only project with an ordinary session.
  //
  // TODO: the dashboard's own statistics cards deserve their own checks.
});
