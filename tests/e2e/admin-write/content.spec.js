import { test, expect } from '../support/test.js';
import { FIXTURE } from '../support/fixture.js';
import {
  uniqueName, deleteRow, pickAutocomplete, fillEditor,
  createGame, deleteGame, createIndividual, deleteIndividual,
} from '../support/write.js';

// News, reviews, interviews and articles, created and deleted through their
// real forms.
//
// What these add over tests/Feature/Admin, which already posts at all four
// controllers and asserts the changelog rows: the form itself. Three of these
// forms carry a hidden id that only the autocomplete's onSelection fills in,
// and all four post their body through SCEditor rather than from the textarea
// in the markup. A PHPUnit test supplies both directly and passes either way.
//
// So: assert that the round-trip works, not what the controller wrote. The
// validation rules and the changelog rows belong to the PHPUnit suite.

test.describe('Admin news', () => {
  test('creates and deletes a news item', async ({ page }) => {
    const headline = uniqueName('News');

    await page.goto('/admin/news/news/create');
    await page.fill('#headline', headline);
    await fillEditor(page, 'text', 'Written by the e2e suite.');
    // The author and date are prefilled with the signed-in admin and today.
    // Picking someone else covers the one autocomplete mode the other forms
    // never exercise: replacing a value the field arrived with, name and
    // hidden id both. The user is only referenced, never written to.
    await pickAutocomplete(page, 'author_name', FIXTURE.user.userid);
    await page.getByRole('button', { name: 'Save' }).click();

    await expect(page).toHaveURL(/\/admin\/news\/news$/);
    await expect(page.getByText(headline)).toBeVisible();

    await deleteRow(page, headline);
  });
});

test.describe('Admin reviews', () => {
  test('creates and deletes a review', async ({ page }) => {
    const name = uniqueName('Review');
    // Its own game rather than the seeded one: a review is listed under the
    // game it is about, on /reviews and on the game's own page, so reviewing
    // the fixture would put a transient row in front of the public specs.
    const game = await createGame(page);

    await page.goto('/admin/reviews/reviews/create');
    // A review is always about a game, and the game id arrives via the
    // autocomplete rather than as a select.
    await pickAutocomplete(page, 'game_name', game.name);
    // A review has no title of its own - the table lists it under its game -
    // so the unique name goes in the body, which the table also searches.
    await fillEditor(page, 'text', name);
    await page.getByRole('button', { name: /Save & Close/ }).click();

    await expect(page).toHaveURL(/\/admin\/reviews\/reviews$/);

    await deleteRow(page, name);

    // Only now: a game with a review on it is not deletable.
    await deleteGame(page, game);
  });
});

test.describe('Admin interviews', () => {
  test('creates and deletes an interview', async ({ page }) => {
    const name = uniqueName('Interview');
    // Its own individual, and this one is not optional. An interview is
    // *titled* after its individual, and public/interviews.spec.js asserts on
    // a heading carrying the seeded individual's name with no .first() - so an
    // interview with the fixture would give that locator two matches and fail
    // it on strict mode, from another project entirely.
    const individual = await createIndividual(page);

    await page.goto('/admin/interviews/interviews/create');
    // An interview is titled after the person it is with, so like a review it
    // is identified in the table by its body rather than by a name of its own.
    await pickAutocomplete(page, 'individual_name', individual.name);
    await fillEditor(page, 'text', name);
    await page.getByRole('button', { name: /Save & Close/ }).click();

    await expect(page).toHaveURL(/\/admin\/interviews\/interviews$/);

    await deleteRow(page, name);
    await deleteIndividual(page, individual);
  });
});

test.describe('Admin articles', () => {
  test('creates and deletes an article', async ({ page }) => {
    const title = uniqueName('Article');

    await page.goto('/admin/articles/articles/create');
    await page.fill('#title', title);
    // Both the intro and the body are editors here, and both are required.
    await fillEditor(page, 'intro', 'Written by the e2e suite.');
    await fillEditor(page, 'text', 'Written by the e2e suite.');
    await page.getByRole('button', { name: 'Save' }).click();

    await expect(page).toHaveURL(/\/admin\/articles\/articles$/);
    await expect(page.getByText(title)).toBeVisible();

    await deleteRow(page, title);
  });
});

// TODO: the image upload flows on Articles and Interviews, which go through
// Filepond and so need a real file and a chunked POST.
