import { test, expect } from '../support/test.js';
import { FIXTURE } from '../support/fixture.js';
import {
  createMenuSet, deleteMenuSet, createMenu, deleteMenu, deleteByAction,
} from '../support/write.js';

// A set holds menus, a menu holds disks. Only the set has an index of its own;
// the two below it are cards on their parent, created from a link that carries
// the parent's id in the query string.
//
// Each test builds its own chain down from a set rather than hanging a menu or
// a disk off the seeded one - see the factories in support/write.js for why.

test.describe('Admin menu sets', () => {
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

    await page.goto(`/admin/menus/disks/create?menu=${menu.id}`);
    await page.fill('#part', 'Z');
    // Conditions come from a migration rather than from the seeder, so there
    // is no form to create one with - this selects a seeded row without
    // writing to it, which is the one thing these specs may still do.
    await page.selectOption('#condition', String(FIXTURE.menuCondition.id));
    await page.getByRole('button', { name: 'Save' }).click();

    await expect(page).toHaveURL(/\/admin\/menus\/disks\/\d+\/edit$/);
    const diskId = page.url().split('/').at(-2);

    // Bottom up: a menu still holding a disk, or a set still holding a menu,
    // takes its children with it and would leave this passing on a delete that
    // did more than it said.
    await page.goto(`/admin/menus/menus/${menu.id}/edit`);
    await deleteByAction(page, `/disks/${diskId}`);

    await deleteMenu(page, menu);
    await deleteMenuSet(page, set);
  });

  // TODO: disk contents, screenshot and dump uploads, and running a
  // spreadsheet import - the one flow in this section with real logic behind
  // it, and the one that most deserves a test of its own.
});
