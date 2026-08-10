/**
 * The data the specs assert against.
 *
 * Mirrors database/seeders/E2ESeeder.php - change one, change the other. The
 * seeder holds the same ids and names as PHP constants, so a mismatch is
 * greppable rather than mysterious.
 *
 * Specs should read names from here rather than hard-coding strings, so that
 * renaming a fixture row is a one-line change.
 */
export const FIXTURE = {
  admin: { id: 1, userid: 'admin', password: 'password' },
  user: { id: 2, userid: 'testuser', password: 'password' },

  game: {
    id: 1,
    slug: 'xenon-2-megablast',
    name: 'Xenon 2 Megablast',
    screenshotId: 1,
    screenshotExt: 'png',
    factId: 1,
  },
  release: { id: 1, year: '1989', boxscanId: 1 },
  submission: { id: 1 },
  series: { id: 1, name: 'Playwright Test Series' },
  company: { id: 1, name: 'Playwright Test Company' },
  individual: { id: 1, name: 'Playwright Test Individual' },
  crew: { id: 1, name: 'Playwright Test Crew' },

  article: { id: 1, title: 'Playwright Test Article' },
  review: { id: 1 },
  interview: { id: 1 },
  news: { id: 1, headline: 'Welcome to Atari Legend' },
  comment: { id: 1 },

  magazine: { id: 1, name: 'Playwright Test Magazine' },
  magazineIssue: { id: 1 },

  menuSet: { id: 1, name: 'Playwright Test Menu Set' },
  menu: { id: 1 },
  menuDisk: { id: 1, contentId: 1 },
  menuSoftware: { id: 1, name: 'Playwright Test Software' },

  // Seeded by migrations rather than by E2ESeeder.
  menuCondition: { id: 4, name: 'Intact' },
  menuContentType: { id: 1, name: 'Game' },

  website: { id: 1, name: 'Playwright Test Website' },
  websiteCategory: { id: 1, name: 'Playwright Test Category' },

  spotlight: { id: 1, screenshotId: 2 },
};
