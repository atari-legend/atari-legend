import {
  test as base,
  expect,
  guardAgainstPageErrors,
  guardAgainstMissingSubresources,
} from './test.js';

/**
 * The `test` the public-write project imports.
 *
 * It extends support/test.js rather than replacing it, so `page` still fails
 * on an uncaught JavaScript exception - which matters more here than anywhere
 * else in the suite. The public write forms are the ones with JavaScript
 * between the visitor and the POST: comments.js toggles the inline editor,
 * user.js clears the avatar, bbcode.js and review/submit.js drive the review
 * form's toolbar and preview.
 *
 * What it adds is `adminPage`.
 *
 * These specs cannot obey admin-write's rule on their own. A public form
 * creates a comment, a vote, a submission or a review; it cannot create the
 * game, review, interview or article to hang one off, and for three of those
 * it cannot delete the row again either - only the admin has a screen for a
 * game submission, a news submission or an unpublished review.
 *
 * So a spec runs two sessions at once: `page`, signed in as FIXTURE.contributor
 * through the real login form, and `adminPage`, a second context restored from
 * the storage state auth.setup.js wrote. The parent is built and torn down over
 * there; everything the test is actually about happens over here.
 *
 * Two contexts rather than signing one page in and out twice: the admin
 * session survives the whole test, so teardown does not have to re-authenticate
 * after the public half has finished with the browser.
 */
export const test = base.extend({
  // Lazy, like every Playwright fixture: a spec that never names `adminPage`
  // never opens the context, so the public-only specs pay nothing for it.
  adminPage: async ({ browser, baseURL }, use) => {
    const context = await browser.newContext({
      storageState: 'tests/e2e/.auth/admin.json',
    });
    const page = await context.newPage();
    const expectNoPageErrors = guardAgainstPageErrors(page);
    const expectNoMissingSubresources = guardAgainstMissingSubresources(page, baseURL);

    await use(page);

    // Named, because 'uncaught JS errors on the page' pointing at a context
    // the test never mentions is a puzzle rather than a message.
    expectNoPageErrors('uncaught JS errors on the admin setup page');
    expectNoMissingSubresources('subresources that 404ed on the admin setup page');
    await context.close();
  },
});

export { expect };
