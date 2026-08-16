import { test, expect } from '../support/test.js';
import { FIXTURE } from '../support/fixture.js';
import { PNG, acceptConfirms, uniqueName } from '../support/write.js';

// The admin users section, which was read-only in this suite until now: it had
// an index, an edit form and an autocomplete asserted on, and not one of the
// four routes that write.
//
// It is also the one section that cannot obey the rule the rest of
// admin-write/ follows. A user is the single row no form in this application
// creates - registration is behind an hCaptcha and the admin has no create
// screen - so there is no parent to build. FIXTURE.moderatedUser is seeded for
// this file alone to stand in for one: nothing signs in as it, no read spec
// asserts on it, and every test below puts back what it changed.
//
// Serial, because all four tests rewrite that same row.
test.describe.configure({ mode: 'serial' });

const EDIT = `/admin/users/users/${FIXTURE.moderatedUser.id}/edit`;

/**
 * Save the user edit form and wait for the redirect back to the index.
 *
 * The form posts to the update route and Laravel bounces to the list, so a
 * spec that carries on reading the page it submitted is reading the old one.
 */
async function saveUser(page) {
  await page.getByRole('button', { name: 'Save' }).click();
  await expect(page).toHaveURL(/\/admin\/users\/users$/);
}

test.describe('Admin users', () => {
  // The three social fields are not free-form: UserHelper::validationRules()
  // pins each to the host it belongs to, so anything else is rejected. That
  // rule is the reason this test posts real-looking URLs.
  const SOCIAL = {
    '#facebook': 'https://www.facebook.com/e2e-test',
    '#twitter': 'https://twitter.com/e2etest',
    '#af': 'https://www.atari-forum.com/memberlist.php?mode=viewprofile&u=1',
  };

  test('edits the profile fields and puts them back', async ({ page }) => {
    const website = `https://example.com/${uniqueName('User').replace(/\s+/g, '-')}`;

    try {
      await page.goto(EDIT);
      await page.fill('#website', website);
      for (const [field, value] of Object.entries(SOCIAL)) {
        await page.fill(field, value);
      }
      await saveUser(page);

      await page.goto(EDIT);
      await expect(page.locator('#website')).toHaveValue(website);
      for (const [field, value] of Object.entries(SOCIAL)) {
        await expect(page.locator(field)).toHaveValue(value);
      }
    } finally {
      await page.goto(EDIT);
      for (const field of ['#website', ...Object.keys(SOCIAL)]) {
        await page.fill(field, '');
      }
      await saveUser(page);
    }

    await page.goto(EDIT);
    await expect(page.locator('#website')).toHaveValue('');
  });

  // The permission select is the kind of round trip that only a browser sees:
  // the option is marked selected by a strict comparison in the Blade template,
  // so a value that comes back from the database as a string rather than an int
  // would leave nothing selected and silently demote the account on the next
  // save. That is exactly what happened to a menu set's sort direction - see
  // follow-up 8 in tests/e2e/README.md.
  test('promotes the user to admin and demotes it again', async ({ page }) => {
    try {
      await page.goto(EDIT);
      await page.selectOption('#permission', { label: 'Admin' });
      await saveUser(page);

      await page.goto(EDIT);
      await expect(page.locator('#permission')).toHaveValue('1');
    } finally {
      await page.goto(EDIT);
      await page.selectOption('#permission', { label: 'User' });
      await saveUser(page);
    }

    await page.goto(EDIT);
    await expect(page.locator('#permission')).toHaveValue('2');
  });

  test('deactivates the user and reactivates it', async ({ page }) => {
    try {
      await page.goto(EDIT);
      await page.uncheck('#active');
      await saveUser(page);

      await page.goto(EDIT);
      await expect(page.locator('#active')).not.toBeChecked();
    } finally {
      await page.goto(EDIT);
      await page.check('#active');
      await saveUser(page);
    }

    await page.goto(EDIT);
    await expect(page.locator('#active')).toBeChecked();
  });

  test('gives the user an avatar, keeps it across an unrelated save, and deletes it', async ({ page }) => {
    acceptConfirms(page);

    const avatar = page.locator('img[alt="User avatar"]');
    const avatarPath = `/storage/images/user_avatars/${FIXTURE.moderatedUser.id}.png`;

    try {
      await page.goto(EDIT);
      await page.locator('input[name="avatar"]').setInputFiles({
        name: 'avatar.png',
        mimeType: 'image/png',
        buffer: PNG,
      });
      await saveUser(page);

      await page.goto(EDIT);
      await expect(avatar).toHaveAttribute('src', new RegExp(`${avatarPath}$`));

      // Saving anything else must not take the avatar with it. The form only
      // sends the file field when a file was chosen, so the update has to keep
      // what is already there - it used to write null instead, which dropped
      // the avatar every time an admin edited an e-mail address.
      await page.fill('#website', 'https://example.com/unrelated-edit');
      await saveUser(page);

      await page.goto(EDIT);
      await expect(avatar).toHaveAttribute('src', new RegExp(`${avatarPath}$`));
    } finally {
      await page.goto(EDIT);
      await page.fill('#website', '');
      await saveUser(page);

      // The delete button is the only control in its own form, above the one
      // that saves the rest of the page.
      await page.goto(EDIT);
      await page.locator('form[action$="/avatar"] button[type="submit"]').click();
      await expect(page).toHaveURL(new RegExp(`${EDIT}$`));
    }

    await expect(avatar).toHaveAttribute('src', /images\/unknown\.jpg$/);
  });
});

// TODO: deleting a user. The route exists and the button is on the list, but a
// spec that used it would consume the only account it is allowed to touch, and
// nothing in this application can create another - so the suite would pass once
// and fail on the next run against the same database. Give the admin a create
// screen, or seed a user per run, and this becomes an ordinary test.
//
// TODO: the two moderation filters on the users table (verified, is-admin),
// which are read-only and belong in admin/users.spec.js rather than here.
