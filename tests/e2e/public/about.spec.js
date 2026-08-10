import { test, expect } from '../support/test.js';
import { expectPageRenders } from '../support/assertions.js';

test.describe('About', () => {
  test('renders the about page', async ({ page }) => {
    const response = await page.goto('/about');

    await expectPageRenders(page, response, '/about');
    await expect(page.getByRole('heading', { name: 'About', level: 1 })).toBeVisible();
  });

  test('renders the memorial page', async ({ page }) => {
    const response = await page.goto('/about/andreas');

    await expectPageRenders(page, response, '/about/andreas');
    await expect(page.getByRole('heading', { name: 'Andreas Wahlin', level: 1 })).toBeVisible();
  });

  test('renders the changelog', async ({ page }) => {
    const response = await page.goto('/changelog');

    await expectPageRenders(page, response, '/changelog');
    await expect(page.getByRole('heading', { name: 'Database changes', level: 1 })).toBeVisible();
  });
});
