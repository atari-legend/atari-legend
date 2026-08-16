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
 * The `test` every spec imports, instead of the one from @playwright/test.
 *
 * It wraps the `page` fixture so that an uncaught JavaScript exception fails
 * the test, without every spec having to remember to wire up the listener.
 *
 * The trade-off: the failure is reported against fixture teardown rather than
 * a line in the test, and a test that has already failed will report twice.
 * That is worth it - a check nobody can forget beats a check that reads nicely.
 */
export const test = base.extend({
  page: async ({ page }, use) => {
    const expectNoPageErrors = guardAgainstPageErrors(page);

    await use(page);

    expectNoPageErrors();
  },
});

export { expect };
