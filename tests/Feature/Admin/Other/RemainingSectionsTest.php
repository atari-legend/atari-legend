<?php

namespace Tests\Feature\Admin\Other;

use App\Models\Changelog;
use App\Models\Comment;
use App\Models\Game;
use App\Models\Magazine;
use App\Models\MagazineIssue;
use App\Models\Spotlight;
use App\Models\Trivia;
use App\Models\TriviaQuote;
use App\Models\User;
use App\Models\Website;
use App\Models\WebsiteCategory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Admin\AdminTestCase;

/**
 * The smaller admin sections: magazines and their issues, links and their
 * categories, the trivia/quote/spotlight panels behind the home page, and the
 * user and comment moderation screens.
 */
class RemainingSectionsTest extends AdminTestCase
{
    // Magazines

    public function test_a_magazine_can_be_created_edited_and_deleted(): void
    {
        $this->post(route('admin.magazines.magazines.store'), ['name' => 'ST Format'])
            ->assertRedirect(route('admin.magazines.magazines.edit', Magazine::sole()));

        $magazine = Magazine::sole();
        $this->assertSame('ST Format', $magazine->name);
        $this->assertChangelog(Changelog::INSERT, 'Magazines', 'ST Format');

        $this->put(route('admin.magazines.magazines.update', $magazine), ['name' => 'ST Format UK'])
            ->assertRedirect();
        $this->assertSame('ST Format UK', $magazine->fresh()->name);

        $this->delete(route('admin.magazines.magazines.destroy', $magazine))
            ->assertRedirect(route('admin.magazines.magazines.index'));
        $this->assertSame(0, Magazine::query()->count());
    }

    public function test_a_magazine_needs_a_name(): void
    {
        $this->post(route('admin.magazines.magazines.store'), [])->assertSessionHasErrors('name');

        $this->assertSame(0, Magazine::query()->count());
    }

    public function test_an_issue_can_be_added_to_a_magazine(): void
    {
        $magazine = Magazine::factory()->create(['name' => 'ST Format']);

        $this->post(route('admin.magazines.issues.store', $magazine), [
            'issue'       => 12,
            'published'   => '1990-07-01',
            'page_count'  => 132,
            'circulation' => 50000,
        ])->assertRedirect();

        $issue = MagazineIssue::sole();

        $this->assertSame(12, $issue->issue);
        $this->assertSame(132, $issue->page_count);
        $this->assertSame($magazine->getKey(), $issue->magazine_id);
    }

    /**
     * The archive.org field takes a details URL with a trailing slash, not any
     * URL - the reader is embedded from it.
     */
    public function test_an_issue_rejects_a_malformed_archive_url(): void
    {
        $magazine = Magazine::factory()->create();

        $this->post(route('admin.magazines.issues.store', $magazine), [
            'issue'          => 1,
            'archiveorg_url' => 'https://example.org/whatever',
        ])->assertSessionHasErrors('archiveorg_url');

        $this->post(route('admin.magazines.issues.store', $magazine), [
            'issue'          => 1,
            'archiveorg_url' => 'https://archive.org/details/stformat12/',
        ])->assertSessionDoesntHaveErrors('archiveorg_url');
    }

    public function test_an_issue_rejects_non_numeric_counts(): void
    {
        $magazine = Magazine::factory()->create();

        $this->post(route('admin.magazines.issues.store', $magazine), [
            'issue'      => 'twelve',
            'page_count' => 'many',
        ])->assertSessionHasErrors(['issue', 'page_count']);
    }

    public function test_an_issue_can_be_deleted(): void
    {
        $magazine = Magazine::factory()->create();
        $issue = MagazineIssue::factory()->create(['magazine_id' => $magazine->getKey()]);

        $this->delete(route('admin.magazines.issues.destroy', [$magazine, $issue]))
            ->assertRedirect();

        $this->assertSame(0, MagazineIssue::query()->count());
    }

    // Links

