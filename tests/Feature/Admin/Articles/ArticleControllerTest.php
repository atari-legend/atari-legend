<?php

namespace Tests\Feature\Admin\Articles;

use App\Models\Article;
use App\Models\ArticleText;
use App\Models\ArticleType;
use App\Models\Changelog;
use App\Models\Screenshot;
use App\Models\ScreenshotArticle;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Admin\AdminTestCase;

/**
 * The admin Articles section.
 *
 * An article is two rows: the article itself and an article_text holding the
 * title, body and date. Keeping those in step is most of what this covers,
 * along with the screenshots hanging off it and their captions.
 */
class ArticleControllerTest extends AdminTestCase
{
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title'  => 'Coding the blitter',
            'author' => $this->admin->getKey(),
            'date'   => '2026-03-14',
            'intro'  => 'A short introduction.',
            'text'   => 'The body of the article.',
            'type'   => ArticleType::factory()->create()->getKey(),
            'draft'  => null,
        ], $overrides);
    }

    public function test_index_lists_the_articles(): void
    {
        Article::factory()->titled('Coding the blitter')->create();

        $this->get(route('admin.articles.articles.index'))
            ->assertOk()
            ->assertSee('Coding the blitter');
    }

    public function test_create_form_offers_the_types(): void
    {
        ArticleType::factory()->create(['article_type' => 'Hardware']);

        $this->get(route('admin.articles.articles.create'))
            ->assertOk()
            ->assertSee('Hardware');
    }

    public function test_edit_form_shows_the_article(): void
    {
        $article = Article::factory()->titled('Coding the blitter')->create();

        $this->get(route('admin.articles.articles.edit', $article))
            ->assertOk()
            ->assertSee('Coding the blitter');
    }

    public function test_store_creates_the_article_and_its_text(): void
    {
        $this->post(route('admin.articles.articles.store'), $this->payload())
            ->assertRedirect(route('admin.articles.articles.index'));

        $article = Article::sole();
        $text = $article->texts->first();

        $this->assertSame($this->admin->getKey(), $article->user_id);
        $this->assertSame('Coding the blitter', $text->article_title);
        $this->assertSame('A short introduction.', $text->article_intro);
        $this->assertSame('The body of the article.', $text->article_text);
        $this->assertSame(
            Carbon::parse('2026-03-14')->timestamp,
            $text->getRawOriginal('article_date')
        );

        $this->assertChangelog(Changelog::INSERT, 'Articles', 'Coding the blitter');
    }

    public function test_store_can_mark_the_article_as_a_draft(): void
    {
        $this->post(route('admin.articles.articles.store'), $this->payload(['draft' => '1']));

        $this->assertTrue((bool) Article::sole()->draft);
    }

    public function test_store_requires_the_text_fields(): void
    {
        $this->post(route('admin.articles.articles.store'), [])
            ->assertSessionHasErrors(['title', 'author', 'date', 'intro', 'text']);

        $this->assertSame(0, Article::query()->count());
        $this->assertNoChangelog();
    }

    /**
     * The author is a real user, not just any number.
     */
    public function test_store_rejects_an_unknown_author(): void
    {
        $this->post(route('admin.articles.articles.store'), $this->payload(['author' => 9999]))
            ->assertSessionHasErrors('author');

        $this->assertSame(0, Article::query()->count());
    }

    public function test_update_changes_both_rows(): void
    {
        $article = Article::factory()->titled('Old title')->create();
        $type = ArticleType::factory()->create();

        $this->put(route('admin.articles.articles.update', $article), $this->payload([
            'title' => 'New title',
            'text'  => 'Rewritten.',
            'type'  => $type->getKey(),
        ]))->assertRedirect(route('admin.articles.articles.index'));

        $article->refresh();

        $this->assertSame($type->getKey(), $article->article_type_id);
        $this->assertSame('New title', $article->texts->first()->article_title);
        $this->assertSame('Rewritten.', $article->texts->first()->article_text);
    }

    /**
     * The changelog records the title the article had before the edit, so a
     * rename can be traced back.
     */
    public function test_update_logs_the_previous_title(): void
    {
        $article = Article::factory()->titled('Old title')->create();

        $this->put(route('admin.articles.articles.update', $article), $this->payload([
            'title' => 'New title',
        ]));

        $entry = Changelog::sole();

        $this->assertSame(Changelog::UPDATE, $entry->action);
        $this->assertSame('Old title', $entry->section_name);
        $this->assertSame('New title', $entry->sub_section_name);
    }

    public function test_destroy_removes_the_article_its_text_and_its_images(): void
    {
        Storage::fake('public');

        $article = Article::factory()->titled('Doomed')->create();
        $screenshot = Screenshot::factory()->create();
        $article->screenshots()->attach($screenshot);
        Storage::disk('public')->put($screenshot->getPath('article'), 'image');

        $this->delete(route('admin.articles.articles.destroy', $article))
            ->assertRedirect(route('admin.articles.articles.index'));

        $this->assertSame(0, Article::query()->count());
        $this->assertSame(0, ArticleText::query()->count());
        $this->assertSame(0, Screenshot::query()->count());
        Storage::disk('public')->assertMissing($screenshot->getPath('article'));

        $this->assertChangelog(Changelog::DELETE, 'Articles', 'Doomed');
    }

    public function test_images_can_be_uploaded(): void
    {
        Storage::fake('public');

        $article = Article::factory()->titled('Coding the blitter')->create();

        $this->post(route('admin.articles.articles.image.store', $article), [
            'image' => [
                UploadedFile::fake()->image('one.png'),
                UploadedFile::fake()->image('two.png'),
            ],
        ])->assertRedirect(route('admin.articles.articles.edit', $article));

        $this->assertSame(2, $article->screenshots()->count());

        foreach ($article->screenshots as $screenshot) {
            $this->assertSame('png', $screenshot->imgext);
            Storage::disk('public')->assertExists($screenshot->getPath('article'));
        }

        $this->assertSame(
            2,
            Changelog::where('sub_section', 'Screenshots')->where('action', Changelog::INSERT)->count()
        );
    }

    public function test_an_upload_with_no_file_changes_nothing(): void
    {
        Storage::fake('public');

        $article = Article::factory()->create();

        $this->post(route('admin.articles.articles.image.store', $article), [])
            ->assertRedirect(route('admin.articles.articles.edit', $article));

        $this->assertSame(0, $article->screenshots()->count());
        $this->assertNoChangelog();
    }

    public function test_an_image_can_be_deleted(): void
    {
        Storage::fake('public');

        $article = Article::factory()->titled('Coding the blitter')->create();
        $screenshot = Screenshot::factory()->create();
        $article->screenshots()->attach($screenshot);
        Storage::disk('public')->put($screenshot->getPath('article'), 'image');

        $this->delete(route('admin.articles.articles.image.destroy', [$article, $screenshot]))
            ->assertRedirect(route('admin.articles.articles.edit', $article));

        $this->assertSame(0, Screenshot::query()->count());
        Storage::disk('public')->assertMissing($screenshot->getPath('article'));
        $this->assertChangelog(Changelog::DELETE, 'Articles', 'Coding the blitter');
    }

    /**
     * Captions are posted as description-{pivot id}, and the pivot row is what
     * the comment hangs off - not the screenshot.
     */
    public function test_a_caption_can_be_added_changed_and_removed(): void
    {
        $article = Article::factory()->titled('Coding the blitter')->create();
        $screenshot = Screenshot::factory()->create();
        $article->screenshots()->attach($screenshot);

        $pivot = ScreenshotArticle::sole();

        // Added
        $this->put(route('admin.articles.articles.image.update', $article), [
            'description-' . $pivot->getKey() => 'The blitter at work',
        ])->assertRedirect(route('admin.articles.articles.edit', $article));

        $this->assertSame('The blitter at work', $pivot->fresh()->comment->comment_text);

        // Changed
        $this->put(route('admin.articles.articles.image.update', $article), [
            'description-' . $pivot->getKey() => 'A better caption',
        ]);

        $this->assertSame('A better caption', $pivot->fresh()->comment->comment_text);

        // Removed
        $this->put(route('admin.articles.articles.image.update', $article), [
            'description-' . $pivot->getKey() => '',
        ]);

        $this->assertNull($pivot->fresh()->comment);
    }

    public function test_an_empty_caption_on_an_uncaptioned_image_stays_empty(): void
    {
        $article = Article::factory()->create();
        $screenshot = Screenshot::factory()->create();
        $article->screenshots()->attach($screenshot);

        $pivot = ScreenshotArticle::sole();

        $this->put(route('admin.articles.articles.image.update', $article), [
            'description-' . $pivot->getKey() => '',
        ])->assertRedirect();

        $this->assertNull($pivot->fresh()->comment);
    }

    public function test_a_caption_for_an_unknown_image_is_a_404(): void
    {
        $article = Article::factory()->create();

        $this->put(route('admin.articles.articles.image.update', $article), [
            'description-9999' => 'Nowhere',
        ])->assertNotFound();
    }

    public function test_non_admins_are_turned_away(): void
    {
        $article = Article::factory()->create();

        $this->assertNonAdminIsTurnedAway(route('admin.articles.articles.index'));
        $this->assertNonAdminIsTurnedAway(route('admin.articles.articles.edit', $article));
        $this->assertNonAdminIsTurnedAway(
            route('admin.articles.articles.store'),
            'post',
            $this->payload()
        );

        $this->assertSame(1, Article::query()->count());
    }
}
