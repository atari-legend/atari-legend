import { test, expect } from '../support/public-write.js';
import { FIXTURE } from '../support/fixture.js';
import { expectPageRenders, expectResourceLoads } from '../support/assertions.js';
import { signIn, signOut } from '../support/auth.js';
import { PNG, uniqueName } from '../support/write.js';

// What a visitor can do to their own account.
//
// This is the one spec in the suite that mutates a seeded row rather than one
// it created, because there is no public form that creates an account without
// solving an hCaptcha. So it has a seeded row of its own - FIXTURE.accountUser,
// which nothing else in the suite signs in as, looks at or lists by name - and
// it puts back everything it changes.
//
// Serial, and that is not decoration. `fullyParallel` runs the tests in a file
// at the same time by default, and these three all rewrite one row; the
// password test in particular would sign the others out from underneath
// themselves halfway through.
test.describe.configure({ mode: 'serial' });

/**
 * Take the avatar off the account, if it has one, and leave the profile page
 * showing none.
 *
 * Removal is entirely user.js: the trash link has no href of its own, and all
 * it does is drop the <img> and set the hidden field the controller reads.
 * Without the script the form posts an empty avatar and an empty flag, which
 * UserController::update() treats as "no change" - so the avatar silently
 * stays, and the assertions below are what say so.
 *
 * Assumes the caller is on /profile.
 */
async function removeAvatar(page) {
  const trash = page.locator('#delete-avatar');

  if (await trash.count() === 0) {
    await expect(page.locator('#avatar-image')).toHaveCount(0);

    return;
  }

  await trash.click();

  await expect(page.locator('#avatar-image')).toHaveCount(0);
  await expect(page.locator('#avatar-removed')).toHaveValue('on');

  await page.getByRole('button', { name: 'Update profile' }).click();

  await page.goto('/profile');
  await expect(page.locator('#avatar-image')).toHaveCount(0);
}

test.describe('Profile', () => {
  test('updates the profile and puts it back', async ({ page }) => {
    const website = `https://example.org/${uniqueName('site').replace(/\s+/g, '-')}`;

    await signIn(page, FIXTURE.accountUser);
    const response = await page.goto('/profile');
    await expectPageRenders(page, response, '/profile');

    // Each social link is validated with starts_with against its own host, so
    // these are not interchangeable placeholders.
    await page.fill('#website', website);
    await page.fill('#facebook', 'https://www.facebook.com/atarilegend');
    await page.fill('#twitter', 'https://twitter.com/atarilegend');
    await page.fill('#af', 'https://www.atari-forum.com/memberlist.php?mode=viewprofile&u=1');
    await page.getByRole('button', { name: 'Update profile' }).click();

    await expect(page.getByRole('alert')).toContainText('Your profile has been updated');

    // update() re-renders the view with the request flashed back, so the fields
    // would show what was typed even if nothing had been saved. Fetch the page
    // again to read the row rather than the input.
    await page.goto('/profile');
    await expect(page.locator('#website')).toHaveValue(website);
    await expect(page.locator('#facebook')).toHaveValue('https://www.facebook.com/atarilegend');
    await expect(page.locator('#twitter')).toHaveValue('https://twitter.com/atarilegend');

    // Put it back. Blank is valid for all four - they are `nullable`.
    for (const field of ['#website', '#facebook', '#twitter', '#af']) {
      await page.fill(field, '');
    }
    await page.getByRole('button', { name: 'Update profile' }).click();

    await page.goto('/profile');
    await expect(page.locator('#website')).toHaveValue('');
    await expect(page.locator('#facebook')).toHaveValue('');
  });

  test('uploads an avatar and removes it again', async ({ page }) => {
    await signIn(page, FIXTURE.accountUser);
    await page.goto('/profile');

    // Clear whatever is there rather than asserting there is nothing.
    //
    // This is the one test in the suite whose subject is a seeded row it cannot
    // recreate, so a run that dies between the upload and the removal leaves an
    // avatar behind - and an opening assertion of "no avatar" would then fail
    // every run afterwards, on the previous run's mess rather than on anything
    // real. Starting from a known state instead costs nothing and is not a hole:
    // removing an avatar that is already there goes through exactly the same
    // user.js the test is about, so a broken script still fails here.
    await removeAvatar(page);

    await page.locator('input[name="avatar"]').setInputFiles({
      name: 'avatar.png',
      mimeType: 'image/png',
      buffer: PNG,
    });
    await page.getByRole('button', { name: 'Update profile' }).click();

    await page.goto('/profile');
    const avatar = page.locator('#avatar-image');
    await expect(avatar).toHaveAttribute(
      'src',
      new RegExp(`/storage/images/user_avatars/${FIXTURE.accountUser.id}\\.png$`)
    );

    // Served, not merely linked: the file is written straight to the public
    // disk with no route in front of it, so a wrong path looks fine in the
    // markup and 404s in the browser.
    const path = new URL(await avatar.getAttribute('src')).pathname;
    await expectResourceLoads(await page.request.get(path), path, { magic: 'PNG' });

    await removeAvatar(page);
  });
});

test.describe('Password', () => {
  test('changes the password and changes it back', async ({ page }) => {
    const replacement = 'e2e-replacement-password';

    await signIn(page, FIXTURE.accountUser);
    await page.goto('/profile');

    await page.fill('#password-current', FIXTURE.accountUser.password);
    await page.fill('#password', replacement);
    await page.fill('#password-confirm', replacement);
    await page.getByRole('button', { name: 'Change password' }).click();

    await expect(page.getByRole('alert')).toContainText('Your password has been changed');

    // The flash is the controller talking about itself. Signing in again with
    // the new password is the only thing that proves the legacy sha512+salt
    // pair was rewritten rather than only the bcrypt column.
    await signOut(page);
    await signIn(page, { ...FIXTURE.accountUser, password: replacement });

    // Put it back, and prove that too - an interrupted run here would leave an
    // account the rest of the suite cannot sign in as.
    await page.goto('/profile');
    await page.fill('#password-current', replacement);
    await page.fill('#password', FIXTURE.accountUser.password);
    await page.fill('#password-confirm', FIXTURE.accountUser.password);
    await page.getByRole('button', { name: 'Change password' }).click();

    await expect(page.getByRole('alert')).toContainText('Your password has been changed');

    await signOut(page);
    await signIn(page, FIXTURE.accountUser);
    await expect(page.locator('#user-menu')).toBeVisible();
  });
});

// TODO: the e-mail address, which is the one profile field with a uniqueness
// rule - and the one whose round trip would want a second account to collide
// with.
