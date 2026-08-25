<?php

namespace Tests\Feature\Public;

use App\Models\Article;
use App\Models\Changelog;
use App\Models\Comment;
use App\Models\Game;
use App\Models\Interview;
use App\Models\Magazine;
use App\Models\MagazineIssue;
use App\Models\News;
use App\Models\NewsSubmission;
use App\Models\Review;
use App\Models\User;
use App\Models\Website;
use App\Models\WebsiteCategory;
use App\Models\WebsiteValidate;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Articles, interviews, news, magazines, links, the changelog and the home
 * page: the listing and detail pages that make up the rest of the public site,
 * plus the three public submission forms.
 */
class ContentPagesTest extends TestCase
{
    use RefreshDatabase;

    private function article(string $title, string $date = '2026-01-01', ?User $author = null): Article
    {
        return Article::factory()->titled($title)->create([
            'user_id'      => ($author ?? User::factory()->create())->getKey(),
            'article_date' => Carbon::parse($date)->timestamp,
        ]);
    }

    private function interview(string $date = '2026-01-01'): Interview
    {
        $interview = Interview::factory()->create();
        $interview->texts()->first()->update(['interview_date' => Carbon::parse($date)->timestamp]);

        return $interview->fresh();
    }

    // Articles

    public function test_articles_are_listed_newest_first(): void
    {
        $this->article('Older', '2026-01-01');
        $this->article('Newer', '2026-06-01');

        $articles = $this->get(route('articles.index'))->assertOk()->viewData('articles');

        $this->assertSame(
            ['Newer', 'Older'],
            $articles->map(fn ($article) => $article->article_title)->all()
        );
    }

    public function test_an_article_page_shows_the_article(): void
    {
        $article = $this->article('Coding the blitter');

        $this->get(route('articles.show', $article))
            ->assertOk()
            ->assertSee('Coding the blitter');
    }

    /**
     * The sidebar offers the author's other articles, minus the one being read.
     */
    public function test_an_article_page_links_the_authors_other_articles(): void
    {
        $author = User::factory()->create();

        $article = $this->article('This one', author: $author);
        $this->article('Another one', author: $author);
        $this->article('By someone else');

        $others = $this->get(route('articles.show', $article))->assertOk()->viewData('otherArticles');

        $this->assertSame(
            ['Another one'],
            $others->map(fn ($other) => $other->article_title)->all()
        );
    }

    public function test_an_article_page_carries_structured_data(): void
    {
        $article = $this->article('Coding the blitter');

        $jsonLd = $this->get(route('articles.show', $article))->assertOk()->viewData('jsonLd')->json();

        $this->assertStringContainsString('"@type": "Article"', $jsonLd);
        $this->assertStringContainsString('Coding the blitter', $jsonLd);
    }

    public function test_a_signed_in_visitor_can_comment_on_an_article(): void
    {
        $article = $this->article('Coding the blitter');

        $this->actingAs(User::factory()->create())
            ->post(route('article.comment', $article), ['comment' => 'Useful.'])
            ->assertRedirect();

        $this->assertSame('Useful.', Comment::sole()->comment);
        $this->assertSame(1, Changelog::where('section', 'Articles')->count());
    }

    // Interviews

    public function test_interviews_are_listed_newest_first(): void
    {
        $older = $this->interview('2026-01-01');
        $newer = $this->interview('2026-06-01');

        $interviews = $this->get(route('interviews.index'))->assertOk()->viewData('interviews');

        $this->assertSame(
            [$newer->getKey(), $older->getKey()],
            $interviews->pluck('id')->all()
        );
    }

    public function test_an_interview_page_names_the_subject(): void
    {
        $interview = $this->interview();

        $this->get(route('interviews.show', $interview))
            ->assertOk()
            ->assertSee($interview->individual->ind_name);
    }

    public function test_an_interview_page_carries_structured_data(): void
    {
        $interview = $this->interview();

        $jsonLd = $this->get(route('interviews.show', $interview))->assertOk()->viewData('jsonLd')->json();

        $this->assertStringContainsString('Interview of ' . $interview->individual->ind_name, $jsonLd);
    }

    public function test_a_signed_in_visitor_can_comment_on_an_interview(): void
    {
        $interview = $this->interview();

        $this->actingAs(User::factory()->create())
            ->post(route('interview.comment', $interview), ['comment' => 'Great read.'])
            ->assertRedirect();

        $this->assertSame('Great read.', Comment::sole()->comment);
        $this->assertSame(1, Changelog::where('section', 'Interviews')->count());
    }

    // News

    public function test_news_is_listed_newest_first_and_paginated(): void
    {
        foreach (range(1, 8) as $i) {
            News::factory()->create([
                'news_headline' => 'Item ' . $i,
                'news_date'     => Carbon::parse('2026-01-01')->addDays($i)->timestamp,
            ]);
        }

        $news = $this->get(route('news.index'))->assertOk()->viewData('news');

        $this->assertCount(6, $news);
        $this->assertSame('Item 8', $news->first()->news_headline);
    }

