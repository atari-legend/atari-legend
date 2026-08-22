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
  admin: { id: 101, userid: 'admin', password: 'password' },
  user: { id: 102, userid: 'testuser', password: 'password' },
  // Never confirmed its address, so every route redirects it to the
  // verification notice. See E2ESeeder::seedUsers().
  unverifiedUser: { id: 103, userid: 'unverified', password: 'password' },

  // The two accounts the public-write project signs in as, so that it never
  // writes through `user` above - which owns the seeded comment on the seeded
  // game, and which admin-write/content.spec.js picks in an autocomplete.
  //
  // `accountUser` is separate from `contributor` because
  // public-write/account.spec.js rewrites its profile and its password. An
  // account whose password is in flux cannot also be the one every other spec
  // in the project is signing in with at the same moment.
  contributor: { id: 104, userid: 'contributor', password: 'password' },
  accountUser: {
    id: 105,
    userid: 'accounttester',
    email: 'accounttester@example.com',
    password: 'password',
  },

  // The account admin-write/users.spec.js edits. A user is the one row no form
  // in this application can create - registration is behind an hCaptcha and the
  // admin has no create screen - so that spec cannot build its own parent the
  // way every other write spec does, and rewrites this one instead. Nothing
  // signs in as it and no read spec asserts on it.
  moderatedUser: {
    id: 106,
    userid: 'moderated',
    email: 'moderated@example.com',
  },

  game: {
    id: 201,
    slug: 'xenon-2-megablast',
    name: 'Xenon 2 Megablast',
    // Shorter than the name above and matching the same term, so the
    // autocompletes that merge games with their AKAs have something to rank.
    akaName: 'Xenon II',
    screenshotId: 401,
    screenshotExt: 'png',
    factId: 601,
  },
  release: { id: 301, year: '1989', boxscanId: 501 },
  submission: { id: 701 },
  series: { id: 801, name: 'Playwright Test Series' },
  // The company publishes and developed the game, the individual is credited
  // on it, and the genre and engine below are crossed with it - so each field
  // of the game search has something to find. See E2ESeeder::seedGameLinks().
  company: { id: 901, name: 'Playwright Test Company' },
  individual: { id: 1001, name: 'Playwright Test Individual' },
  crew: { id: 1101, name: 'Playwright Test Crew' },
  sndh: { id: 'Playwright/Test Tune.sndh', title: 'Playwright Test Tune' },

  port: { id: 1201, name: 'Playwright Test Port' },
  progressSystem: { id: 1301, name: 'Playwright Test Progress' },
  genre: { id: 1401, name: 'Playwright Test Genre' },
  programmingLanguage: { id: 1501, name: 'Playwright Test Assembly' },
  engine: { id: 1601, name: 'Playwright Test Engine' },
  control: { id: 1701, name: 'Playwright Test Joystick' },
  soundHardware: { id: 1801, name: 'Playwright Test YM2149' },
  copyProtection: { id: 1901, name: 'Playwright Test Copy Protection' },

  article: {
    id: 2001,
    title: 'Playwright Test Article',
    // The badge on /articles. There is no filter by type anywhere in the
    // application - ArticleController::index takes no Request at all - so the
    // badge is the whole of the feature.
    type: 'Playwright Test Article Type',
    screenshotId: 404,
    screenshotCaption: 'Playwright Test Article Screenshot',
  },
  review: { id: 2101 },
  interview: {
    id: 2201,
    screenshotId: 403,
    screenshotCaption: 'Playwright Test Interview Screenshot',
    // The interview's [hotspotUrl] chapter and the [hotspot] it jumps to are
    // both titled this. Nothing else in the fixture exercises either tag.
    chapter: 'Playwright Test Chapter',
  },
  news: {
    id: 2301,
    headline: 'Welcome to Atari Legend',
    // Six more, dated backwards, so /news has a second page. The oldest is the
    // only item on it.
    fillerCount: 6,
    fillerHeadline: 'Playwright Test Filler News',
  },
  comment: { id: 2401 },
  trivia: { text: 'Playwright test trivia.' },

  magazine: { id: 2501, name: 'Playwright Test Magazine' },
  magazineIssue: {
    id: 2501,
    coverExt: 'png',
    // MagazineIssue::getReadUrlAttribute() rewrites /details/ to /stream/, so
    // the link on the page is not the URL stored here.
    archiveUrl: 'https://archive.org/details/playwright-test-issue',
  },
  // The issue's index, one row per shape the public view renders: a row links
  // to a game, a menu software, an individual, or to nothing at all.
  //
  // Listed in id order, which is deliberately not page order - the text row is
  // stored last and displays first. Anything asserting on the order has to say
  // which one it means. The types are seeded by the magazines migration rather
  // than by E2ESeeder.
  magazineIndex: {
    game: { id: 2601, page: 12, type: 'Review', score: '92%' },
    software: { id: 2602, page: 30, type: 'Tutorial', title: 'Playwright Test Cover Disk' },
    individual: { id: 2603, page: 44, type: 'Interview', title: 'Playwright Test Coder Profile' },
    text: { id: 2604, page: 3, type: 'Column', title: 'Playwright Test Editorial' },
  },

  menuSet: { id: 2701, name: 'Playwright Test Menu Set' },
  menu: { id: 2801 },
  menuDisk: { id: 2901, contentId: 3101 },
  menuSoftware: { id: 3001, name: 'Playwright Test Software' },

  // Seeded by migrations rather than by E2ESeeder. The four conditions come
  // from 2020_12_20_141639_create_new_menu_structure.php, in this order:
  // Missing, Intro only or partially damaged, Slightly damaged, Intact.
  //
  // Only Intact counts as available - MenuSetController::INTACT_CONDITION_ID -
  // so a set needs one of the other three to be anything less than complete.
  menuCondition: { id: 4, name: 'Intact' },
  menuConditionMissing: { id: 1, name: 'Missing' },
  menuContentType: { id: 1, name: 'Game' },

  website: { id: 3201, name: 'Playwright Test Website' },
  websiteCategory: { id: 3301, name: 'Playwright Test Category' },

  spotlight: { id: 4001, screenshotId: 402 },
};
