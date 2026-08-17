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

  test('picks a review for the Screenstar card', async ({ page }) => {
    await page.goto('/');

    // The card picks a published review at random, so which one it lands on is
    // not assertable and neither is the game behind it - the figure is only
    // rendered when that game has a screenshot. What is assertable is that it
    // landed on a review at all: with none, @isset leaves the body empty and
    // there is no link in the card besides its own heading.
    const card = page.locator('.card').filter({
      has: page.getByRole('heading', { name: 'Screenstar' }),
    });

    await expect(card.getByRole('link', { name: /^Read the review/ }))
      .toHaveAttribute('href', /\/reviews\/\d+$/);
    // review_date is a cast, and the card formats it unguarded.
    await expect(card).toContainText(/\w+ \d{1,2}, \d{4} by /);
  });

  test('picks an illustrated interview for the Who is it? card', async ({ page }) => {
    await page.goto('/');

    // This one only ever picks an interview whose individual has a picture -
    // that is the whole of its query - so unlike Screenstar the image is part
    // of what the card promises, and is served by a route rather than by a
    // file path. Which interview it is is still random.
    const card = page.locator('.card').filter({
      has: page.getByRole('heading', { name: 'Who is it?' }),
    });

    await expect(card.getByRole('img', { name: /^Picture of / }))
      .toHaveAttribute('src', /\/individuals\/\d+\/avatar\.webp$/);
    await expect(card.getByRole('link', { name: /^Read interview of / }))
      .toHaveAttribute('href', /\/interviews\/\d+$/);
  });

  // The card renders its heading either way and its body only @isset($trivia),
  // so the text is the only thing that tells a working card from an empty one.
  //
  // The seeded row is asserted by name rather than by presence of any row:
  // admin-write/others.spec.js has trivia of its own in the table while this
  // runs, and the card picks one at random - so this reloads until its own
  // comes up. A card that stopped querying would never show it.
  test('shows a trivia in the Did you know? card', async ({ page }) => {
    await expect(async () => {
      await page.goto('/');
      await expect(page.getByText(FIXTURE.trivia.text)).toBeVisible({ timeout: 2000 });
    }).toPass({ timeout: 30000 });
  });
});

// x-cards.latest-menus is listed with the home page cards, but it is not on
// the home page: menus/index, menus/search and menus/show are the three views
// that include it. Asserted from here rather than from public/menus.spec.js
// because it is a home-style card and reads the menus hierarchy the way the
// others read reviews and interviews - see the note in its test.
test.describe('Latest menus card', () => {
  test('lists recently updated disks', async ({ page }) => {
    await page.goto('/menusets');

    const card = page.locator('.card').filter({
      has: page.getByRole('heading', { name: 'Latest menus' }),
    });

    // The card merges the seven newest disks with the seven newest dumps and
    // takes seven of the two - so the seeded disk is only in there while no
    // seven newer rows exist, and a write spec building a set is exactly that.
    // Hence the shape of an entry rather than which entry: each one resolves a
    // dump or a disk back to its menu and its set, and links to the page of
    // the set that disk sits on.
    const entry = card.getByRole('heading', { level: 3 }).first();

    await expect(entry.getByRole('link'))
      .toHaveAttribute('href', /\/menusets\/\d+\?page=\d+#menudisk-\d+$/);
    // Which of the two the row was, and the date it carries. The @else of that
    // branch renders the literal 'Event:', which is the not-a-dump-or-a-disk
    // case and should never be reachable.
    await expect(card).toContainText(/(Dump|Info) updated/);
    await expect(card).not.toContainText('Event:');
    await expect(card.getByRole('link', { name: 'View all database changes' })).toBeVisible();
  });
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
