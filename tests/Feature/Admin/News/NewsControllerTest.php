<?php

namespace Tests\Feature\Admin\News;

use App\Models\Changelog;
use App\Models\News;
use App\Models\User;
use Carbon\Carbon;
use Tests\Feature\Admin\AdminTestCase;

/**
 * The admin News section, end to end over HTTP.
 *
 * The E2E suite already proves the index page renders; what it cannot cover is
 * the write paths, because it is read-only by design. So the weight here is on
 * store, update and destroy.
 */
class NewsControllerTest extends AdminTestCase
{
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'headline' => 'Automation 189 released',
            'author'   => $this->admin->getKey(),
            'date'     => '2026-03-14',
            'text'     => 'A new menu disk has been dumped.',
        ], $overrides);
    }

    public function test_index_lists_the_news(): void
    {
        News::factory()->create(['news_headline' => 'Automation 189 released']);

        $this->get(route('admin.news.news.index'))
            ->assertOk()
            ->assertSee('Automation 189 released');
    }

    public function test_create_form_loads(): void
    {
        $this->get(route('admin.news.news.create'))->assertOk();
    }

    public function test_edit_form_shows_the_item(): void
    {
        $news = News::factory()->create(['news_headline' => 'Xenon reviewed']);

        $this->get(route('admin.news.news.edit', $news))
            ->assertOk()
            ->assertSee('Xenon reviewed');
    }

    public function test_store_creates_the_item(): void
    {
        $this->post(route('admin.news.news.store'), $this->payload())
            ->assertRedirect(route('admin.news.news.index'));

        $news = News::sole();

        $this->assertSame('Automation 189 released', $news->news_headline);
        $this->assertSame('A new menu disk has been dumped.', $news->news_text);
        $this->assertSame($this->admin->getKey(), $news->user_id);

        // news_date is a unix timestamp in an integer column
        $this->assertSame(
            Carbon::parse('2026-03-14')->timestamp,
            $news->getRawOriginal('news_date')
        );
    }

    /**
     * A new item is an Insert. It was logged as an Update until 2026-08-10, so
     * changelog entries written before then say Update for news items that were
     * in fact created - AdminStatisticsHelper::changesByMonth() will show that
     * as a dip in inserts.
     */
    public function test_store_records_the_change(): void
    {
        $this->post(route('admin.news.news.store'), $this->payload());

        $this->assertChangelog(Changelog::INSERT, 'News', 'Automation 189 released');
    }

    /**
     * Insert and Update have to stay distinguishable: the two paths differ by
     * one constant, so a regression in either is invisible without this.
     */
    public function test_creating_and_editing_are_logged_differently(): void
    {
        $this->post(route('admin.news.news.store'), $this->payload());

        $this->put(route('admin.news.news.update', News::sole()), $this->payload([
            'headline' => 'Automation 189 re-released',
        ]));

        $this->assertChangelog(Changelog::INSERT, 'News', 'Automation 189 released');
        $this->assertChangelog(Changelog::UPDATE, 'News', 'Automation 189 re-released');
    }

    public function test_store_requires_every_field(): void
    {
        $this->post(route('admin.news.news.store'), [])
            ->assertSessionHasErrors(['headline', 'author', 'date', 'text']);

        $this->assertSame(0, News::query()->count());
        $this->assertNoChangelog();
    }

    public function test_store_rejects_an_unparseable_date(): void
    {
        $this->post(route('admin.news.news.store'), $this->payload(['date' => 'last Thursday-ish']))
            ->assertSessionHasErrors('date');

        $this->assertSame(0, News::query()->count());
    }

    public function test_update_persists_the_changes(): void
    {
        $news = News::factory()->create(['news_headline' => 'Old headline']);

        $this->put(route('admin.news.news.update', $news), $this->payload([
            'headline' => 'New headline',
            'text'     => 'Rewritten.',
        ]))->assertRedirect(route('admin.news.news.index'));

        $news->refresh();

        $this->assertSame('New headline', $news->news_headline);
        $this->assertSame('Rewritten.', $news->news_text);
        $this->assertChangelog(Changelog::UPDATE, 'News', 'New headline');
    }

    public function test_update_can_reassign_the_author(): void
    {
        $news = News::factory()->create();
        $author = User::factory()->create();

        $this->put(route('admin.news.news.update', $news), $this->payload([
            'author' => $author->getKey(),
        ]))->assertRedirect(route('admin.news.news.index'));

        $this->assertSame($author->getKey(), $news->fresh()->user_id);
    }

    public function test_update_rejects_an_empty_headline(): void
    {
        $news = News::factory()->create(['news_headline' => 'Untouched']);

        $this->put(route('admin.news.news.update', $news), $this->payload(['headline' => '']))
            ->assertSessionHasErrors('headline');

        $this->assertSame('Untouched', $news->fresh()->news_headline);
    }

    public function test_destroy_removes_the_item(): void
    {
        $news = News::factory()->create(['news_headline' => 'Retracted']);

        $this->delete(route('admin.news.news.destroy', $news))
            ->assertRedirect(route('admin.news.news.index'));

        $this->assertSame(0, News::query()->count());
        $this->assertChangelog(Changelog::DELETE, 'News', 'Retracted');
    }

    public function test_a_missing_item_is_a_404(): void
    {
        $this->get(route('admin.news.news.edit', 9999))->assertNotFound();
    }

    public function test_non_admins_are_turned_away(): void
    {
        $news = News::factory()->create();

        $this->assertNonAdminIsTurnedAway(route('admin.news.news.index'));
        $this->assertNonAdminIsTurnedAway(route('admin.news.news.create'));
        $this->assertNonAdminIsTurnedAway(route('admin.news.news.edit', $news));
        $this->assertNonAdminIsTurnedAway(route('admin.news.news.store'), 'post', $this->payload());

        $this->assertSame(1, News::query()->count());
        $this->assertNoChangelog();
    }

    /**
     * The Admin middleware sends anyone without an admin session to '/', and it
     * does not distinguish a guest from a signed-in non-admin: both get the
     * home page rather than a login prompt.
     */
    public function test_guests_are_sent_to_the_home_page(): void
    {
        auth()->logout();

        $this->get(route('admin.news.news.index'))->assertRedirect('/');
    }
}
