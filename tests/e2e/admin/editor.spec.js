import { test, expect } from '../support/test.js';
import { FIXTURE } from '../support/fixture.js';
import { expectPageRenders } from '../support/assertions.js';
import {
  editorBody,
  editorFor,
  expectEditorsBooted,
  insertViaButton,
  toggleSource,
} from '../support/editor.js';

// The BBCode editor, which is one feature spread across four sections rather
// than a section of its own - hence a spec of its own.
//
// It is the piece of admin JavaScript that fails most quietly: SCEditor
// replaces a textarea, so when it does not run the form still renders, still
// submits, and still saves - just without a toolbar, and with whatever BBCode
// the author typed by hand. Every page below was already visited by another
// spec, all of which passed throughout #272.
//
// Read-only, despite driving forms: these tests type into editors and never
// submit, so nothing here writes. Creating content through the editor is
// admin-write/content.spec.js.

const pages = [
  { name: 'news', path: `/admin/news/news/${FIXTURE.news.id}/edit` },
  { name: 'reviews', path: `/admin/reviews/reviews/${FIXTURE.review.id}/edit` },
  { name: 'interviews', path: `/admin/interviews/interviews/${FIXTURE.interview.id}/edit` },
  { name: 'articles', path: `/admin/articles/articles/${FIXTURE.article.id}/edit` },
  { name: 'individuals', path: `/admin/games/individuals/${FIXTURE.individual.id}/edit` },
  { name: 'companies', path: `/admin/games/companies/${FIXTURE.company.id}/edit` },
  // The fact edit route has no /edit; see the comment in admin/games.spec.js.
  { name: 'game facts', path: `/admin/games/${FIXTURE.game.id}/facts/${FIXTURE.game.factId}` },
  { name: 'user comments', path: `/admin/users/comments/${FIXTURE.comment.id}/edit` },
];

test.describe('Admin BBCode editor', () => {
  for (const { name, path } of pages) {
    test(`boots on the ${name} form`, async ({ page }) => {
      await expectPageRenders(page, await page.goto(path), path);

      await expectEditorsBooted(page);
    });
  }
});

// The custom commands and BBCodes from resources/js/admin/sceditor.js, driven
// on the news form because its single #text editor is the simplest host. What
// is being tested is our configuration of SCEditor rather than SCEditor
// itself, so there is one test per shape of custom code, not one per code.
test.describe('Admin BBCode editor commands', () => {
  const path = `/admin/news/news/${FIXTURE.news.id}/edit`;

  test('offers a toolbar button for every custom code', async ({ page }) => {
    await page.goto(path);
    const container = editorFor(page, 'text');

    for (const code of [
      'game', 'review', 'article', 'interview', 'menuset', 'magazine',
      'releaseyear', 'publisher', 'developer', 'individual',
    ]) {
      await expect(container.locator(`.sceditor-button-${code}`)).toBeVisible();
    }
  });

  test('inserts a link to a game', async ({ page }) => {
    await page.goto(path);
    const container = editorFor(page, 'text');

    await insertViaButton(container, 'game', {
      game: FIXTURE.game.id,
      text: FIXTURE.game.name,
    });

    const link = editorBody(container).locator(`a[data-al-game-id="${FIXTURE.game.id}"]`);
    await expect(link).toHaveText(FIXTURE.game.name);
    await expect(link).toHaveAttribute('href', `/games/${FIXTURE.game.id}`);

    const source = await toggleSource(container);
    const bbcode = await source.inputValue();

    expect(bbcode).toContain(`[game=${FIXTURE.game.id}]${FIXTURE.game.name}[/game]`);
    // The reason the [url] tag is overridden in sceditor.js: every custom code
    // renders as an <a href>, so without that override this anchor would be
    // formatted back as a plain link and the game reference would be lost on
    // the next save. The assertion above cannot tell the difference on its own.
    expect(bbcode).not.toContain('[url=');
  });

  test('inserts a code that points at the search', async ({ page }) => {
    await page.goto(path);
    const container = editorFor(page, 'text');

    await insertViaButton(container, 'publisher', {
      publisher: FIXTURE.company.id,
      text: FIXTURE.company.name,
    });

    // Publishers, developers and individuals have no page of their own, so
    // these three link to a search rather than to /publishers/1.
    const link = editorBody(container).locator(`a[data-al-publisher-id="${FIXTURE.company.id}"]`);
    await expect(link).toHaveAttribute(
      'href',
      `/games/search?publisher_id=${FIXTURE.company.id}`
    );

    const source = await toggleSource(container);
    expect(await source.inputValue())
      .toContain(`[publisher=${FIXTURE.company.id}]${FIXTURE.company.name}[/publisher]`);
  });

  test('inserts a release year', async ({ page }) => {
    await page.goto(path);
    const container = editorFor(page, 'text');

    // The odd one out: a year carries no id, so the value is the content of
    // the tag rather than an attribute on it.
    await insertViaButton(container, 'releaseyear', { releaseyear: FIXTURE.release.year });

    await expect(editorBody(container).locator('a[data-al-releaseyear-id]'))
      .toHaveText(FIXTURE.release.year);

    const source = await toggleSource(container);
    expect(await source.inputValue())
      .toContain(`[releaseyear]${FIXTURE.release.year}[/releaseyear]`);
  });

  test('renders BBCode typed as source', async ({ page }) => {
    await page.goto(path);
    const container = editorFor(page, 'text');

    // The direction an existing body takes when an edit form loads, which the
    // tests above do not cover: they start from the toolbar, which builds the
    // HTML rather than parsing it.
    const source = await toggleSource(container);
    await source.fill(`[b]Bold[/b] [game=${FIXTURE.game.id}]${FIXTURE.game.name}[/game]`);
    await toggleSource(container);

    const body = editorBody(container);
    await expect(body.locator('strong')).toHaveText('Bold');
    await expect(body.locator(`a[data-al-game-id="${FIXTURE.game.id}"]`))
      .toHaveText(FIXTURE.game.name);
  });
});

// TODO: the editor is also on the create forms, where it starts empty - covered
// only through admin-write/content.spec.js filling it. Not covered at all: the
// emoticon dropdown, the image and link commands, and maximize.
