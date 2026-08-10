<?php

namespace Tests\Feature\Public;

use App\Models\Game;
use App\Models\Interview;
use App\Models\Review;
use App\Models\WebsiteCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The sitemap is served as XML from three routes. The per-letter game index
 * used to filter digits with `regexp`, which is MySQL-only; it now goes through
 * Helper::whereTitleStartsWith(), the same utility the public searches use.
 */
class SitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_index_is_served_as_xml(): void
    {
        $this->get(route('sitemap.index'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/xml; charset=utf-8');
    }

    public function test_the_general_sitemap_lists_the_content(): void
    {
        $interview = Interview::factory()->create();
        $review = Review::factory()->forGame()->create();
        WebsiteCategory::factory()->create(['website_category_name' => 'Emulation']);

        $response = $this->get(route('sitemap.general'))->assertOk();

        $response->assertSee(route('interviews.show', $interview), escape: false);
        $response->assertSee(route('reviews.show', $review), escape: false);
    }

    /**
     * Unpublished reviews are not public, so they must not be advertised to a
     * crawler either.
     */
    public function test_the_general_sitemap_leaves_out_unpublished_reviews(): void
    {
        $review = Review::factory()->unpublished()->forGame()->create();

        $this->get(route('sitemap.general'))
            ->assertOk()
            ->assertDontSee(route('reviews.show', $review), escape: false);
    }

    public function test_games_are_listed_under_their_initial(): void
    {
        $xenon = Game::factory()->named('Xenon')->create();
        $arkanoid = Game::factory()->named('Arkanoid')->create();

        $this->get(route('sitemap.games', ['letter' => 'X']))
            ->assertOk()
            ->assertSee(route('games.show', $xenon), escape: false)
            ->assertDontSee(route('games.show', $arkanoid), escape: false);
    }

    /**
     * The '0-9' bucket has to catch every digit, which is what the regexp used
     * to do.
     */
    public function test_the_digit_bucket_catches_every_digit(): void
    {
        $numbered = collect(range(0, 9))->map(
            fn (int $digit) => Game::factory()->named($digit . ' Game')->create()
        );
        $letter = Game::factory()->named('Xenon')->create();

        $response = $this->get(route('sitemap.games', ['letter' => '0-9']))->assertOk();

        foreach ($numbered as $game) {
            $response->assertSee(route('games.show', $game), escape: false);
        }

        $response->assertDontSee(route('games.show', $letter), escape: false);
    }

    public function test_a_letter_with_no_games_still_renders(): void
    {
        Game::factory()->named('Xenon')->create();

        $this->get(route('sitemap.games', ['letter' => 'Q']))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/xml; charset=utf-8');
    }

    public function test_robots_txt_is_served(): void
    {
        $this->get('/robots.txt')->assertOk();
    }
}
