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

/**
 * Assert that a non-HTML route answered with the resource it claims to serve.
 *
 * expectPageRenders() is wrong for these: page.goto() on a download returns a
 * null response, and page.content() on an image gives you Chrome's viewer
 * markup rather than the payload. Fetch with page.request.get() instead, which
 * inherits the project's storage state, so it is authenticated under 'admin'
 * and a clean guest under 'public'.
 *
 * The URL check matters for the same reason it does in expectPageRenders: an
 * unauthenticated admin request is *redirected* to '/', and the request context
 * follows redirects, so status alone cannot tell the two apart.
 *
 * The body length check is not padding. Several of these routes are
 * response()->stream() callbacks: the 200 and its headers are on the wire
 * before the callback runs, so a failure inside one produces a truncated 200
 * that every other assertion here would happily accept.
 *
 * @param {import('@playwright/test').APIResponse} response
 * @param {string} path
 * @param {{contentType?: string, magic?: string}} expected
 */
export async function expectResourceLoads(response, path, { contentType, magic } = {}) {
  expect(response.status()).toBe(200);
  expect(response.url()).toMatch(new RegExp(`${escapeForRegExp(path)}$`));

  if (contentType) {
    // toContain, not toBe: Laravel appends '; charset=UTF-8' to text types.
    expect(response.headers()['content-type'] ?? '').toContain(contentType);
  }

  const body = await response.body();
  expect(body.byteLength).toBeGreaterThan(0);

  if (magic) {
    // WEBP sits at offset 8 inside the RIFF container, so look a little way in
    // rather than only at the first bytes.
    expect(body.subarray(0, magic.length + 8).toString('latin1')).toContain(magic);
  }
}
