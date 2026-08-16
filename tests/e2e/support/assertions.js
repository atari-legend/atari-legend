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
 * A URL ends in `path`, ignoring any query string.
 *
 * Anchoring on the path alone rather than the whole URL is what makes the
 * check a redirect check: '/links?category=1' must still be on /links, but a
 * bounce to '/' must fail.
 */
function endsWithPath(path) {
  const [pathname] = path.split('?');

  return new RegExp(`${escapeForRegExp(pathname)}(\\?.*)?$`);
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
  await expect(page).toHaveURL(endsWithPath(path));

  const content = await page.content();
  for (const marker of EXCEPTION_MARKERS) {
    expect(content).not.toContain(marker);
  }
}

/**
 * Assert on the values of several inputs at once, in order.
 *
 * toHaveText does this for text, and toHaveValues only understands a multiple
 * select, so a column of inputs inside a table has nothing to assert against.
 *
 * Polls rather than reading once: the callers are looking at Livewire
 * components, which replace the inputs underneath them.
 */
export async function expectValues(locator, values) {
  await expect
    .poll(() => locator.evaluateAll((inputs) => inputs.map((input) => input.value)))
    .toEqual(values);
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
  expect(response.url()).toMatch(endsWithPath(path));

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
