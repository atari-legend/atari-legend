<?php

namespace Tests\Feature\Helpers;

use App\Helpers\FeedHelper;
use App\Models\Article;
use App\Models\Game;
use App\Models\Interview;
use App\Models\News;
use App\Models\Review;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The site feed mixes news, reviews, interviews and articles into one list,
 * newest first, capped at 20. Each kind keeps its date in a different column -
 * two of them in a joined text table - so the ordering is the part worth
 * pinning down.
 */
class FeedHelperTest extends TestCase
{
    use RefreshDatabase;

    private function items(): \Illuminate\Support\Collection
    {
        return (new FeedHelper())->getFeedItems();
    }

    private function titles(): array
    {
        return $this->items()->map(fn ($item) => $item->toFeedItem()->title)->values()->all();
    }

    private function news(string $headline, string $date): News
    {
        return News::factory()->create([
            'news_headline' => $headline,
            'news_date'     => Carbon::parse($date)->timestamp,
        ]);
    }

    private function review(string $gameName, string $date): Review
    {
        return Review::factory()
            ->forGame(Game::factory()->named($gameName)->create()->getKey())
            ->create(['review_date' => Carbon::parse($date)->timestamp]);
    }

    private function interview(string $date): Interview
    {
        return Interview::factory()->create([
            'interview_date' => Carbon::parse($date)->timestamp,
        ]);
    }

    private function article(string $title, string $date): Article
    {
        return Article::factory()->titled($title)->create([
            'article_date' => Carbon::parse($date)->timestamp,
        ]);
    }

    public function test_an_empty_site_has_an_empty_feed(): void
    {
        $this->assertCount(0, $this->items());
    }

    public function test_the_feed_gathers_every_kind_of_item(): void
    {
        $this->news('A news item', '2026-01-01');
        $this->review('Xenon', '2026-01-02');
        $this->interview('2026-01-03');
        $this->article('An article', '2026-01-04');

        $this->assertCount(4, $this->items());
    }

    /**
     * The four kinds are interleaved by date, not grouped by type.
     */
    public function test_items_are_ordered_newest_first_across_types(): void
    {
        $this->news('Older news', '2026-01-01');
        $this->article('Middle article', '2026-01-03');
        $this->news('Newer news', '2026-01-05');
        $this->review('Xenon', '2026-01-04');

        $this->assertSame([
            'Newer news',
            'Review: Xenon',
            'Article: Middle article',
            'Older news',
        ], $this->titles());
    }

    /**
     * Unpublished reviews - what the public submission form creates - must not
     * reach the feed before an editor has seen them.
     */
    public function test_unpublished_reviews_are_left_out(): void
    {
        Review::factory()
            ->unpublished()
            ->forGame(Game::factory()->named('Unfinished')->create()->getKey())
            ->create(['review_date' => Carbon::parse('2026-01-01')->timestamp]);

        $this->review('Published', '2026-01-02');

        $this->assertSame(['Review: Published'], $this->titles());
    }

    public function test_the_feed_is_capped_at_twenty_items(): void
    {
        foreach (range(1, 25) as $i) {
            $this->news('News ' . $i, '2026-01-01 ' . sprintf('%02d:00:00', $i % 24));
        }

        $this->assertCount(20, $this->items());
    }

    /**
     * The cap is applied after merging, so a flood of one kind must not crowd
     * out a newer item of another.
     */
    public function test_the_newest_item_survives_a_flood_of_another_kind(): void
    {
        foreach (range(1, 25) as $i) {
            $this->news('News ' . $i, '2020-01-01');
        }

        $this->article('The newest thing', '2026-06-01');

        $this->assertSame('Article: The newest thing', $this->titles()[0]);
    }

    /**
     * Every item has to survive being turned into a feed entry - the titles are
     * built from a relation each, and a missing one would only surface here.
     */
    public function test_every_item_renders_as_a_feed_entry(): void
    {
        $this->news('A news item', '2026-01-01');
        $this->review('Xenon', '2026-01-02');
        $this->interview('2026-01-03');
        $this->article('An article', '2026-01-04');

        foreach ($this->items() as $item) {
            $feedItem = $item->toFeedItem();

            $this->assertNotSame('', $feedItem->title);
            $this->assertNotSame('', $feedItem->link);
            $this->assertNotNull($feedItem->updated);
        }
    }

    /**
     * The feed route is what actually calls this helper.
     */
    public function test_the_feed_route_renders(): void
    {
        $this->news('A news item', '2026-01-01');

        $this->get('/feed')
            ->assertOk()
            ->assertSee('A news item');
    }
}
