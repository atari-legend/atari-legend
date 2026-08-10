import { test, expect } from '../support/test.js';
import { expectPageRenders } from '../support/assertions.js';

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

  // TODO: the home page cards (Screenstar, Who is it?, Latest menus, Trivia)
  // each read a different corner of the database and each has broken on its
  // own before. One test per card would be worth having.
});
