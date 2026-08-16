import { test as base, expect } from '@playwright/test';

/**
 * Fail a test on an uncaught JavaScript exception, without every spec having
 * to remember to wire up the listener.
 *
 * Returns the teardown half, so a fixture reads as: collect, hand the page
 * over, assert. Exported because support/public-write.js builds a second page
 * fixture - the admin context those specs create their parents in - and it
 * deserves the same guard as the primary one.
 */
export function guardAgainstPageErrors(page) {
  const uncaughtErrors = [];
  page.on('pageerror', exception => {
    uncaughtErrors.push(exception.message);
  });

  return (label = 'uncaught JS errors on the page') => {
    expect(uncaughtErrors, label).toEqual([]);
  };
}

/**
 * Fail a test when a page asked this application for a subresource and got a
 * 404 back.
 *
 * Nothing else in the suite notices one. A page whose stylesheet, script or
 * image never arrived still answers 200, still has its heading, and still
 * passes expectPageRenders() - which is exactly how a missing SCEditor script
 * took out the admin's JavaScript for a while (see tests/e2e/README.md,
 * follow-up 3), and how a missing `storage:link` would look today.
 *
 * Three things are deliberately not failures:
 *
 * - **A navigation.** Several specs assert a 404 on purpose - the create forms
 *   that 404 without their parent in admin/menus.spec.js - so the document
 *   request belongs to the spec, not to this guard.
 * - **Another origin.** A game page embeds YouTube; what a third party answers
 *   is not this application's to account for.
 * - **Anything under /storage/.** Those files belong to database rows, and the
 *   write projects are creating and deleting rows while this page is loading.
 *   A card that picks a random review can render another spec's screenshot in
 *   the moment between its HTML going out and that spec's teardown unlinking
 *   the file - a 404 that says nothing about this application, and exactly the
 *   "survive a row you did not expect" case the README asks read specs to
 *   tolerate. The uploads worth asserting on are asserted deliberately, with
 *   expectResourceLoads(), against a row the spec owns.
 *
 * What is left is everything the application ships rather than stores: its
 * scripts, its stylesheets, its fonts and its static images. None of those
 * depend on a row, so a 404 on one is a real defect on every run.
 *
 * Returns the teardown half, like guardAgainstPageErrors() above.
 */
export function guardAgainstMissingSubresources(page, baseURL) {
  const missing = [];

  page.on('response', (response) => {
    if (response.status() !== 404) {
      return;
    }

    const request = response.request();
    if (request.resourceType() === 'document') {
      return;
    }

    if (baseURL && !response.url().startsWith(baseURL)) {
      return;
    }

    if (new URL(response.url()).pathname.startsWith('/storage/')) {
      return;
    }

    // The resource type is what makes the message actionable: 'script' and
    // 'image' are read very differently by whoever sees this fail.
    missing.push(`${request.resourceType()} ${response.url()}`);
  });

  return (label = 'subresources that 404ed') => {
    expect(missing, label).toEqual([]);
  };
}

/**
 * The `test` every spec imports, instead of the one from @playwright/test.
 *
 * It wraps the `page` fixture so that an uncaught JavaScript exception, or a
 * subresource this application answered 404 to, fails the test - without every
 * spec having to remember to wire up the listener.
 *
 * The trade-off: the failure is reported against fixture teardown rather than
 * a line in the test, and a test that has already failed will report twice.
 * That is worth it - a check nobody can forget beats a check that reads nicely.
 */
export const test = base.extend({
  page: async ({ page, baseURL }, use) => {
    const expectNoPageErrors = guardAgainstPageErrors(page);
    const expectNoMissingSubresources = guardAgainstMissingSubresources(page, baseURL);

    await use(page);

    expectNoPageErrors();
    expectNoMissingSubresources();
  },
});

export { expect };
