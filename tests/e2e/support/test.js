import { test as base, expect } from '@playwright/test';

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
    const uncaughtErrors = [];
    page.on('pageerror', exception => {
      uncaughtErrors.push(exception.message);
    });

    await use(page);

    expect(uncaughtErrors, 'uncaught JS errors on the page').toEqual([]);
  },
});

export { expect };
