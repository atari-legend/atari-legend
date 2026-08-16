import { test, expect } from '../support/test.js';
import { FIXTURE } from '../support/fixture.js';
import {
  uniqueName, deleteRow, findRow, deleteByAction, acceptConfirms,
  pickAutocomplete, fillEditor, PNG,
  createGame, deleteGame, createIndividual, deleteIndividual,
  createArticle, deleteArticle, createInterview, deleteInterview,
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

/**
 * The "Images" card that the article and the interview edit screens both host.
 *
 * The two blades are the same markup with a different route in the action and a
 * different folder in the src, so the three helpers below take the folder and
 * find everything else by its shape. Anything more specific would be two copies
 * of the same test.
 *
 * Worth driving through the browser at all, rather than posting at the three
 * routes the way tests/Feature/Admin does: the description is not part of the
 * upload. It belongs to the *pivot* row, is posted by a second form that lives
 * outside the table and is reached from each textarea by `form="update-images"`
 * — so a textarea whose form attribute is wrong submits nothing, on a screen
 * that looks identical.
 */
async function addImage(page, folder) {
  await page.locator('input[name="image[]"]').setInputFiles({
    name: 'image.png',
    mimeType: 'image/png',
    buffer: PNG,
  });
  await page.getByRole('button', { name: /Add image/ }).click();
  await page.waitForLoadState('domcontentloaded');

  // The image itself has to arrive: it is served out of storage/public through
  // the storage:link symlink, and a broken link is invisible to everything but
  // the subresource guard in support/test.js.
  await expect(page.locator(`img[src*="${folder}"]`)).toBeVisible();

  // The screenshot id appears nowhere else on the page - the textarea is keyed
  // on the *pivot* id, not on this one - so it is read off the delete form,
  // which is the only route that carries it.
  const destroy = page.locator('form[action*="/image/"]');
  await expect(destroy).toHaveCount(1);
  const action = await destroy.getAttribute('action');

  return { id: action.split('/').at(-1) };
}

/**
 * Write a description on the one image on screen, and check it came back.
 *
 * Called twice per test on purpose: the controller creates the comment row on
 * the first save and updates it on the second, and those are two different
 * branches of updateImage().
 */
async function describeImage(page, description) {
  await page.locator('textarea[name^="description-"]').fill(description);
  await page.getByRole('button', { name: 'Save changes', exact: true }).click();
  await page.waitForLoadState('domcontentloaded');

  await expect(page.locator('textarea[name^="description-"]')).toHaveValue(description);
}

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

  test('adds and removes the image on a news item', async ({ page }) => {
    // Six full page loads and an upload, where every other test in this file
    // is one form - the same reason the games and menus specs raise it.
    test.setTimeout(90000);

    const headline = uniqueName('News');

    // A news item carries one image rather than a gallery, and there is no
    // separate upload form: the file input is part of the edit form, and
    // NewsController::addOrUpdateImage() creates the news_image row on the way
    // through. So the round trip is create, edit, delete-the-image.
    await page.goto('/admin/news/news/create');
    await page.fill('#headline', headline);
    await fillEditor(page, 'text', 'Written by the e2e suite.');
    await page.getByRole('button', { name: 'Save' }).click();

    await expect(page).toHaveURL(/\/admin\/news\/news$/);

    const row = await findRow(page, headline);
    await row.getByRole('link', { name: headline }).click();
    await expect(page).toHaveURL(/\/admin\/news\/news\/\d+\/edit$/);
    const edit = page.url();

    // Nothing yet, so the card shows the placeholder and offers no bin.
    await expect(page.locator('img[src*="news_images"]')).toHaveCount(0);

    await page.locator('input[name="image"]').setInputFiles({
      name: 'news.png',
      mimeType: 'image/png',
      buffer: PNG,
    });
    await page.getByRole('button', { name: 'Save' }).click();

    // Back on the index, where the table renders the thumbnail it just gained.
    await expect(page).toHaveURL(/\/admin\/news\/news$/);
    const listed = await findRow(page, headline);
    await expect(listed.locator('img[src*="news_images"]')).toBeVisible();

    await page.goto(edit);
    await expect(page.locator('img[src*="news_images"]')).toBeVisible();

    // The bin next to the image is the only delete button in the admin with no
    // accessible name at all - no text, no title, just an icon - so it cannot
    // be reached by role. It is also the only one whose confirm() is in an
    // onclick rather than an onsubmit, submitting a separate form by id.
    acceptConfirms(page);
    await page.locator('button[onclick*="delete-image"]').click();
    await page.waitForLoadState('domcontentloaded');

    await expect(page).toHaveURL(/\/admin\/news\/news\/\d+\/edit$/);
    await expect(page.locator('img[src*="news_images"]')).toHaveCount(0);

    await page.goto('/admin/news/news');
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

  test('adds, describes and removes an image on an interview', async ({ page }) => {
    // An individual, an interview, an upload and two saves before the teardown
    // even starts. See the news image test above.
    test.setTimeout(90000);

    // createInterview() leaves us on the edit screen, which is where the
    // Images card lives - on the create screen it is only a "save it first"
    // notice, because all three routes are keyed on an existing interview.
    const interview = await createInterview(page);

    const image = await addImage(page, 'interview_screenshots');
    await describeImage(page, uniqueName('Interview image'));
    await describeImage(page, uniqueName('Interview image again'));

    await deleteByAction(page, `/image/${image.id}`);
    await expect(page.locator('img[src*="interview_screenshots"]')).toHaveCount(0);

    await deleteInterview(page, interview);
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

  test('adds, describes and removes an image on an article', async ({ page }) => {
    // See the news image test above.
    test.setTimeout(90000);

    const article = await createArticle(page);

    const image = await addImage(page, 'article_screenshots');
    await describeImage(page, uniqueName('Article image'));
    await describeImage(page, uniqueName('Article image again'));

    await deleteByAction(page, `/image/${image.id}`);
    await expect(page.locator('img[src*="article_screenshots"]')).toHaveCount(0);

    await deleteArticle(page, article);
  });
});

test.describe('Admin article types', () => {
  test('creates, renames and deletes an article type', async ({ page }) => {
    const type = uniqueName('Type');
    const renamed = `${type} renamed`;

    // The odd one out in this file. Every other section edits a record on a
    // page of its own; a type is a single column, so the index *is* the edit
    // screen - one form per row, submitting a PUT from an input the row
    // renders inline. Nothing in the admin's JavaScript is involved, which is
    // exactly why the markup can rot unnoticed: every input on this page,
    // the add form's included, carries id="type", so the label points at
    // whichever one the browser finds first and none of these fields can be
    // addressed by it. Hence the scoping below.
    await page.goto('/admin/articles/types');

    const add = page.locator('form[action$="/articles/types"]');
    await add.locator('input[name="type"]').fill(type);
    await add.getByRole('button', { name: 'Add' }).click();

    await expect(page).toHaveURL(/\/admin\/articles\/types$/);
    // A type has no page and no link - the row is the input holding its name.
    const row = page.locator('tr').filter({ has: page.locator(`input[value="${type}"]`) });
    await expect(row).toHaveCount(1);

    await row.locator('input[name="type"]').fill(renamed);
    await row.getByRole('button', { name: 'Update' }).click();
    await page.waitForLoadState('domcontentloaded');

    await expect(page.locator(`input[value="${type}"]`)).toHaveCount(0);
    await expect(page.locator(`input[value="${renamed}"]`)).toHaveCount(1);

    // Not deleteByAction(): the update and the destroy forms post to the same
    // URL, so an action selector matches both. The bin's title is what tells
    // them apart, and it is also the accessible name.
    acceptConfirms(page);
    await page.getByRole('button', { name: `Delete '${renamed}'` }).click();
    await page.waitForLoadState('domcontentloaded');

    await expect(page.locator(`input[value="${renamed}"]`)).toHaveCount(0);
  });
});

// TODO: the article type as a filter on /articles and as a select on the
// article form; the <br /> normalisation the legacy CPANEL's rows need.
