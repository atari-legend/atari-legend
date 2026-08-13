import { test, expect } from '../support/test.js';
import { FIXTURE } from '../support/fixture.js';
import { uniqueName, deleteByAction } from '../support/write.js';

// A set holds menus, a menu holds disks. Only the set has an index of its own;
// the two below it are cards on their parent, created from a link that carries
// the parent's id in the query string.

test.describe('Admin menu sets', () => {
  test('creates and deletes a menu set', async ({ page }) => {
    const name = uniqueName('Menu Set');

    await page.goto('/admin/menus/sets/create');
    await page.fill('#name', name);
    // A set has to belong to at least one crew - the validation rules make
    // `crews` required, and the field is a multi-select rather than an
    // autocomplete.
    await page.selectOption('select[name="crews[]"]', String(FIXTURE.crew.id));
    await page.getByRole('button', { name: 'Save' }).click();

    await expect(page).toHaveURL(/\/admin\/menus\/sets\/\d+\/edit$/);
    const setId = page.url().split('/').at(-2);

    // The sets index is a plain table rather than a Livewire one, so there is
    // no search box to narrow it down with.
    await page.goto('/admin/menus/sets');
    await deleteByAction(page, `/sets/${setId}`);
  });

  test('creates and deletes a menu', async ({ page }) => {
    const set = `/admin/menus/sets/${FIXTURE.menuSet.id}/edit`;

    await page.goto(`/admin/menus/menus/create?set=${FIXTURE.menuSet.id}`);
    // A menu is identified by its number within the set, so pick one no
    // fixture uses rather than a generated name.
    await page.fill('#number', '9999');
    await page.getByRole('button', { name: 'Save' }).click();

    await expect(page).toHaveURL(/\/admin\/menus\/menus\/\d+\/edit$/);
    const menuId = page.url().split('/').at(-2);

    await page.goto(set);
    await deleteByAction(page, `/menus/${menuId}`);
  });

  test('creates and deletes a menu disk', async ({ page }) => {
    const menu = `/admin/menus/menus/${FIXTURE.menu.id}/edit`;

    await page.goto(`/admin/menus/disks/create?menu=${FIXTURE.menu.id}`);
    await page.fill('#part', 'Z');
    await page.selectOption('#condition', String(FIXTURE.menuCondition.id));
    await page.getByRole('button', { name: 'Save' }).click();

    await expect(page).toHaveURL(/\/admin\/menus\/disks\/\d+\/edit$/);
    const diskId = page.url().split('/').at(-2);

    await page.goto(menu);
    await deleteByAction(page, `/disks/${diskId}`);
  });

  // TODO: disk contents, screenshot and dump uploads, and running a
  // spreadsheet import - the one flow in this section with real logic behind
  // it, and the one that most deserves a test of its own.
});