    /**
     * A submitted news item goes to the submissions queue, not to the news
     * table, so it cannot appear on the site before an editor sees it.
     */
    public function test_submitted_news_waits_in_the_queue(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('news.submit'), ['title' => 'New dump released', 'text' => 'Details here.'])
            ->assertRedirect()
            ->assertSessionHas('alert-title', 'News submitted');

        $submission = NewsSubmission::sole();

        $this->assertSame('New dump released', $submission->news_headline);
        $this->assertSame($user->getKey(), $submission->user_id);
        $this->assertSame(0, News::query()->count());
        $this->assertSame(1, Changelog::where('sub_section', 'News submit')->count());
    }

    public function test_a_guest_cannot_submit_news(): void
    {
        $this->post(route('news.submit'), ['title' => 'Spam', 'text' => 'Spam'])
            ->assertRedirect(route('login'));

        $this->assertSame(0, NewsSubmission::query()->count());
    }

    // Magazines

    public function test_magazines_are_listed_alphabetically(): void
    {
        Magazine::factory()->create(['name' => 'ST Format']);
        Magazine::factory()->create(['name' => 'Atari ST User']);

        $magazines = $this->get(route('magazines.index'))->assertOk()->viewData('magazines');

        $this->assertSame(['Atari ST User', 'ST Format'], $magazines->pluck('name')->all());
    }

    public function test_a_magazine_page_lists_its_issues_in_order(): void
    {
        $magazine = Magazine::factory()->create(['name' => 'ST Format']);

        foreach ([3, 1, 2] as $number) {
            MagazineIssue::factory()->create([
                'magazine_id' => $magazine->getKey(),
                'issue'       => $number,
            ]);
        }

        $issues = $this->get(route('magazines.show', $magazine))->assertOk()->viewData('issues');

        $this->assertSame([1, 2, 3], $issues->pluck('issue')->all());
    }

    /**
     * The page-count chart only plots issues that have both a date and a count.
     */
    public function test_the_page_count_chart_skips_incomplete_issues(): void
    {
        $magazine = Magazine::factory()->create();

        MagazineIssue::factory()->create([
            'magazine_id' => $magazine->getKey(),
            'published'   => '1988-01-01',
            'page_count'  => 100,
        ]);
        MagazineIssue::factory()->create([
            'magazine_id' => $magazine->getKey(),
            'published'   => null,
            'page_count'  => 80,
        ]);
        MagazineIssue::factory()->create([
            'magazine_id' => $magazine->getKey(),
            'published'   => '1989-01-01',
            'page_count'  => null,
        ]);

        $this->assertCount(
            1,
            $this->get(route('magazines.show', $magazine))->assertOk()->viewData('pageCountChartData')
        );
    }

    // Links

    public function test_links_are_listed_and_can_be_filtered_by_category(): void
    {
        $emulation = WebsiteCategory::factory()->create(['website_category_name' => 'Emulation']);

        $inCategory = Website::factory()->create(['website_name' => 'Hatari']);
        $inCategory->categories()->attach($emulation);

        Website::factory()->create(['website_name' => 'Something else']);

        $all = $this->get(route('links.index'))->assertOk()->viewData('websites');
        $this->assertCount(2, $all);

        $filtered = $this->get(route('links.index', ['category' => $emulation->getKey()]))
            ->assertOk()
            ->viewData('websites');

        $this->assertSame(['Hatari'], $filtered->pluck('website_name')->all());
    }

    /**
     * A submitted link waits for approval in its own table.
     */
    public function test_a_submitted_link_waits_for_approval(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('links.submit'), [
                'name'        => 'Atari Legend',
                'url'         => 'https://www.atarilegend.com',
                'description' => 'This very site.',
            ])
            ->assertRedirect()
            ->assertSessionHas('alert-title', 'Link submitted');

        $this->assertSame('Atari Legend', WebsiteValidate::sole()->website_name);
        $this->assertSame(0, Website::query()->count());
        $this->assertSame(1, Changelog::where('section', 'Links')->count());
    }

    // Changelog

    public function test_the_changelog_lists_the_newest_changes_first(): void
    {
        $this->change('Games', 'Screenshots', 'Xenon', '2026-01-01');
        $this->change('Reviews', 'Comments', 'Turrican', '2026-06-01');

        $changes = $this->get(route('changelog.index'))->assertOk()->viewData('changes');

        $this->assertSame(['Turrican', 'Xenon'], $changes->pluck('section_name')->all());
    }

    public function test_the_changelog_can_be_filtered_by_section_and_subsection(): void
    {
        $this->change('Games', 'Screenshots', 'Xenon', '2026-01-01');
        $this->change('Games', 'Music', 'Turrican', '2026-02-01');
        $this->change('Reviews', 'Comments', 'Rick Dangerous', '2026-03-01');

        $bySection = $this->get(route('changelog.index', ['filter' => 'Games']))
            ->assertOk()
            ->viewData('changes');
        $this->assertCount(2, $bySection);

        $bySubsection = $this->get(route('changelog.index', ['filter' => 'Games:Music']))
            ->assertOk()
            ->viewData('changes');
        $this->assertSame(['Turrican'], $bySubsection->pluck('section_name')->all());
    }

    private function change(string $section, string $subSection, string $name, string $date): Changelog
    {
        return Changelog::create([
            'action'           => Changelog::UPDATE,
            'section'          => $section,
            'section_id'       => 1,
            'section_name'     => $name,
            'sub_section'      => $subSection,
            'sub_section_id'   => 0,
            'sub_section_name' => '',
            'user_id'          => User::factory()->create()->getKey(),
            'timestamp'        => Carbon::parse($date)->timestamp,
        ]);
    }

    // Home and about

    public function test_the_home_page_shows_the_latest_news(): void
    {
        foreach (range(1, 8) as $i) {
            News::factory()->create([
                'news_headline' => 'Item ' . $i,
                'news_date'     => Carbon::parse('2026-01-01')->addDays($i)->timestamp,
            ]);
        }

        $news = $this->get(route('home.index'))->assertOk()->viewData('news');

        $this->assertCount(6, $news);
        $this->assertSame('Item 8', $news->first()->news_headline);
    }

    public function test_the_about_pages_render(): void
    {
        $this->get(route('about.index'))->assertOk();
        $this->get(route('about.andreas'))->assertOk();
    }

    // Comments

    public function test_a_visitor_can_edit_their_own_comment(): void
    {
        $game = Game::factory()->named('Xenon')->create();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('games.comment', $game), ['comment' => 'First take.']);
        $comment = Comment::sole();

        $this->actingAs($user)
            ->post(route('comments.update'), [
                'comment_id' => $comment->getKey(),
                'comment'    => 'Second take.',
                'context'    => 'game',
                'id'         => $game->getKey(),
            ])
            ->assertRedirect();

        $this->assertSame('Second take.', $comment->fresh()->comment);
        $this->assertSame(1, Changelog::where('action', Changelog::UPDATE)->count());
    }

    /**
     * Someone else's comment must be left alone, silently.
     */
    public function test_a_visitor_cannot_edit_someone_elses_comment(): void
    {
        $game = Game::factory()->create();
        $author = User::factory()->create();

        $this->actingAs($author)->post(route('games.comment', $game), ['comment' => 'Mine.']);
        $comment = Comment::sole();

        $this->actingAs(User::factory()->create())
            ->post(route('comments.update'), [
                'comment_id' => $comment->getKey(),
                'comment'    => 'Hijacked.',
                'context'    => 'game',
                'id'         => $game->getKey(),
            ])
            ->assertRedirect();

        $this->assertSame('Mine.', $comment->fresh()->comment);
    }

    public function test_a_visitor_can_delete_their_own_comment(): void
    {
        $game = Game::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('games.comment', $game), ['comment' => 'Never mind.']);

        $this->actingAs($user)
            ->post(route('comments.delete'), [
                'comment_id' => Comment::sole()->getKey(),
                'context'    => 'game',
                'id'         => $game->getKey(),
            ])
            ->assertRedirect();

        $this->assertSame(0, Comment::query()->count());
        $this->assertSame(1, Changelog::where('action', Changelog::DELETE)->count());
    }

    public function test_a_visitor_cannot_delete_someone_elses_comment(): void
    {
        $game = Game::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('games.comment', $game), ['comment' => 'Mine.']);

        $this->actingAs(User::factory()->create())
            ->post(route('comments.delete'), [
                'comment_id' => Comment::sole()->getKey(),
                'context'    => 'game',
                'id'         => $game->getKey(),
            ])
            ->assertRedirect();

        $this->assertSame(1, Comment::query()->count());
    }

    /**
     * Comments can be edited from pages that are not a game, review, interview
     * or article. There is nothing sensible to write in the changelog then, so
     * the edit goes through without one.
     */
    public function test_an_edit_without_a_context_is_not_logged(): void
    {
        $game = Game::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('games.comment', $game), ['comment' => 'First take.']);
        Changelog::query()->delete();

        $this->actingAs($user)
            ->post(route('comments.update'), [
                'comment_id' => Comment::sole()->getKey(),
                'comment'    => 'Second take.',
            ])
            ->assertRedirect();

        $this->assertSame('Second take.', Comment::sole()->comment);
        $this->assertSame(0, Changelog::query()->count());
    }

    /**
     * Each context resolves a different name for the changelog entry.
     */
    public function test_the_changelog_names_the_thing_the_comment_is_on(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()
            ->forGame(Game::factory()->named('Turrican')->create()->getKey())
            ->scored()
            ->create();

        $this->actingAs($user)->post(route('review.comment', $review), ['comment' => 'Nice.']);
        Changelog::query()->delete();

        $this->actingAs($user)
            ->post(route('comments.update'), [
                'comment_id' => Comment::sole()->getKey(),
                'comment'    => 'Edited.',
                'context'    => 'review',
                'id'         => $review->getKey(),
            ])
            ->assertRedirect();

        $entry = Changelog::sole();

        $this->assertSame('Reviews', $entry->section);
        $this->assertSame('Turrican', $entry->section_name);
    }
}
