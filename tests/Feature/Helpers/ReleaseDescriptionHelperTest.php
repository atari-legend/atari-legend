<?php

namespace Tests\Feature\Helpers;

use App\Helpers\ReleaseDescriptionHelper;
use App\Models\Game;
use App\Models\Menu;
use App\Models\MenuDisk;
use App\Models\MenuDiskContent;
use App\Models\MenuSet;
use App\Models\Release;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ReleaseDescriptionHelper turns a release and its relations into the prose
 * shown on the release page. It is the largest untested file in the app, and
 * almost all of it is punctuation logic: whether a clause opens with 'It was'
 * or a comma, whether a list ends with a full stop, whether 'and' joins two
 * halves. That only breaks in the combinations, so the tests below pair the
 * fragments up as well as exercising them alone.
 *
 * The output is BBCode, not HTML - [b], [game=..], [publisher=..] and so on are
 * rendered later by Helper::bbCode().
 */
class ReleaseDescriptionHelperTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The fragments come back as separate strings; joining them makes the
     * assertions read like the sentence a visitor sees.
     */
    private function describe(Release $release): string
    {
        return join(' ', ReleaseDescriptionHelper::descriptions($release->fresh()));
    }

    /**
     * A release stripped of everything optional, so each test adds back only
     * the one thing it is about. The game is left unnamed unless a test needs
     * to recognise it, because `game.slug` is unique and several tests build
     * more than one release.
     */
    private function release(array $attributes = [], ?string $gameName = null): Release
    {
        $game = $gameName === null
            ? Game::factory()->create()
            : Game::factory()->named($gameName)->create();

        return Release::factory()->create(array_merge([
            'game_id' => $game->getKey(),
            'name'    => null,
            'date'    => null,
            'license' => null,
        ], $attributes));
    }

    public function test_the_barest_release_still_names_its_game(): void
    {
        $release = $this->release(gameName: 'Xenon');

        $this->assertSame(
            'This is a release of [game=' . $release->game->getKey() . ']Xenon[/game].',
            trim($this->describe($release))
        );
    }

    public function test_a_named_release_opens_with_its_name(): void
    {
        $this->assertStringStartsWith(
            'Budget version is a release of',
            $this->describe($this->release(['name' => 'Budget version']))
        );
    }

    public function test_a_dated_release_states_the_year(): void
    {
        $description = $this->describe($this->release(['date' => '1988-06-01']));

        $this->assertStringContainsString('is a [releaseYear]1988[/releaseYear] release of', $description);
    }

    /**
     * Type and status share one pair of brackets, and are separated only when
     * both are present.
     */
    public function test_type_and_status_are_bracketed_together(): void
    {
        $this->assertStringContainsString(
            'is a release (budget) of',
            $this->describe($this->release(['type' => 'Budget']))
        );

        $this->assertStringContainsString(
            'is a release (unreleased) of',
            $this->describe($this->release(['status' => 'Unreleased']))
        );

        $this->assertStringContainsString(
            'is a release (budget, unreleased) of',
            $this->describe($this->release(['type' => 'Budget', 'status' => 'Unreleased']))
        );
    }

    public function test_locations_are_listed(): void
    {
        $release = Release::factory()->releasedIn('France', 'Germany')->create();

        $this->assertStringContainsString(
            'It was released in [b]France[/b], [b]Germany[/b].',
            $this->describe($release)
        );
    }

    public function test_a_publisher_is_named_with_a_link(): void
    {
        $release = Release::factory()->publishedBy('Ocean')->create();
        $publisher = $release->publisher;

        $this->assertStringContainsString(
            'It was published by [publisher=' . $publisher->getKey() . ']Ocean[/publisher].',
            $this->describe($release)
        );
    }

    /**
     * With a location already mentioned, the publisher clause continues the
     * sentence with a comma instead of starting a new 'It was'.
     */
    public function test_a_location_and_a_publisher_share_one_sentence(): void
    {
        $release = Release::factory()->releasedIn('France')->publishedBy('Ocean')->create();

        $this->assertStringContainsString(
            'It was released in [b]France[/b], published by',
            $this->describe($release)
        );
        $this->assertStringNotContainsString('[/b]. It was published', $this->describe($release));
    }

    public function test_distributors_are_listed_after_the_publisher(): void
    {
        $release = Release::factory()
            ->publishedBy('Ocean')
            ->distributedBy('Erbe', 'Proein')
            ->create();

        $this->assertStringContainsString(
            'published by [publisher=' . $release->publisher->getKey() . ']Ocean[/publisher], '
                . 'distributed by [b]Erbe[/b], [b]Proein[/b].',
            $this->describe($release)
        );
    }

    public function test_a_distributor_without_a_publisher_opens_the_clause(): void
    {
        $release = Release::factory()->distributedBy('Erbe')->create();

        $this->assertStringContainsString('It was distributed by [b]Erbe[/b].', $this->describe($release));
    }

    public function test_the_license_is_stated(): void
    {
        $this->assertStringContainsString(
            'Its license is [b]commercial[/b].',
            $this->describe($this->release(['license' => Release::LICENCE_COMMERCIAL]))
        );
    }

    public function test_an_empty_license_is_left_out(): void
    {
        $this->assertStringNotContainsString(
            'Its license is',
            $this->describe($this->release(['license' => '']))
        );
    }

    public function test_alternative_titles_are_listed_with_their_language(): void
    {
        $release = Release::factory()
            ->alsoKnownAs('Xenon II', 'fr')
            ->alsoKnownAs('Xenon Zwei')
            ->create();

        $this->assertStringContainsString(
            'It is also known as [b]Xenon II (fr)[/b], [b]Xenon Zwei[/b].',
            $this->describe($release)
        );
    }

    public function test_the_cracking_crew_is_named(): void
    {
        $release = Release::factory()->crackedBy('The Replicants')->create();

        $this->assertStringContainsString('It was cracked by [b]The Replicants[/b]', $this->describe($release));
    }

    public function test_languages_are_listed(): void
    {
        $release = Release::factory()->inLanguages('en')->create();

        $this->assertStringContainsString(
            'The following languages are supported: [b]en[/b].',
            $this->describe($release)
        );
    }

    /**
     * 'resolution' is pluralised from the count, so both branches matter.
     */
    public function test_resolutions_are_pluralised_by_count(): void
    {
        $this->assertStringContainsString(
            'It supports the following resolution: [b]Low[/b].',
            $this->describe(Release::factory()->inResolutions('Low')->create())
        );

        $this->assertStringContainsString(
            'It supports the following resolutions: [b]Low[/b], [b]Medium[/b].',
            $this->describe(Release::factory()->inResolutions('Low', 'Medium')->create())
        );
    }

    public function test_hard_drive_installability_is_stated_only_when_true(): void
    {
        $this->assertStringContainsString(
            'It can be installed on a hard-drive.',
            $this->describe(Release::factory()->hdInstallable()->create())
        );

        $this->assertStringNotContainsString(
            'hard-drive',
            $this->describe(Release::factory()->create())
        );
    }

    public function test_system_and_memory_enhancements_are_joined_with_and(): void
    {
        $release = Release::factory()
            ->enhancedForSystem('STE', 'Graphics')
            ->enhancedForMemory('1 MB')
            ->create();

        $this->assertStringContainsString(
            'It is enhanced for [b]STE (Graphics)[/b] and [b]1 MB[/b].',
            $this->describe($release)
        );
    }

    public function test_a_system_enhancement_alone_needs_no_and(): void
    {
        $release = Release::factory()->enhancedForSystem('Falcon')->create();

        $this->assertStringContainsString('It is enhanced for [b]Falcon[/b].', $this->describe($release));
        $this->assertStringNotContainsString('[b]Falcon[/b] and', $this->describe($release));
    }

    public function test_minimum_and_incompatible_memory_are_joined_with_and(): void
    {
        $release = Release::factory()
            ->requiringMemory('512 KB')
            ->incompatibleWithMemory('4 MB')
            ->create();

        $this->assertStringContainsString(
            'It requires a minimum memory of [b]512 KB[/b] and is incompatible with [b]4 MB[/b].',
            $this->describe($release)
        );
    }

    /**
     * Without a minimum, the incompatibility has to open its own sentence.
     */
    public function test_incompatible_memory_alone_opens_the_sentence(): void
    {
        $release = Release::factory()->incompatibleWithMemory('4 MB')->create();

        $this->assertStringContainsString('It is incompatible with [b]4 MB[/b].', $this->describe($release));
    }

    public function test_incompatibilities_are_listed_across_systems_emulators_and_tos(): void
    {
        $release = Release::factory()
            ->incompatibleWithSystems('Falcon')
            ->incompatibleWithEmulators('Hatari')
            ->incompatibleWithTos('2.06')
            ->create();

        $this->assertStringContainsString(
            'It is incompatible with [b]Falcon[/b], [b]Hatari[/b], TOS [b]2.06[/b].',
            $this->describe($release)
        );
    }

    public function test_an_incompatible_tos_can_name_a_language(): void
    {
        $release = Release::factory()->incompatibleWithTos('1.04', 'de')->create();

        $this->assertStringContainsString(
            'It is incompatible with TOS [b]1.04 (de)[/b].',
            $this->describe($release)
        );
    }

    public function test_copy_protection_notes_are_appended(): void
    {
        $release = Release::factory()
            ->copyProtectedBy('Manual lookup', 'page 12')
            ->create();

        $this->assertStringContainsString(
            'The game is copy protected via [b]Manual lookup (page 12)[/b].',
            $this->describe($release)
        );
    }

    public function test_empty_protection_notes_are_left_out(): void
    {
        $release = Release::factory()->copyProtectedBy('Code wheel', '')->create();

        $this->assertStringContainsString(
            'copy protected via [b]Code wheel[/b].',
            $this->describe($release)
        );
    }

    public function test_copy_and_disk_protection_share_one_sentence(): void
    {
        $release = Release::factory()
            ->copyProtectedBy('Manual lookup')
            ->diskProtectedBy('Macrodos')
            ->create();

        $this->assertStringContainsString(
            'The game is copy protected via [b]Manual lookup[/b] '
                . 'and the media is protected with [b]Macrodos[/b].',
            $this->describe($release)
        );
    }

    public function test_disk_protection_alone_opens_the_sentence(): void
    {
        $release = Release::factory()->diskProtectedBy('Speedlock')->create();

        $this->assertStringContainsString(
            'The media is protected with [b]Speedlock[/b].',
            $this->describe($release)
        );
    }

    /**
     * Protection 1 is the catch-all 'Yes' from the legacy data, which must not
     * be shown to a visitor by that name.
     */
    public function test_the_catch_all_disk_protection_is_described_as_unknown(): void
    {
        $release = Release::factory()->diskProtectedByUnknownScheme()->create();

        $description = $this->describe($release);

        $this->assertStringContainsString('protected with [b]an unknown scheme[/b].', $description);
        $this->assertStringNotContainsString('Yes', $description);
    }

    public function test_trainers_are_pluralised_by_count(): void
    {
        $this->assertStringContainsString(
            'The trainer [b]Infinite lives[/b] can be used.',
            $this->describe(Release::factory()->withTrainer('Infinite lives')->create())
        );

        $this->assertStringContainsString(
            'The trainers [b]Infinite lives[/b], [b]Level skip[/b] can be used.',
            $this->describe(
                Release::factory()->withTrainer('Infinite lives')->withTrainer('Level skip')->create()
            )
        );
    }

    /**
     * Fragments that came out empty are dropped, so the description never has
     * double spaces or stray punctuation from a section that had nothing to say.
     */
    public function test_empty_fragments_are_dropped(): void
    {
        $descriptions = ReleaseDescriptionHelper::descriptions($this->release());

        $this->assertCount(1, $descriptions);
        foreach ($descriptions as $fragment) {
            $this->assertNotSame('', trim($fragment));
        }
    }

    public function test_a_fully_described_release_reads_as_one_passage(): void
    {
        $release = Release::factory()
            ->publishedBy('Ocean')
            ->releasedIn('France')
            ->crackedBy('The Replicants')
            ->inLanguages('en')
            ->inResolutions('Low')
            ->requiringMemory('512 KB')
            ->copyProtectedBy('Manual lookup')
            ->withTrainer('Infinite lives')
            ->hdInstallable()
            ->create(['name' => 'Original', 'date' => '1988-06-01']);

        $descriptions = ReleaseDescriptionHelper::descriptions($release->fresh());

        // One fragment per non-empty group, in the order descriptions() builds them
        $this->assertCount(6, $descriptions);
        $this->assertStringStartsWith('Original is a [releaseYear]1988[/releaseYear] release', $descriptions[0]);
        $this->assertStringContainsString('cracked by [b]The Replicants[/b]', $descriptions[0]);
        $this->assertStringContainsString('languages are supported', $descriptions[1]);
        $this->assertStringContainsString('hard-drive', $descriptions[2]);
        $this->assertStringContainsString('minimum memory', $descriptions[3]);
        $this->assertStringContainsString('copy protected', $descriptions[4]);
        $this->assertStringContainsString('trainer', $descriptions[5]);
    }

    /**
     * The menu variant drops the editorial opening and the publisher, because
     * the reader is already looking at the menu disk.
     */
    public function test_menu_descriptions_leave_out_the_opening_sentence(): void
    {
        $release = Release::factory()
            ->publishedBy('Ocean')
            ->inLanguages('en')
            ->withTrainer('Infinite lives')
            ->create(['name' => 'Original']);

        $descriptions = ReleaseDescriptionHelper::menuDescriptions($release->fresh());
        $joined = join(' ', $descriptions);

        $this->assertStringNotContainsString('is a release of', $joined);
        $this->assertStringNotContainsString('published by', $joined);
        $this->assertStringContainsString('languages are supported', $joined);
        $this->assertStringContainsString('trainer', $joined);
    }

    public function test_menu_descriptions_of_a_bare_release_are_empty(): void
    {
        $this->assertSame([], ReleaseDescriptionHelper::menuDescriptions($this->release()));
    }

    /**
     * Put a release on a menu disk. The link carries three values -
     * set id, disk id and the page of the set listing the disk falls on -
     * which MenuSetBBCodeTag turns into a URL with an anchor.
     */
    private function putOnMenuDisk(Release $release, MenuDisk $disk): void
    {
        // forceCreate: game_release_id is outside MenuDiskContent::$fillable
        MenuDiskContent::forceCreate([
            'menu_disk_id'    => $disk->getKey(),
            'order'           => 1,
            'game_release_id' => $release->getKey(),
        ]);
    }

    public function test_a_release_on_a_menu_disk_links_to_it(): void
    {
        $set = MenuSet::factory()->create(['name' => 'Automation']);
        $menu = Menu::factory()->create(['menu_set_id' => $set->getKey(), 'number' => 76, 'version' => null]);
        $disk = MenuDisk::factory()->create(['menu_id' => $menu->getKey(), 'part' => 'A']);

        $release = Release::factory()->create();
        $this->putOnMenuDisk($release, $disk);

        $this->assertStringContainsString(
            'It is from the menu [menuSet=' . $set->getKey() . '#' . $disk->getKey() . '#1]'
                . 'Automation #76A[/menuSet].',
            $this->describe($release)
        );
    }

    /**
     * The page number is worked out from the disk's position in the set's own
     * ordering, 20 to a page - so a disk late in a large set must not link to
     * page 1.
     */
    public function test_the_menu_link_points_at_the_page_holding_the_disk(): void
    {
        $set = MenuSet::factory()->create(['name' => 'Automation']);

        $disks = collect(range(1, 21))->map(fn (int $number) => MenuDisk::factory()->create([
            'menu_id' => Menu::factory()->create([
                'menu_set_id' => $set->getKey(),
                'number'      => $number,
            ])->getKey(),
            'part' => 'A',
        ]));

        $release = Release::factory()->create();
        $this->putOnMenuDisk($release, $disks->last());

        $this->assertStringContainsString(
            '#' . $disks->last()->getKey() . '#2]',
            $this->describe($release),
            'The 21st disk of a set falls on page 2.'
        );
    }

    /**
     * A release can sit on several disks; the same menu must not be listed
     * twice.
     */
    public function test_repeated_menus_are_listed_once(): void
    {
        $set = MenuSet::factory()->create(['name' => 'Automation']);
        $menu = Menu::factory()->create(['menu_set_id' => $set->getKey(), 'number' => 1, 'version' => null]);
        $disk = MenuDisk::factory()->create(['menu_id' => $menu->getKey(), 'part' => 'A']);

        $release = Release::factory()->create();
        $this->putOnMenuDisk($release, $disk);
        $this->putOnMenuDisk($release, $disk);

        $this->assertSame(1, substr_count($this->describe($release), 'Automation #1A'));
    }
}
