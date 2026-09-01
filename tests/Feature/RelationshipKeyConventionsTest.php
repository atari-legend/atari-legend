<?php

namespace Tests\Feature;

use App\Helpers\RelationshipKeyAudit;
use Tests\TestCase;

/**
 * Every relationship in app/Models either uses the foreign key Eloquent
 * derives -- and therefore names no key at all -- or is listed below with the
 * reason it cannot or should not.
 *
 * This is the end state of the foreign key rename campaign, kept from
 * eroding. The value is not in the count: it is that a new relation carrying
 * an explicit key argument has to be argued for here, in a list a reviewer
 * reads, rather than being added quietly because the surrounding code has
 * several.
 *
 * Run `artisan al:audit-relationship-keys` to see the current picture.
 *
 * See docs/plans/2026-08-23-foreign-key-rename.md, Phase D.
 */
class RelationshipKeyConventionsTest extends TestCase
{
    /**
     * The relations that end the campaign holding an explicit key argument on
     * purpose, and why. Four reasons, and no fifth has been accepted:
     *
     * - SELF-REFERENTIAL: the pivot needs two different key names and
     *   convention derives the same one for both. Unreachable by any rename.
     * - PIVOT SUBCLASS: AsPivot overrides getForeignKey(), which is what
     *   hasOne/hasMany consult, and it is null on a fresh instance -- there is
     *   no default to fall back to.
     * - TABLE, NOT MODEL: the schema is right and the model name is what
     *   diverges. The campaign's rule is foreign key = singularised table
     *   name, so the code carries the argument. Renaming the model would
     *   close these, and did for Release -> GameRelease where it was load
     *   bearing; the rest are optional tidy-ups.
     * - DECLINED ON PRICING: a method rename would close it, and costs more
     *   than it buys. ->type is 97 lines repo-wide and ->release 77, mostly
     *   plain columns on other models.
     */
    private const DECLINED = [
        // SELF-REFERENTIAL
        'Crew::parentCrews()'               => 'sub_crew needs crew_id and parent_id',
        'Crew::subCrews()'                  => 'sub_crew needs parent_id and crew_id',
        'Game::similarGames()'              => 'game_similar needs game_id and similar_game_id',
        'Game::similarGamesReverse()'       => 'game_similar needs similar_game_id and game_id',
        'Individual::nicknames()'           => 'individual_nicks needs individual_id and nick_id',
        'Individual::individuals()'         => 'individual_nicks needs nick_id and individual_id',

        // PIVOT SUBCLASS
        'ScreenshotArticle::comment()'      => 'declared on a Pivot: no derivable default exists',
        'ScreenshotInterview::comment()'    => 'declared on a Pivot: no derivable default exists',
        'ScreenshotReview::comment()'       => 'declared on a Pivot: no derivable default exists',

        // TABLE, NOT MODEL
        'GameRelease::publisher()'          => 'pub_dev_id is right; belongsTo derives publisher_id from the method name',
        'Game::vs()'                        => 'atari_id says what game_id would not',

        // DECLINED ON PRICING
        'Article::type()'                   => 'article_type_id is the better column name; ->type is 97 lines',
        'Media::type()'                     => 'media_type_id is the better column name; ->type is 97 lines',
        'MediaScan::type()'                 => 'media_scan_type_id is the better column name; ->type is 97 lines',
        'Media::release()'                  => 'gameRelease() would close it; ->release is 77 lines',
        'MenuDiskContent::release()'        => 'gameRelease() would close it; ->release is 77 lines',
        'GameReleaseAka::release()'         => 'gameRelease() would close it; ->release is 77 lines',
        'News::image()'                     => 'newsImage() would close it; ->image is ambiguous, deferred',
    ];

    public function test_no_relation_diverges_from_the_convention_without_a_reason(): void
    {
        $divergent = RelationshipKeyAudit::relations()
            ->where('divergent', true)
            ->pluck('label')
            ->sort()
            ->values()
            ->all();

        $declined = collect(self::DECLINED)->keys()->sort()->values()->all();

        $this->assertSame($declined, $divergent, implode("\n", [
            'A relation diverges from the key Eloquent derives, or a declared',
            'exception has stopped diverging.',
            '',
            'If you added a relation with an explicit key argument: delete the',
            'argument if it is what Eloquent would derive anyway, and if it is',
            'not, add it to self::DECLINED with the reason it has to stay.',
            '',
            'If you closed one -- by renaming a column, a method or a model --',
            'delete its line from self::DECLINED.',
            '',
            'artisan al:audit-relationship-keys shows the current picture.',
        ]));
    }

    public function test_no_relation_passes_a_key_argument_it_would_derive_anyway(): void
    {
        $redundant = RelationshipKeyAudit::relations()
            ->where('redundant', true)
            ->map(fn ($relation) => $relation->label . '  (' . $relation->actual . ')')
            ->all();

        $this->assertSame([], $redundant, implode("\n", array_merge(
            ['These relations name a foreign key that Eloquent derives by itself:'],
            $redundant,
            [
                '',
                'Delete the argument. Keep the pivot table argument of a',
                'belongsToMany -- only the third and fourth are keys, and the',
                'derived table name is alphabetical, so it is right for',
                'game_individual and wrong for game_release_crew.',
            ]
        )));
    }
}
