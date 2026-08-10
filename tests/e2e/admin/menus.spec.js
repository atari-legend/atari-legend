import { test, expect } from '../support/test.js';
import { FIXTURE } from '../support/fixture.js';
import { expectPageRenders } from '../support/assertions.js';

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

  test('opens the import screen for a menu set', async ({ page }) => {
    const path = `/admin/menus/sets/${FIXTURE.menuSet.id}/import`;

    await expectPageRenders(page, await page.goto(path), path);
  });

  // TODO: downloading the import spreadsheet template (needs the zip
  // extension), running an import, and the disk screenshot/dump uploads.
});

test.describe('Admin menu reference data', () => {
  const sections = [
    { name: 'conditions', index: '/admin/menus/conditions', edit: `/admin/menus/conditions/${FIXTURE.menuCondition.id}/edit` },
    { name: 'content types', index: '/admin/menus/content-types', edit: `/admin/menus/content-types/${FIXTURE.menuContentType.id}/edit` },
    { name: 'software', index: '/admin/menus/software', edit: `/admin/menus/software/${FIXTURE.menuSoftware.id}/edit` },
    { name: 'crews', index: '/admin/menus/crews', edit: `/admin/menus/crews/${FIXTURE.crew.id}/edit` },
  ];

  for (const section of sections) {
    test(`lists ${section.name}`, async ({ page }) => {
      await expectPageRenders(page, await page.goto(section.index), section.index);
    });

    test(`opens the edit form for a ${section.name.replace(/s$/, '')}`, async ({ page }) => {
      await expectPageRenders(page, await page.goto(section.edit), section.edit);
    });
  }

  // TODO: the crew relationships - adding members, sub-crews and logos - which
  // are the only part of this section with more than a name field.
});
