import { expect } from '@playwright/test';

// Strings that only show up once Laravel has rendered an exception page. The
// status check below is the primary signal; these catch the rarer case of a
// page that swallows an exception and still answers 200.
const EXCEPTION_MARKERS = [
  'ErrorException',
  'QueryException',
  'InvalidArgumentException',
];

function escapeForRegExp(value) {
  return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

/**
 * Assert that `path` rendered as itself.
 *
 * The URL check is not redundant with the status check. An unauthenticated
 * admin request is redirected to '/' rather than refused, and page.goto()
 * reports the status of the *last* response, so a redirected request is
 * indistinguishable from a healthy one by status alone.
 */
export async function expectPageRenders(page, response, path) {
  expect(response?.status()).toBe(200);
  await expect(page).toHaveURL(new RegExp(`${escapeForRegExp(path)}$`));

  const content = await page.content();
  for (const marker of EXCEPTION_MARKERS) {
    expect(content).not.toContain(marker);
  }
}
