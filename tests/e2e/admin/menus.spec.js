import { test, expect } from '../support/test.js';
import { FIXTURE } from '../support/fixture.js';
import { expectPageRenders, expectResourceLoads } from '../support/assertions.js';

test.describe('Admin menus', () => {
  test('lists menu sets', async ({ page }) => {
    const response = await page.goto('/admin/menus/sets');

    await expectPageRenders(page, response, '/admin/menus/sets');
    await expect(page.getByText(FIXTURE.menuSet.name).first()).toBeVisible();
  });

  // Menus and disks have no index of their own - they are reached from the
  // set they belong to, which is why routes/admin.php prunes those actions.
  const forms = [
    { name: 'a menu set', path: `/admin/menus/sets/${FIXTURE.menuSet.id}/edit` },
    { name: 'a menu', path: `/admin/menus/menus/${FIXTURE.menu.id}/edit` },
    { name: 'a menu disk', path: `/admin/menus/disks/${FIXTURE.menuDisk.id}/edit` },
    { name: 'a disk content entry', path: `/admin/menus/disks/${FIXTURE.menuDisk.id}/content/${FIXTURE.menuDisk.contentId}/edit` },
  ];

  for (const form of forms) {
    test(`opens the edit form for ${form.name}`, async ({ page }) => {
      await expectPageRenders(page, await page.goto(form.path), form.path);
    });
  }

  // Everything below a set is created from inside its parent, so these create
  // routes carry state in the query string: which menu the disk belongs to,
  // which of the three content forms to render.
  //
  // The three content types are three separate partials
  // (content/create_{release,game,software}.blade.php), so each is listed.
  const createForms = [
    { name: 'a menu set', path: '/admin/menus/sets/create' },
    { name: 'a menu', path: `/admin/menus/menus/create?set=${FIXTURE.menuSet.id}` },
    { name: 'a menu disk', path: `/admin/menus/disks/create?menu=${FIXTURE.menu.id}` },
    ...['release', 'game', 'software'].map((type) => ({
      name: `a ${type} content entry`,
      path: `/admin/menus/disks/${FIXTURE.menuDisk.id}/content/create?type=${type}`,
    })),
  ];

  for (const form of createForms) {
    test(`opens the create form for ${form.name}`, async ({ page }) => {
      // expectPageRenders compares against the path only, query string aside.
      const path = form.path.split('?')[0];

      await expectPageRenders(page, await page.goto(form.path), path);
    });
  }

  // The parent only ever arrives in the query string, so a bare or stale URL
  // has to answer 404 rather than an error page. Only a hand-edited URL or a
  // stale bookmark gets here - every link in the admin supplies the id.
  const parentlessCreateForms = [
    { name: 'a menu without its set', path: '/admin/menus/menus/create' },
    { name: 'a menu whose set is gone', path: '/admin/menus/menus/create?set=999999' },
    { name: 'a disk without its menu', path: '/admin/menus/disks/create' },
    { name: 'a disk whose menu is gone', path: '/admin/menus/disks/create?menu=999999' },
  ];

  for (const form of parentlessCreateForms) {
    test(`404s on the create form for ${form.name}`, async ({ page }) => {
      const response = await page.goto(form.path);

      expect(response?.status()).toBe(404);
    });
  }

  test('opens the import screen for a menu set', async ({ page }) => {
    const path = `/admin/menus/sets/${FIXTURE.menuSet.id}/import`;

    await expectPageRenders(page, await page.goto(path), path);
  });

  test('serves the import spreadsheet template', async ({ page }) => {
    const path = `/admin/menus/sets/${FIXTURE.menuSet.id}/import/template`;

    // A .xlsx is a zip, hence the PK magic - the same check the EPUB export
    // uses in public/menus.spec.js.
    await expectResourceLoads(await page.request.get(path), path, { magic: 'PK' });
  });

  // TODO: running an import, and the disk screenshot/dump uploads.
});

test.describe('Admin menu reference data', () => {
  const sections = [
    { name: 'conditions', index: '/admin/menus/conditions', edit: `/admin/menus/conditions/${FIXTURE.menuCondition.id}/edit` },
    { name: 'content types', index: '/admin/menus/content-types', edit: `/admin/menus/content-types/${FIXTURE.menuContentType.id}/edit` },
    { name: 'software', index: '/admin/menus/software', edit: `/admin/menus/software/${FIXTURE.menuSoftware.id}/edit` },
    { name: 'crews', index: '/admin/menus/crews', edit: `/admin/menus/crews/${FIXTURE.crew.id}/edit` },
  ];

  for (const section of sections) {
    const singular = section.name.replace(/s$/, '');

    test(`lists ${section.name}`, async ({ page }) => {
      await expectPageRenders(page, await page.goto(section.index), section.index);
    });

    test(`opens the edit form for a ${singular}`, async ({ page }) => {
      await expectPageRenders(page, await page.goto(section.edit), section.edit);
    });

    test(`opens the create form for a ${singular}`, async ({ page }) => {
      const path = `${section.index}/create`;

      await expectPageRenders(page, await page.goto(path), path);
    });
  }

  // TODO: the crew relationships - adding members, sub-crews and logos - which
  // are the only part of this section with more than a name field.
});
