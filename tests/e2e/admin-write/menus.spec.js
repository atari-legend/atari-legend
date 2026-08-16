import { test, expect } from '../support/test.js';
import { FIXTURE } from '../support/fixture.js';
import { expectResourceLoads } from '../support/assertions.js';
import {
  uniqueName, acceptConfirms, pickAutocomplete, deleteByAction,
  createMenuSet, deleteMenuSet, createMenu, deleteMenu, createMenuDisk, deleteMenuDisk,
  createGame, deleteGame, createIndividual, deleteIndividual,
  createMenuSoftware, deleteMenuSoftware,
} from '../support/write.js';

// A set holds menus, a menu holds disks, a disk holds contents. Only the set
// has an index of its own; everything below it is a card on its parent, created
// from a link that carries the parent's id in the query string.
//
// Each test builds its own chain down from a set rather than hanging a menu or
// a disk off the seeded one - see the factories in support/write.js for why.

/** A 1x1 PNG. storeScreenshot() derives the extension from the file's own
 *  magic bytes (UploadedFile::extension() sniffs the content, not the name),
 *  so this has to be a real image rather than a buffer with a .png name. */
const PNG = Buffer.from(
  'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
  'base64'
);

/**
 * The summary card at the top of a menu set's public page.
 *
 * Scoped rather than asserted on the page: /menusets/{set} also renders the
 * reviews, trivia and "Latest menus" side cards, and that last one lists disks
 * from every set - including, once this spec has made one, its own.
 */
async function openPublicSet(page, set) {
  await page.goto(`/menusets/${set.id}`);

  return page.locator('.card').filter({
    has: page.getByRole('heading', { level: 2, name: set.name }),
  });
}

/**
 * One disk's card, on whichever page is already open.
 *
 * The card carries the disk's id, which matters for the same reason as above:
 * its header text - set name, menu label, part - is repeated verbatim by the
 * "Latest menus" card in the sidebar.
 */
function publicDisk(page, disk) {
  return page.locator(`#menudisk-${disk.id}`);
}

/** The same, going to the set's page first. */
async function openPublicDisk(page, set, disk) {
  await page.goto(`/menusets/${set.id}`);

  return publicDisk(page, disk);
}

/**
 * One content card on a disk's edit screen.
 *
 * Two locators, and both are load-bearing. The `:has(> .card-footer > form)`
 * picks the content cards apart from the "Disk content" card they all sit
 * inside, which would otherwise match every filter below it. And the heading is
 * matched on '{order}. {label}' rather than on the label alone, because a
 * content's label comes from whatever it points at
 * (MenuDiskContent::getLabelAttribute) - so a release and the trainer hanging
 * off it share one, and only the order tells their cards apart.
 */
function contentCard(page, order, label) {
  return page
    .locator('.card:has(> .card-footer > form[action*="/content/"])')
    .filter({ has: page.getByRole('heading', { name: `${order}. ${label}` }) });
}

/**
 * A content's database id, read out of its delete form's action.
 *
 * store() redirects to the disk rather than to the content it just made, so
 * there is no URL to take the id from - and deleteByAction() needs it to find
 * that same form again later.
 */
async function contentId(page, order, label) {
  const action = await contentCard(page, order, label)
    .locator('form[action*="/content/"]')
    .getAttribute('action');

  return action.split('/').pop();
}

/**
 * Fill the fields every disk content shares, and save.
 *
 * The head of the form differs per type - a game picker, a software picker, or
 * a radio pair - so the caller does that part; this is the tail they all have.
 */
async function saveContent(page, disk, { order, subtype = '', version = '', requirements = '' }) {
  await page.fill('#order', String(order));
  await page.fill('#subtype', subtype);
  await page.fill('#version', version);
  await page.fill('#requirements', requirements);
  await page.getByRole('button', { name: 'Save' }).click();

  await expect(page).toHaveURL(new RegExp(`/admin/menus/disks/${disk.id}/edit$`));
}

