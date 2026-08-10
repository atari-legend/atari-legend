import { test } from '../support/test.js';
import { FIXTURE } from '../support/fixture.js';
import { expectPageRenders } from '../support/assertions.js';

// News, reviews, interviews and articles are four instances of the same
// pattern - a Livewire table, an edit form, and (for some) image management -
// so they share a spec and a shape. Anything specific to one of them belongs
// in its own describe block below rather than in this table.

const sections = [
  {
    name: 'News',
    index: '/admin/news/news',
    create: '/admin/news/news/create',
    edit: `/admin/news/news/${FIXTURE.news.id}/edit`,
    extra: [{ name: 'submissions', path: '/admin/news/submissions' }],
  },
  {
    name: 'Reviews',
    index: '/admin/reviews/reviews',
    create: '/admin/reviews/reviews/create',
    edit: `/admin/reviews/reviews/${FIXTURE.review.id}/edit`,
    extra: [{ name: 'submissions', path: '/admin/reviews/submissions' }],
  },
  {
    name: 'Interviews',
    index: '/admin/interviews/interviews',
    create: '/admin/interviews/interviews/create',
    edit: `/admin/interviews/interviews/${FIXTURE.interview.id}/edit`,
    extra: [],
  },
  {
    name: 'Articles',
    index: '/admin/articles/articles',
    create: '/admin/articles/articles/create',
    edit: `/admin/articles/articles/${FIXTURE.article.id}/edit`,
    extra: [{ name: 'types', path: '/admin/articles/types' }],
  },
];

for (const section of sections) {
  test.describe(`Admin ${section.name.toLowerCase()}`, () => {
    test(`lists ${section.name.toLowerCase()}`, async ({ page }) => {
      await expectPageRenders(page, await page.goto(section.index), section.index);
    });

    test('opens the create form', async ({ page }) => {
      await expectPageRenders(page, await page.goto(section.create), section.create);
    });

    test('opens the edit form', async ({ page }) => {
      await expectPageRenders(page, await page.goto(section.edit), section.edit);
    });

    for (const extra of section.extra) {
      test(`lists the ${extra.name}`, async ({ page }) => {
        await expectPageRenders(page, await page.goto(extra.path), extra.path);
      });
    }
  });
}

// TODO for all four: saving an edit and asserting the changelog row, the
// "Save" versus "Save & Close" split, the <br /> normalisation the legacy
// CPANEL left behind, and the image upload/description flows on Articles and
// Interviews.
