<?php

namespace Tests\Unit\Helpers;

use App\Helpers\Helper;
use Tests\TestCase;

/**
 * The site-specific BBCode tags, exercised through Helper::bbCode() rather than
 * by constructing the tag classes directly - that is how every caller reaches
 * them, and it also covers the parser wiring.
 *
 * The tags all build a link from an id, so the assertions are on the href.
 */
class BBCodeTagsTest extends TestCase
{
    public function test_a_game_tag_links_to_the_game(): void
    {
        $this->assertSame(
            '<a href="' . route('games.show', 42) . '">Xenon</a>',
            Helper::bbCode('[game=42]Xenon[/game]')
        );
    }

    public function test_an_interview_tag_links_to_the_interview(): void
    {
        $this->assertSame(
            '<a href="' . route('interviews.show', 7) . '">an interview</a>',
            Helper::bbCode('[interview=7]an interview[/interview]')
        );
    }

    public function test_a_review_tag_links_to_the_review(): void
    {
        $this->assertSame(
            '<a href="' . route('reviews.show', 3) . '">a review</a>',
            Helper::bbCode('[review=3]a review[/review]')
        );
    }

    public function test_an_article_tag_links_to_the_article(): void
    {
        $this->assertSame(
            '<a href="' . route('articles.show', 9) . '">an article</a>',
            Helper::bbCode('[article=9]an article[/article]')
        );
    }

    public function test_a_magazine_tag_links_to_the_magazine(): void
    {
        $this->assertStringContainsString('/magazines/', Helper::bbCode('[magazine=5]ST Format[/magazine]'));
    }

    /**
     * The search tags all point at the game search, each filling in its own
     * query parameter - the tag name plus '_id'.
     */
    public function test_search_tags_filter_the_game_search(): void
    {
        foreach (['publisher', 'developer', 'individual'] as $tag) {
            $this->assertSame(
                '<a href="' . route('games.search', [$tag . '_id' => 11]) . '">Ocean</a>',
                Helper::bbCode("[{$tag}=11]Ocean[/{$tag}]"),
                "The [{$tag}] tag did not link to a {$tag}_id search."
            );
        }
    }

    /**
     * The release year tag takes its value from the tag's content rather than
     * from an option.
     */
    public function test_the_release_year_tag_searches_for_that_year(): void
    {
        $this->assertSame(
            '<a href="' . route('games.search', ['year' => '1988']) . '">1988</a>',
            Helper::bbCode('[releaseYear]1988[/releaseYear]')
        );
    }

    /**
     * A menu set link carries the set, the page of the listing and an anchor
     * for the disk itself.
     */
    public function test_a_menu_set_tag_links_to_the_page_holding_the_disk(): void
    {
        $this->assertSame(
            '<a href="' . route('menus.show', ['set' => 1, 'page' => 3]) . '#menudisk-2">Automation #1A</a>',
            Helper::bbCode('[menuSet=1#2#3]Automation #1A[/menuSet]')
        );
    }

    /**
     * With no page given the tag falls back to the first one, so an older link
     * that only names the set and disk still works.
     */
    public function test_a_menu_set_tag_defaults_to_the_first_page(): void
    {
        $this->assertSame(
            '<a href="' . route('menus.show', ['set' => 1, 'page' => 1]) . '#menudisk-2">Automation</a>',
            Helper::bbCode('[menuSet=1#2]Automation[/menuSet]')
        );
    }

    public function test_hotspots_pair_a_chapter_link_with_its_anchor(): void
    {
        $this->assertSame(
            '<a href="#1">The early days</a>',
            Helper::bbCode('[hotspotUrl=#1]The early days[/hotspotUrl]')
        );

        $this->assertSame(
            '<span id="1">How did you start?</span>',
            Helper::bbCode('[hotspot=1]How did you start?[/hotspot]')
        );
    }

    /**
     * [frontpage] and [screenstar] mark a passage for extraction elsewhere;
     * they leave no trace in the rendered HTML.
     */
    public function test_marker_tags_render_only_their_contents(): void
    {
        $this->assertSame('A teaser', Helper::bbCode('[frontpage]A teaser[/frontpage]'));
        $this->assertSame('A caption', Helper::bbCode('[screenstar]A caption[/screenstar]'));
    }

    public function test_standard_bbcode_still_works_alongside_the_custom_tags(): void
    {
        $this->assertSame(
            '<strong>Xenon</strong> is a <em>classic</em>',
            Helper::bbCode('[b]Xenon[/b] is a [i]classic[/i]')
        );
    }

    /**
     * Smileys are substituted by a node visitor after parsing, so they have to
     * survive being nested inside a tag.
     */
    public function test_smileys_are_replaced_inside_tags(): void
    {
        $html = Helper::bbCode('[b]Great game :-D[/b]');

        $this->assertStringContainsString('icon_e_biggrin.gif', $html);
        $this->assertStringContainsString('<strong>', $html);
    }

    public function test_text_without_bbcode_is_left_alone(): void
    {
        $this->assertSame('Just some text', Helper::bbCode('Just some text'));
    }
}
