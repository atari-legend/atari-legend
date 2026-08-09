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
  // Every spec is a GET and asserts only on its own response, so nothing here
  // shares state and the suite is safe to parallelise. Keep it that way: a
  // spec that creates or edits data would need its own fixtures, since all
  // workers share one seeded database.
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
  // Each project selects its specs by filename: a new spec file must be named
  // admin-*.spec.js or public-*.spec.js, or no project will pick it up.
  projects: [
    {
      name: 'setup',
      testMatch: /.*\.setup\.js/,
    },
    {
      name: 'public',
      testMatch: /public-.*\.spec\.js/,
      use: {
        ...devices['Desktop Chrome'],
        storageState: { cookies: [], origins: [] },
      },
    },
    {
      name: 'admin',
      testMatch: /admin-.*\.spec\.js/,
      use: {
        ...devices['Desktop Chrome'],
        storageState: 'tests/e2e/.auth/admin.json',
      },
      dependencies: ['setup'],
    },
  ],
  webServer: process.env.PLAYWRIGHT_TEST_BASE_URL ? undefined : {
    // --no-reload is not just about the file watcher. Without it, `artisan
    // serve` only forwards a small allowlist of variables to the PHP server it
    // spawns whenever a .env file exists - DB_* is not on that list. CI has no
    // .env today, so the E2E database settings happen to get through; adding
    // one would silently point the served app at a different database from the
    // one that was just migrated and seeded.
    command: `php artisan serve --no-reload --port=${PORT}`,
    url: baseURL,
    reuseExistingServer: !process.env.CI,
    timeout: 120 * 1000,
    env: {
      // artisan serve defaults to one request at a time, so parallel Playwright
      // workers would queue behind each other. Comfortably above the worker
      // count, since one page load fans out into several asset requests.
      PHP_CLI_SERVER_WORKERS: '8',
    },
  },
});