    public function test_a_link_can_be_created_with_categories(): void
    {
        $category = WebsiteCategory::factory()->create(['website_category_name' => 'Emulation']);

        $this->post(route('admin.links.links.store'), [
            'name'        => 'Hatari',
            'url'         => 'https://hatari.tuxfamily.org',
            'description' => 'An Atari ST emulator.',
            'categories'  => [$category->getKey()],
        ])->assertRedirect();

        $link = Website::sole();

        $this->assertSame('Hatari', $link->website_name);
        $this->assertSame(['Emulation'], $link->categories->pluck('website_category_name')->all());
        $this->assertChangelog(Changelog::INSERT, 'Links', 'Hatari');
    }

    public function test_a_link_needs_a_name_and_a_valid_url(): void
    {
        $this->post(route('admin.links.links.store'), ['name' => 'Hatari', 'url' => 'not a url'])
            ->assertSessionHasErrors('url');

        $this->post(route('admin.links.links.store'), [])
            ->assertSessionHasErrors(['name', 'url']);

        $this->assertSame(0, Website::query()->count());
    }

    public function test_a_link_rejects_an_unknown_category(): void
    {
        $this->post(route('admin.links.links.store'), [
            'name'       => 'Hatari',
            'url'        => 'https://hatari.tuxfamily.org',
            'categories' => [9999],
        ])->assertSessionHasErrors('categories.0');

        $this->assertSame(0, Website::query()->count());
    }

    /**
     * The inactive flag is how a submitted link is kept off the public page.
     */
    public function test_a_link_can_be_hidden_and_shown(): void
    {
        $link = Website::factory()->create(['website_name' => 'Hatari']);

        $this->put(route('admin.links.links.update', $link), [
            'name'     => 'Hatari',
            'url'      => 'https://hatari.tuxfamily.org',
            'inactive' => '1',
        ])->assertRedirect();

        $this->assertTrue((bool) $link->fresh()->inactive);
    }

    public function test_a_link_can_be_deleted(): void
    {
        $link = Website::factory()->create(['website_name' => 'Hatari']);

        $this->delete(route('admin.links.links.destroy', $link))
            ->assertRedirect(route('admin.links.links.index'));

        $this->assertSame(0, Website::query()->count());
        $this->assertChangelog(Changelog::DELETE, 'Links', 'Hatari');
    }

    public function test_a_link_category_can_be_managed(): void
    {
        $this->post(route('admin.links.categories.store'), ['name' => 'Emulation'])
            ->assertRedirect(route('admin.links.categories.index'));

        $category = WebsiteCategory::sole();
        $this->assertSame('Emulation', $category->website_category_name);

        $this->put(route('admin.links.categories.update', $category), ['name' => 'Emulators'])
            ->assertRedirect();
        $this->assertSame('Emulators', $category->fresh()->website_category_name);

        $this->delete(route('admin.links.categories.destroy', $category))->assertRedirect();
        $this->assertSame(0, WebsiteCategory::query()->count());
    }

    // Trivia, quotes and spotlights

    public function test_a_trivia_item_can_be_managed(): void
    {
        $this->post(route('admin.others.trivias.store'), ['text' => 'The ST shipped in 1985.'])
            ->assertRedirect(route('admin.others.trivias.index'));

        $trivia = Trivia::sole();
        $this->assertSame('The ST shipped in 1985.', $trivia->trivia_text);
        $this->assertChangelog(Changelog::INSERT, 'Trivia', 'The ST shipped in 1985.');

        $this->put(route('admin.others.trivias.update', $trivia), ['text' => 'The ST shipped in 1985!'])
            ->assertRedirect();
        $this->assertSame('The ST shipped in 1985!', $trivia->fresh()->trivia_text);

        $this->delete(route('admin.others.trivias.destroy', $trivia))->assertRedirect();
        $this->assertSame(0, Trivia::query()->count());
    }

    public function test_a_trivia_item_needs_text(): void
    {
        $this->post(route('admin.others.trivias.store'), [])->assertSessionHasErrors('text');

        $this->assertSame(0, Trivia::query()->count());
    }

