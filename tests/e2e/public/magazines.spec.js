import { test, expect } from '../support/test.js';
import { FIXTURE } from '../support/fixture.js';
import { expectPageRenders, expectResourceLoads } from '../support/assertions.js';

test.describe('Magazines', () => {
  test('lists magazines', async ({ page }) => {
    const response = await page.goto('/magazines');

    await expectPageRenders(page, response, '/magazines');
    await expect(page.getByRole('heading', { name: 'Magazines', level: 1 })).toBeVisible();
    await expect(page.getByRole('link', { name: FIXTURE.magazine.name }).first()).toBeVisible();
  });

  test('displays one magazine', async ({ page }) => {
    await page.goto('/magazines');

    // exact: the entry in the list is a link whose whole name is the magazine.
    // Now that the seeded issue has a cover, the "latest issues" and "random
    // issue" cards render as well, and their links say more than the name and
    // point at a page-and-anchor for the issue rather than at the magazine.
    await page.getByRole('link', { name: FIXTURE.magazine.name, exact: true }).click();

    await expect(page).toHaveURL(new RegExp(`/magazines/${FIXTURE.magazine.id}$`));
    await expect(page.getByRole('heading', { name: FIXTURE.magazine.name, level: 1 })).toBeVisible();
  });

  test('renders the index of an issue', async ({ page }) => {
    await page.goto(`/magazines/${FIXTURE.magazine.id}`);

    // Scoped to the seeded issue's card. Every issue of a magazine is a card on
    // this one page, and the write specs add issues to magazines of their own -
    // but a card is the only thing keeping their rows out of these assertions.
    const card = page.locator(`#magazine-issue-${FIXTURE.magazineIssue.id}`);
    const index = FIXTURE.magazineIndex;

    // The page a row sits on is what the view sorts by, so the four rows are
    // read in an order the seeder does not control.
    await expect(card.locator('tbody tr td:last-child')).toHaveText(
      [index.text, index.game, index.software, index.individual].map((row) => String(row.page))
    );

    // A row links to a game, a menu software, an individual, or to nothing at
    // all - and that last shape is the reason the first three are worth
    // asserting separately.
    const gameRow = card.locator('tbody tr').filter({ hasText: index.game.type });
    await expect(gameRow.getByRole('link', { name: FIXTURE.game.name })).toHaveAttribute(
      'href',
      new RegExp(`/games/${FIXTURE.game.slug}$`)
    );
    // No title of its own, so the game's name is the whole cell, plus the score.
    await expect(gameRow).toContainText(index.game.score);

    // A title of its own does not replace the link, it follows it.
    const softwareRow = card.locator('tbody tr').filter({ hasText: index.software.title });
    await expect(softwareRow.getByRole('link', { name: FIXTURE.menuSoftware.name })).toBeVisible();
    await expect(softwareRow.locator('td').first()).toContainText(`: ${index.software.title}`);

    const individualRow = card.locator('tbody tr').filter({ hasText: index.individual.title });
    await expect(individualRow.getByRole('link', { name: FIXTURE.individual.name })).toHaveAttribute(
      'href',
      new RegExp(`individual_id=${FIXTURE.individual.id}$`)
    );

    const textRow = card.locator('tbody tr').filter({ hasText: index.text.title });
    await expect(textRow.getByRole('link')).toHaveCount(0);
    await expect(textRow.locator('td').first()).toHaveText(index.text.title);
    await expect(textRow).toContainText(index.text.type);
  });

  test('shows an issue cover', async ({ page }) => {
    await page.goto(`/magazines/${FIXTURE.magazine.id}`);

    // getImageAttribute() returns images/no-cover.svg when imgext is null, so
    // asserting an <img> is there would pass with the column ignored: the
    // magazine_scans path is what says the stored cover was found.
    const cover = page.locator(`img[src*="/magazine_scans/${FIXTURE.magazineIssue.id}."]`).first();
    await expect(cover).toBeVisible();

    const path = new URL(await cover.getAttribute('src')).pathname;
    await expectResourceLoads(await page.request.get(path), path, { magic: 'PNG' });
  });

  test('links an issue to archive.org', async ({ page }) => {
    await page.goto(`/magazines/${FIXTURE.magazine.id}`);

    // getReadUrlAttribute() rewrites /details/ to /stream/, which is the point
    // of the assertion - the stored URL is not the one on the page.
    // .first(): the issue header carries the link twice, once on the cover and
    // once as the book icon beside the issue number.
    const read = FIXTURE.magazineIssue.archiveUrl.replace('/details/', '/stream/');
    await expect(page.locator(`a[href="${read}"]`).first()).toBeVisible();
  });

  // TODO: the page-count chart, which the equivalent test in public/games.spec.js
  // covers for /games. card_page_count.blade.php renders only above four issues
  // and E2ESeeder seeds one, so this needs five seeded before it can assert
  // anything.
});
