import { test, expect } from '../support/test.js';
import { FIXTURE } from '../support/fixture.js';
import { expectPageRenders, expectResourceLoads } from '../support/assertions.js';
import { pickSuggestion } from '../support/autocomplete.js';

test.describe('Home', () => {
  test('renders the home page', async ({ page }) => {
    const response = await page.goto('/');

    await expectPageRenders(page, response, '/');
    await expect(page.getByRole('heading', { name: 'Atari Legend home page' })).toBeVisible();
  });

  test('links to every section from the nav', async ({ page }) => {
    await page.goto('/');

    const nav = page.getByRole('navigation').first();
    for (const [label, path] of [
      ['News', '/news'],
      ['Games', '/games'],
      ['Menus', '/menusets'],
      ['Reviews', '/reviews'],
      ['Interviews', '/interviews'],
      ['Articles', '/articles'],
      ['Links', '/links'],
      ['Mags', '/magazines'],
      ['About', '/about'],
    ]) {
      await expect(nav.getByRole('link', { name: label, exact: true }))
        .toHaveAttribute('href', new RegExp(`${path}$`));
    }
  });

  test('serves the spotlight image', async ({ page }) => {
    // The spotlight card is the only place this route is used.
    const path = `/spotlights/${FIXTURE.spotlight.id}/spotlight.webp`;

    await expectResourceLoads(await page.request.get(path), path, {
      contentType: 'image/webp',
      magic: 'WEBP',
    });
  });

  // TODO: the home page cards (Screenstar, Who is it?, Latest menus, Trivia)
  // each read a different corner of the database and each has broken on its
  // own before. One test per card would be worth having.
});

// The search box in the nav bar, which is in the layout and so on every page.
//
// It is the only autocomplete with data-autocomplete-follow-url: picking a
// suggestion navigates to the url the endpoint built for it, rather than
// filling the field. That url is also the only thing separating a game from a
// piece of menu software in a single list of both, hence one test each.
test.describe('Nav search', () => {
  const NAV_SEARCH = 'input[name="title"].autocomplete';

  // The box is `d-flex d-xl-none d-xxl-flex`: on screens between 1200px and
  // 1400px the nav needs the room for its links, and the box is hidden. The
  // default viewport of 1280px lands in exactly that gap, so the widget tests
  // below would be typing into something nobody can see.
  test.use({ viewport: { width: 1440, height: 900 } });

  test('serves the games-and-software autocomplete', async ({ page }) => {
    const response = await page.request.get('/ajax/games-and-software.json');

    expect(response.status()).toBe(200);
    expect(response.headers()['content-type'] ?? '').toContain('application/json');
    expect(Array.isArray(await response.json())).toBe(true);
  });

  test('gives every suggestion a url and an icon', async ({ page }) => {
    // Both are built in PHP rather than coming from a column, and the box is
    // unusable without them: no url means nowhere to go on selection.
    const response = await page.request.get(
      `/ajax/games-and-software.json?q=${encodeURIComponent(FIXTURE.game.name)}`
    );

    const rows = await response.json();
    expect(rows.map((row) => row.name)).toContain(FIXTURE.game.name);
    for (const row of rows) {
      expect(row.url ?? '').not.toBe('');
      expect(row.icon ?? '').not.toBe('');
    }
  });

  test('follows a game suggestion to its page', async ({ page }) => {
    await page.goto('/');

    await pickSuggestion(page, NAV_SEARCH, FIXTURE.game.name);

    await expect(page).toHaveURL(new RegExp(`/games/${FIXTURE.game.slug}$`));
  });

  test('follows a software suggestion to its menus', async ({ page }) => {
    // The same box, the same list, a different route - menu software resolves
    // to the menus that contain it rather than to a game page.
    await page.goto('/');

    await pickSuggestion(page, NAV_SEARCH, FIXTURE.menuSoftware.name);

    await expect(page).toHaveURL(
      new RegExp(`/menusets/software/${FIXTURE.menuSoftware.id}$`)
    );
  });
});