test.describe('Admin menu sets', () => {
  // Four levels of hierarchy means even the small round-trips below are five or
  // six full page loads each, and they run beside the lifecycle test at the
  // bottom of this file. Against the PHP dev server that is close enough to the
  // 30s default to lose the race - and with retries off, losing it once is a
  // red build. The lifecycle test sets its own budget on top of this.
  test.describe.configure({ timeout: 90000 });

  test('creates and deletes a menu set', async ({ page }) => {
    const set = await createMenuSet(page);

    await deleteMenuSet(page, set);
  });

  test('creates and deletes a menu', async ({ page }) => {
    const set = await createMenuSet(page);
    const menu = await createMenu(page, set);

    await deleteMenu(page, menu);
    await deleteMenuSet(page, set);
  });

  test('creates and deletes a menu disk', async ({ page }) => {
    const set = await createMenuSet(page);
    const menu = await createMenu(page, set);
    // Conditions come from a migration rather than from the seeder, so there is
    // no form to create one with - createMenuDisk selects a seeded row without
    // writing to it, which is the one thing these specs may still do.
    const disk = await createMenuDisk(page, menu, { part: 'Z' });

    // Bottom up: a menu still holding a disk, or a set still holding a menu,
    // takes its children with it and would leave this passing on a delete that
    // did more than it said.
    await deleteMenuDisk(page, disk);
    await deleteMenu(page, menu);
    await deleteMenuSet(page, set);
  });

  /**
   * A menu set, end to end.
   *
   * tests/Feature/Admin/Menus/ drives these controllers by posting to them, so
   * it passes whether or not any of the browser wiring still works: three
   * autocompletes across the disk and content forms, a radio pair that reveals
   * one half of the content form through a Bootstrap collapse, and two plain
   * file uploads whose results only show up on the public page.
   *
   * So each step is checked where it lands - in the admin form for what the
   * admin is for, and on /menusets/{set} for what the data is for. The public
   * page is the point of the section: a disk card is assembled out of the menu,
   * the disk, its condition, its donor, its dump and every one of its contents.
   */
  test('builds a full menu set with disks and contents, and checks it on the site', async ({ page }) => {
    test.setTimeout(240000);
    acceptConfirms(page);

    // Everything this touches is its own, parents included. A content pointing
    // at FIXTURE.menuSoftware would surface this spec's disk on
    // /menusets/software/1, and one pointing at FIXTURE.game would surface it
    // on /games/xenon-2-megablast - both pages a read spec opens. The crew and
    // the conditions below are reference data: selected, never written to.
    const set = await createMenuSet(page);
    const individual = await createIndividual(page);
    const gameOnMenu = await createGame(page);
    const docGame = await createGame(page);
    const software = await createMenuSoftware(page);

    const renamed = uniqueName('Menu Set');
    const notes = uniqueName('Notes');
    const scrolltext = uniqueName('Scrolltext');

    let testPassed = false;
    let menu = null;
    let otherMenu = null;
    let disk = null;
    let missingDisk = null;
    let otherDisk = null;

    try {
      // 1. A set with no disk at all. It is not on /menusets yet:
      //    MenuSetController::index() joins through menus and disks, so a set
      //    only appears once it has one.
      await page.goto('/menusets');
      await expect(page.getByRole('link', { name: set.name })).toHaveCount(0);

      let card = await openPublicSet(page, set);
      await expect(card).toContainText(`${set.name} contains 0 menus and 0 disks.`);
      await expect(card).toContainText('All known disks are available, our set is 100% complete!');

      // 2. Update the set: a new name, and the menus sorted the other way.
      //
      //    A set starts out ascending, so the select has to be read before it is
      //    changed as well as after: the option it marks selected is the only
      //    thing standing between a saved direction and a later save silently
      //    reverting it. That is exactly what used to happen - the blade
      //    compared the stored 'asc'/'desc' against 'ascending'/'descending' and
      //    matched neither, so every saved set rendered as Ascending.
      await page.goto(`/admin/menus/sets/${set.id}/edit`);
      await expect(page.locator('#sort')).toHaveValue('asc');

      await page.fill('#name', renamed);
      await page.selectOption('#sort', 'desc');
      await page.selectOption('select[name="crews[]"]', String(FIXTURE.crew.id));
      await page.getByRole('button', { name: 'Save' }).click();

      await expect(page).toHaveURL(new RegExp(`/admin/menus/sets/${set.id}/edit$`));
      await expect(page.locator('#name')).toHaveValue(renamed);
      await expect(page.locator('#sort')).toHaveValue('desc');
      set.name = renamed;

      //    And it survives a save that does not touch it, which is the half a
      //    round-trip assertion on its own would miss.
      await page.getByRole('button', { name: 'Save' }).click();
      await expect(page.locator('#sort')).toHaveValue('desc');

      card = await openPublicSet(page, set);
      await expect(card).toContainText(`By: ${FIXTURE.crew.name}`);

      // 3. A menu. It has no name of its own - Menu::getLabelAttribute()
      //    assembles one out of the number and the version, and that label is
      //    what both the admin and the public page show.
      menu = await createMenu(page, set, { number: '1', version: '1.0', date: '1989-02-03' });
      expect(menu.label).toBe('#1 v1.0');

      await page.goto(`/admin/menus/sets/${set.id}/edit`);
      await expect(page.getByRole('link', { name: menu.label })).toBeVisible();
      await expect(page.getByRole('cell', { name: 'February 3, 1989' })).toBeVisible();

      // 4. A disk, through every field the form has - including the individuals
      //    autocomplete, whose companion hidden input is what the controller
      //    reads. pickAutocomplete() asserts the id arrived there.
      disk = await createMenuDisk(page, menu, { part: 'A' });

      await page.goto(`/admin/menus/disks/${disk.id}/edit`);
      await page.fill('#notes', notes);
      await page.fill('#scrolltext', scrolltext);
      await pickAutocomplete(page, 'individual_name', individual.name);
      await page.getByRole('button', { name: 'Save' }).click();

      await expect(page).toHaveURL(new RegExp(`/admin/menus/disks/${disk.id}/edit$`));
      await expect(page.locator('#notes')).toHaveValue(notes);
      await expect(page.locator('#scrolltext')).toHaveValue(scrolltext);

      card = await openPublicSet(page, set);
      await expect(card).toContainText(`${set.name} contains 1 menu and 1 disk.`);
      await expect(card).toContainText('1 scrolltext');
      await expect(card.getByRole('link', { name: 'read them all in an eBook' })).toBeVisible();

      let diskCard = publicDisk(page, disk);
      await expect(diskCard.getByRole('heading', { level: 3 }))
        .toContainText(`${set.name} ${menu.label}${disk.part}`);
      await expect(diskCard).toContainText('February 3, 1989');
      await expect(diskCard).toContainText(`Notes: ${notes}`);
      await expect(diskCard).toContainText(`Condition: ${FIXTURE.menuCondition.name.toLowerCase()}`);
      await expect(diskCard).toContainText(`Donated by: ${individual.name}`);
      await expect(diskCard).toContainText('No download available');
      await expect(diskCard.locator('img[alt="No screenshot for this disk"]')).toBeVisible();

      // The scrolltext ships collapsed, behind a Bootstrap toggle in the header.
      await diskCard.locator(`[data-bs-target="#scrolltext-${disk.id}"]`).first().click();
      await expect(diskCard.locator(`#scrolltext-${disk.id}`)).toBeVisible();
      await expect(diskCard.locator(`#scrolltext-${disk.id}`)).toContainText(scrolltext);

      // 5. A screenshot. A plain multipart form, not a Filepond pond.
      await page.goto(`/admin/menus/disks/${disk.id}/edit`);
      await page.locator('input[name="screenshot"]').setInputFiles({
        name: 'disk.png',
        mimeType: 'image/png',
        buffer: PNG,
      });
      await page.locator('form[action$="/screenshot"]').getByRole('button', { name: 'Add' }).click();

      await expect(page).toHaveURL(new RegExp(`/admin/menus/disks/${disk.id}/edit$`));
      await expect(page.getByRole('button', { name: 'Remove screenshot' })).toBeVisible();

      diskCard = await openPublicDisk(page, set, disk);
      const screenshot = diskCard.locator('img[alt="Screenshot of disk"]');
      await expect(screenshot).toHaveAttribute('src', /\/storage\/images\/menu_screenshots\/\d+\.png$/);

      const screenshotPath = new URL(await screenshot.getAttribute('src')).pathname;
      await expectResourceLoads(await page.request.get(screenshotPath), screenshotPath, { magic: 'PNG' });

      // 6. A dump. storeDump() re-packs whatever it is given into a zip of its
      //    own, so the file that comes back out is not the one that went in -
      //    which is why fetching it is worth doing rather than only asserting
      //    that a link appeared.
      await page.goto(`/admin/menus/disks/${disk.id}/edit`);
      await page.locator('input[name="dump"]').setInputFiles({
        name: 'disk.st',
        mimeType: 'application/octet-stream',
        buffer: Buffer.from('Atari ST dummy dump content for testing e2e'),
      });
      await page.locator('form[action$="/dump"]').getByRole('button', { name: 'Add' }).click();

      await expect(page).toHaveURL(new RegExp(`/admin/menus/disks/${disk.id}/edit$`));
      const dumpCard = page.locator('.card').filter({ has: page.getByRole('heading', { name: 'Dump' }) });
      await expect(dumpCard).toContainText('Format: ST');
      await expect(dumpCard).toContainText('Size: 43 bytes');
      await expect(dumpCard).toContainText(`Uploaded by: ${FIXTURE.admin.userid}`);

      diskCard = await openPublicDisk(page, set, disk);
      await expect(diskCard).not.toContainText('No download available');
      const download = diskCard.locator('a[download]');
      await expect(download).toHaveAttribute('download', `${set.name} ${menu.label}${disk.part}.zip`);

      const zipPath = new URL(await download.getAttribute('href')).pathname;
      expect(zipPath).toMatch(/\/storage\/zips\/menus\/\d+\.zip$/);
      await expectResourceLoads(await page.request.get(zipPath), zipPath, { magic: 'PK' });

      // 7. The four shapes a disk content comes in, one per branch of
      //    menus/partial_menudisk_content.blade.php. Each drives the widget the
      //    form hosts rather than the hidden input behind it.

      // 7a. A game that is on the menu: a release is created for it on save.
      await page.goto(`/admin/menus/disks/${disk.id}/content/create?type=release`);
      await pickAutocomplete(page, 'game_name', gameOnMenu.name);
      await saveContent(page, disk, { order: 1 });

      diskCard = await openPublicDisk(page, set, disk);
      await expect(diskCard.getByRole('link', { name: gameOnMenu.name }))
        .toHaveAttribute('href', new RegExp(`/games/${gameOnMenu.slug}$`));

      // 7b. A piece of software, with a version beside its name.
      await page.goto(`/admin/menus/disks/${disk.id}/content/create?type=software`);
      await pickAutocomplete(page, 'software_name', software.name);
      await saveContent(page, disk, { order: 2, version: '1.2' });

      diskCard = await openPublicDisk(page, set, disk);
      const softwareLink = diskCard.getByRole('link', { name: software.name });
      await expect(softwareLink).toHaveAttribute('href', /\/menusets\/software\/\d+$/);
      const softwarePath = new URL(await softwareLink.getAttribute('href')).pathname;

      // 7c. A doc for a game that is *not* on the menu, which is what the
      //     standalone form is for. Its subtype is required by the controller.
      await page.goto(`/admin/menus/disks/${disk.id}/content/create?type=game`);
      await pickAutocomplete(page, 'game_name', docGame.name);
      await saveContent(page, disk, { order: 3, subtype: 'doc', requirements: 'TOS 1.62' });

      diskCard = await openPublicDisk(page, set, disk);
      await expect(diskCard.getByRole('link', { name: docGame.name }))
        .toHaveAttribute('href', new RegExp(`/games/${docGame.slug}$`));

      // 7d. An extra for a release already on the menu. The radio reveals the
      //     select through a Bootstrap collapse wired up in
      //     resources/js/admin/menus/menus.js - the one place in this section
      //     where the form only works if its JavaScript ran.
      await page.goto(`/admin/menus/disks/${disk.id}/content/create?type=release`);
      await page.check('#use-release');
      await expect(page.locator('#action-use-release')).toBeVisible();
      await page.selectOption('#release', { label: gameOnMenu.name });
      await saveContent(page, disk, { order: 4, subtype: 'trainer' });

      // Read in `order`, not in the order they were entered - which they were,
      // here, so what this really pins down is that the public partial sorts at
      // all: it splits the list into two columns and would silently reorder it.
      diskCard = await openPublicDisk(page, set, disk);
      await expect(diskCard.locator('li')).toContainText([
        gameOnMenu.name,
        `${software.name} 1.2`,
        `${docGame.name} [doc] (TOS 1.62)`,
        `${gameOnMenu.name} [trainer]`,
      ]);

      // 8. The same links read from the other end, which is most of why the
      //    content is entered at all.
      await page.goto(softwarePath);
      await expect(page.getByRole('heading', { level: 2, name: software.name })).toBeVisible();
      await expect(page.locator('.card').filter({ hasText: 'is present on 1 menudisk' })).toBeVisible();
      await expect(publicDisk(page, disk)).toBeVisible();

      await page.goto(`/games/${gameOnMenu.slug}`);
      await expect(page.getByRole('heading', { name: 'In 1 Menu' })).toBeVisible();
      await expect(page.getByRole('link', { name: `${set.name} ${menu.label}${disk.part}` }))
        .toBeVisible();

      await page.goto(`/games/${docGame.slug}`);
      await expect(page.getByRole('heading', { name: 'In 1 Menu' })).toBeVisible();

      // 9. A second disk, in a condition that is not Intact. Only Intact counts
      //    as available, so the set stops being complete and the page has a
      //    percentage to work out.
      missingDisk = await createMenuDisk(page, menu, {
        part: 'B',
        condition: FIXTURE.menuConditionMissing.id,
      });

      card = await openPublicSet(page, set);
      await expect(card).toContainText('1 disk missing or damaged (50.0% complete).');
      await expect(card).not.toContainText('100% complete');

      // 10. A second menu, and with it the sort direction set in step 2: menu #2
      //     comes before menu #1, and within a menu the disks go by part.
      otherMenu = await createMenu(page, set, { number: '2' });
      otherDisk = await createMenuDisk(page, otherMenu, { part: 'A' });

      card = await openPublicSet(page, set);
      await expect(card).toContainText(`${set.name} contains 2 menus and 3 disks.`);

      const cards = page.locator('[id^="menudisk-"]');
      await expect(cards).toHaveCount(3);
      await expect
        .poll(() => cards.evaluateAll((elements) => elements.map((element) => element.id)))
        .toEqual([`menudisk-${otherDisk.id}`, `menudisk-${disk.id}`, `menudisk-${missingDisk.id}`]);

      // 11. Update a content that already has values, rather than filling a
      //     blank one. Moving it past the others is the visible half.
      await page.goto(`/admin/menus/disks/${disk.id}/edit`);
      const softwareId = await contentId(page, 2, software.name);

      await page.goto(`/admin/menus/disks/${disk.id}/content/${softwareId}/edit`);
      await page.fill('#order', '5');
      await page.fill('#version', '2.0');
      await page.getByRole('button', { name: 'Save' }).click();

      await expect(page).toHaveURL(new RegExp(`/admin/menus/disks/${disk.id}/edit$`));

      diskCard = await openPublicDisk(page, set, disk);
      await expect(diskCard.locator('li')).toContainText([
        gameOnMenu.name,
        `${docGame.name} [doc] (TOS 1.62)`,
        `${gameOnMenu.name} [trainer]`,
        `${software.name} 2.0`,
      ]);

      // 12. Deleting a content. The standalone doc goes on its own …
      await page.goto(`/admin/menus/disks/${disk.id}/edit`);
      await deleteByAction(page, `/content/${await contentId(page, 3, docGame.name)}`);

      diskCard = await openPublicDisk(page, set, disk);
      await expect(diskCard.locator('li')).toHaveCount(3);
      await expect(diskCard).not.toContainText('[doc]');

      await page.goto(`/games/${docGame.slug}`);
      await expect(page.getByRole('heading', { name: 'In 1 Menu' })).toHaveCount(0);

      // … and deleting the release takes the trainer with it, because that
      // extra hangs off the release rather than off the disk. Only a test that
      // owns both rows can see that: MenuDisksContentController::destroy()
      // deletes the release when the content has no subtype, and with it every
      // other content pointing at that release.
      await page.goto(`/admin/menus/disks/${disk.id}/edit`);
      await deleteByAction(page, `/content/${await contentId(page, 1, gameOnMenu.name)}`);

      diskCard = await openPublicDisk(page, set, disk);
      await expect(diskCard.locator('li')).toHaveCount(1);
      await expect(diskCard).not.toContainText(gameOnMenu.name);
      await expect(diskCard).toContainText(`${software.name} 2.0`);

      // 13. Removing the dump and the screenshot puts the card back to the
      //     placeholders it started with.
      await page.goto(`/admin/menus/disks/${disk.id}/edit`);
      await page.getByRole('button', { name: 'Remove dump' }).click();
      await page.waitForLoadState('domcontentloaded');
      await page.getByRole('button', { name: 'Remove screenshot' }).click();
      await page.waitForLoadState('domcontentloaded');
      await expect(page.getByRole('button', { name: 'Remove dump' })).toHaveCount(0);
      await expect(page.getByRole('button', { name: 'Remove screenshot' })).toHaveCount(0);

      diskCard = await openPublicDisk(page, set, disk);
      await expect(diskCard).toContainText('No download available');
      await expect(diskCard.locator('img[alt="No screenshot for this disk"]')).toBeVisible();

      testPassed = true;
    } finally {
      // Best-effort: a failure above should report itself, not be buried under
      // the teardown failing on a record that was never created.
      const cleanUp = async (label, remove) => {
        try {
          await remove();
        } catch (error) {
          if (testPassed) {
            throw error;
          }
          console.warn(`cleanup: could not delete the ${label}: ${error.message}`);
        }
      };

      // The uploads first, and through their own buttons. Deleting a disk drops
      // its screenshot and dump *rows*, but only destroyScreenshot() and
      // destroyDump() unlink what was written to storage/app/public - so a
      // failure between step 5 and step 13 would otherwise leave a stray png
      // and zip behind on every run. Step 13 has already done this on the happy
      // path; these are no-ops then.
      if (disk) {
        await cleanUp('uploads', async () => {
          await page.goto(`/admin/menus/disks/${disk.id}/edit`);

          for (const name of ['Remove dump', 'Remove screenshot']) {
            const button = page.getByRole('button', { name });

            while (await button.count() > 0) {
              await button.first().click();
              await page.waitForLoadState('domcontentloaded');
            }
          }
        });
      }

      // Then bottom up, and the order is load-bearing twice over. A set whose
      // menus are still there has its delete button *disabled* rather than
      // absent, so deleteByAction would time out on the actionability check.
      // And the software and the games cannot go until the contents pointing at
      // them have - which a disk takes with it, releases included.
      for (const [label, target] of [['disk', disk], ['missing disk', missingDisk], ['other disk', otherDisk]]) {
        if (target) {
          await cleanUp(label, () => deleteMenuDisk(page, target));
        }
      }
      for (const [label, target] of [['menu', menu], ['other menu', otherMenu]]) {
        if (target) {
          await cleanUp(label, () => deleteMenu(page, target));
        }
      }
      await cleanUp('set', () => deleteMenuSet(page, set));
      await cleanUp('software', () => deleteMenuSoftware(page, software));
      await cleanUp('game', () => deleteGame(page, gameOnMenu));
      await cleanUp('doc game', () => deleteGame(page, docGame));
      await cleanUp('individual', () => deleteIndividual(page, individual));
    }
  });

  // TODO: running a spreadsheet import - the one flow in this section with real
  // logic behind it, and the one that most deserves a test of its own.
});