    public function test_a_quote_can_be_managed(): void
    {
        $this->post(route('admin.others.quotes.store'), ['text' => 'Power without the price.'])
            ->assertRedirect(route('admin.others.quotes.index'));

        $quote = TriviaQuote::sole();

        $this->assertSame('Power without the price.', $quote->trivia_quote);

        $this->delete(route('admin.others.quotes.destroy', $quote))->assertRedirect();
        $this->assertSame(0, TriviaQuote::query()->count());
    }

    public function test_a_spotlight_can_be_created_with_an_image(): void
    {
        Storage::fake('public');

        $this->post(route('admin.others.spotlights.store'), [
            'spotlight' => 'A newly dumped menu disk.',
            'link'      => 'https://www.atarilegend.com',
            'image'     => UploadedFile::fake()->image('shot.png'),
        ])->assertRedirect(route('admin.others.spotlights.index'));

        $spotlight = Spotlight::sole();

        $this->assertSame('A newly dumped menu disk.', $spotlight->spotlight);
        $this->assertSame('https://www.atarilegend.com', $spotlight->link);
    }

    public function test_a_spotlight_needs_text_and_a_valid_link(): void
    {
        $this->post(route('admin.others.spotlights.store'), ['spotlight' => 'Something', 'link' => 'nope'])
            ->assertSessionHasErrors('link');

        $this->post(route('admin.others.spotlights.store'), [])
            ->assertSessionHasErrors(['spotlight', 'link']);

        $this->assertSame(0, Spotlight::query()->count());
    }

    // Users and comments

    public function test_a_user_can_be_edited(): void
    {
        $user = User::factory()->create(['email' => 'old@example.org']);

        $this->put(route('admin.users.users.update', $user), [
            'email'   => 'new@example.org',
            'website' => 'https://example.org',
        ])->assertRedirect();

        $this->assertSame('new@example.org', $user->fresh()->email);
    }

    public function test_a_user_cannot_take_another_users_email(): void
    {
        User::factory()->create(['email' => 'taken@example.org']);
        $user = User::factory()->create(['email' => 'mine@example.org']);

        $this->put(route('admin.users.users.update', $user), ['email' => 'taken@example.org'])
            ->assertSessionHasErrors('email');

        $this->assertSame('mine@example.org', $user->fresh()->email);
    }

    public function test_a_user_can_be_deleted(): void
    {
        $user = User::factory()->create();

        $this->delete(route('admin.users.users.destroy', $user))
            ->assertRedirect(route('admin.users.users.index'));

        $this->assertSame(0, User::whereKey($user->getKey())->count());
    }

    public function test_a_comment_can_be_edited_and_deleted(): void
    {
        $game = Game::factory()->named('Xenon')->create();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('games.comment', $game), ['comment' => 'Original.']);
        $this->actingAs($this->admin);

        $comment = Comment::sole();

        // The moderation form posts the body as `content`
        $this->put(route('admin.users.comments.update', $comment), ['content' => 'Moderated.'])
            ->assertRedirect();

        $this->assertSame('Moderated.', $comment->fresh()->comment);

        $this->delete(route('admin.users.comments.destroy', $comment))
            ->assertRedirect(route('admin.users.comments.index'));

        $this->assertSame(0, Comment::query()->count());

        // The changelog has to name what the comment was on, which is only
        // knowable before the delete removes the pivot rows
        $this->assertChangelog(Changelog::DELETE, 'Games', 'Xenon');
    }

    public function test_the_remaining_sections_are_closed_to_non_admins(): void
    {
        $this->assertNonAdminIsTurnedAway(route('admin.magazines.magazines.index'));
        $this->assertNonAdminIsTurnedAway(route('admin.links.links.index'));
        $this->assertNonAdminIsTurnedAway(route('admin.others.trivias.index'));
        $this->assertNonAdminIsTurnedAway(route('admin.users.users.index'));
    }
}
