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
  // Never confirmed its address, so every route redirects it to the
  // verification notice. See E2ESeeder::seedUsers().
  unverifiedUser: { id: 3, userid: 'unverified', password: 'password' },

  game: {
    id: 1,
    slug: 'xenon-2-megablast',
    name: 'Xenon 2 Megablast',
    // Shorter than the name above and matching the same term, so the
    // autocompletes that merge games with their AKAs have something to rank.
    akaName: 'Xenon II',
    screenshotId: 1,
    screenshotExt: 'png',
    factId: 1,
  },
  release: { id: 1, year: '1989', boxscanId: 1 },
  submission: { id: 1 },
  series: { id: 1, name: 'Playwright Test Series' },
  // The company publishes and developed the game, the individual is credited
  // on it, and the genre and engine below are crossed with it - so each field
  // of the game search has something to find. See E2ESeeder::seedGameLinks().
  company: { id: 1, name: 'Playwright Test Company' },
  individual: { id: 1, name: 'Playwright Test Individual' },
  crew: { id: 1, name: 'Playwright Test Crew' },
  sndh: { id: 'Playwright/Test Tune.sndh', title: 'Playwright Test Tune' },

  port: { id: 1, name: 'Playwright Test Port' },
  progressSystem: { id: 1, name: 'Playwright Test Progress' },
  genre: { id: 1, name: 'Playwright Test Genre' },
  programmingLanguage: { id: 1, name: 'Playwright Test Assembly' },
  engine: { id: 1, name: 'Playwright Test Engine' },
  control: { id: 1, name: 'Playwright Test Joystick' },
  soundHardware: { id: 1, name: 'Playwright Test YM2149' },
  copyProtection: { id: 1, name: 'Playwright Test Copy Protection' },

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
