import { defineConfig, devices } from '@playwright/test';

const PORT = process.env.PORT || 8000;
const baseURL = process.env.PLAYWRIGHT_TEST_BASE_URL || `http://127.0.0.1:${PORT}`;

export default defineConfig({
  testDir: './tests/e2e',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  // These specs only ask whether a page renders. Retrying them would turn an
  // intermittent 500 - exactly what we want to hear about - into a pass.
  retries: 0,
  // Every spec in the 'public' and 'admin' projects is a GET that asserts only
  // on its own response, so nothing there shares state. The 'admin-write' and
  // 'public-write' projects do write, but only to rows they created a moment
  // earlier and delete before they finish - so their work is invisible here,
  // and every project can run at once. Keep both halves of that true: a read
  // spec that asserts a count or a row position, or a write spec that touches a
  // seeded row, breaks it. See tests/e2e/README.md.
  workers: process.env.CI ? 4 : undefined,
  reporter: [
    ['html', { open: 'never' }],
    ['list']
  ],
  use: {
    baseURL,
    // Not 'on-first-retry': retries are off, so that setting would never
    // capture anything. A failing page is the whole point of this suite, so
    // keep the trace for it.
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
  },
  // Public and admin are separate projects so that a broken admin login only
  // takes out the admin specs. When they shared one project, every public
  // spec depended on 'setup' too and could not run at all.
  //
  // Each project selects its specs by directory: a spec belongs in
  // tests/e2e/public/, tests/e2e/admin/, tests/e2e/admin-write/ or
  // tests/e2e/public-write/, one file per section of the site. A spec anywhere
  // else - tests/e2e/support/, or the wrong directory - is silently skipped, so
  // check `npx playwright test --list` after adding one.
  projects: [
    {
      name: 'setup',
      testMatch: /.*\.setup\.js/,
    },
    {
      name: 'public',
      testMatch: 'public/**/*.spec.js',
      use: {
        ...devices['Desktop Chrome'],
        storageState: { cookies: [], origins: [] },
      },
    },
    {
      name: 'admin',
      testMatch: 'admin/**/*.spec.js',
      use: {
        ...devices['Desktop Chrome'],
        storageState: 'tests/e2e/.auth/admin.json',
      },
      dependencies: ['setup'],
    },
    // The specs that create and delete data, which the two projects above
    // deliberately do not.
    //
    // This used to be serial and last, depending on 'public' and 'admin' so
    // that nothing was reading the database while it wrote. That was a mutex
    // rather than a data dependency, and it cost the whole suite to run one
    // write spec.
    //
    // What replaces it is isolation in the specs themselves: each one creates
    // every row it modifies - the parent as well as the child - under a name
    // no other row has, and deletes them again before it ends. There is
    // nothing here for a read spec to see half-done, so this project needs
    // nothing but a session. See tests/e2e/support/write.js.
    {
      name: 'admin-write',
      testMatch: 'admin-write/**/*.spec.js',
      use: {
        ...devices['Desktop Chrome'],
        storageState: 'tests/e2e/.auth/admin.json',
      },
      dependencies: ['setup'],
    },
    // The same, for the writes an ordinary signed-in visitor makes: comments,
    // votes, submissions, a review, their own profile.
    //
    // A clean guest rather than a stored session, because each spec signs in
    // through the login form as FIXTURE.contributor - what is worth testing on
    // these pages is what an ordinary account can do. It still depends on
    // 'setup', for a reason that has nothing to do with its own session: a
    // public form cannot create the game or the review it writes against, so
    // the specs open a second context from .auth/admin.json to build the
    // parent. See tests/e2e/support/public-write.js.
    {
      name: 'public-write',
      testMatch: 'public-write/**/*.spec.js',
      use: {
        ...devices['Desktop Chrome'],
        storageState: { cookies: [], origins: [] },
      },
      dependencies: ['setup'],
    },
  ],
  webServer: process.env.PLAYWRIGHT_TEST_BASE_URL ? undefined : {
    // The PHP dev server directly, rather than `php artisan serve`.
    //
    // ServeCommand wraps the same `php -S` invocation, but it also parses the
    // server's log lines to pretty-print them, and that parser races under
    // concurrency: with several workers in flight it dies on 'Undefined array
    // key 0' partway through a run, taking every remaining test with it. It
    // survived while the suite was small and started failing as it grew.
    //
    // Going direct also means the server inherits the full environment.
    // `artisan serve` forwards only a small allowlist of variables - DB_* not
    // among them - whenever a .env file exists, which is a trap waiting for
    // the first person to add one to CI.
    //
    // tests/e2e/support/server.php is the mod_rewrite shim ServeCommand would
    // otherwise supply; see the comment in that file.
    command: `php -S 127.0.0.1:${PORT} -t public tests/e2e/support/server.php`,
    url: baseURL,
    reuseExistingServer: !process.env.CI,
    timeout: 120 * 1000,
    env: {
      // The dev server handles one request at a time by default, so parallel
      // Playwright workers would queue behind each other. Comfortably above
      // the worker count, since one page load fans out into several asset
      // requests.
      PHP_CLI_SERVER_WORKERS: '16',
    },
  },
});
