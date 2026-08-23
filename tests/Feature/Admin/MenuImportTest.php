<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\MenuImport;
use App\Models\Game;
use App\Models\Menu;
use App\Models\MenuDisk;
use App\Models\MenuDiskCondition;
use App\Models\MenuDiskContent;
use App\Models\MenuSet;
use App\Models\GameRelease;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MenuImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // ChangelogHelper::insert() reads the authenticated user's id.
        $this->actingAs(User::factory()->create());
    }

    private function set(): MenuSet
    {
        return MenuSet::create(['name' => 'Test Set', 'menus_sort' => 'asc']);
    }

    private function game(string $name): Game
    {
        $game = new Game();
        $game->game_name = $name;
        $game->slug = \Illuminate\Support\Str::slug($name);
        $game->save();

        return $game;
    }

    /**
     * Build a content row in the shape resolveInitial() produces.
     */
    private function content(array $overrides = []): array
    {
        return array_merge([
            'row'           => 3,
            'order'         => 1,
            'name'          => 'From sheet',
            'query'         => 'From sheet',
            'subtype'       => null,
            'version'       => null,
            'requirements'  => null,
            'game_id'       => null,
            'game_name'     => null,
            'software_id'   => null,
            'software_name' => null,
            'candidates'    => [],
            'link_mode'     => 'new_release',
        ], $overrides);
    }

    /**
     * Build a disk row in the shape resolveInitial() produces.
     */
    private function disk(array $overrides = []): array
    {
        return array_merge([
            'part'                => 'A',
            'condition'           => null,
            'condition_id'        => null,
            'donated_by'          => null,
            'donated_by_id'       => null,
            'notes'               => null,
            'scrolltext'          => null,
            'existing_disk_id'    => null,
            'existing_disk_label' => null,
            'contents'            => [],
        ], $overrides);
    }

    /**
     * Build a menu row in the shape resolveInitial() produces.
     */
    private function menu(array $overrides = []): array
    {
        return array_merge([
            'number'              => '1',
            'issue'               => null,
            'version'             => null,
            'date'                => null,
            'existing_menu_id'    => null,
            'existing_menu_label' => null,
            'disks'               => [],
        ], $overrides);
    }

    public function testMergesIntoExistingMenuAndDiskFillingOnlyBlanks(): void
    {
        $set = $this->set();
        $condition = MenuDiskCondition::create(['name' => 'Good']);

        // Existing menu has a version already set but no date; existing disk has
        // a condition but no notes — both content-free.
        $menu = Menu::create([
            'number' => '1', 'version' => '1.0', 'date' => null, 'menu_set_id' => $set->id,
        ]);
        $disk = MenuDisk::create([
            'part' => 'A', 'menu_disk_condition_id' => $condition->id, 'notes' => null, 'menu_id' => $menu->id,
        ]);

        $game = $this->game('Merged Game');

        $menus = [$this->menu([
            'number'              => '1',
            'version'             => '2.0',          // must NOT overwrite existing 1.0
            'date'                => '1990-01-01',   // blank → should fill
            'existing_menu_id'    => $menu->id,
            'existing_menu_label' => $menu->label,
            'disks'               => [$this->disk([
                'part'                => 'A',
                'notes'               => 'imported notes', // blank → should fill
                'existing_disk_id'    => $disk->id,
                'existing_disk_label' => $disk->label,
                'contents'            => [$this->content([
                    'game_id' => $game->getKey(), 'game_name' => $game->game_name, 'link_mode' => 'new_release',
                ])],
            ])],
        ])];

        Livewire::test(MenuImport::class, ['set' => $set])
            ->set('reviewing', true)
            ->set('menus', $menus)
            ->call('runImport');

        // No duplicates created.
        $this->assertEquals(1, Menu::count());
        $this->assertEquals(1, MenuDisk::count());

        // Fill-blanks: version kept, date filled, notes filled.
        $menu->refresh();
        $disk->refresh();
        $this->assertEquals('1.0', $menu->version);
        $this->assertEquals('1990-01-01', $menu->date->format('Y-m-d'));
        $this->assertEquals('imported notes', $disk->notes);

        // Content landed on the existing disk, linked to a new release.
        $this->assertEquals(1, MenuDiskContent::where('menu_disk_id', $disk->id)->count());
        $this->assertEquals(1, GameRelease::where('game_id', $game->getKey())->count());
    }

    public function testCreatesNewMenuAndDiskWhenNoMatch(): void
    {
        $set = $this->set();
        $condition = MenuDiskCondition::create(['name' => 'Good']);
        $game = $this->game('Fresh Game');

        $menus = [$this->menu([
            'number' => '7',
            'disks'  => [$this->disk([
                'part'         => 'B',
                'condition_id' => $condition->id,
                'contents'     => [$this->content([
                    'game_id' => $game->getKey(), 'game_name' => $game->game_name, 'link_mode' => 'new_release',
                ])],
            ])],
        ])];

        Livewire::test(MenuImport::class, ['set' => $set])
            ->set('reviewing', true)
            ->set('menus', $menus)
            ->call('runImport');

        $this->assertEquals(1, Menu::count());
        $this->assertEquals(1, MenuDisk::count());
        $this->assertEquals('7', Menu::first()->number);
        $this->assertEquals('B', MenuDisk::first()->part);
    }

    public function testExtraLinksToNewReleaseOnMergedDisk(): void
    {
        $set = $this->set();
        $condition = MenuDiskCondition::create(['name' => 'Good']);
        $menu = Menu::create(['number' => '2', 'menu_set_id' => $set->id]);
        $disk = MenuDisk::create([
            'part' => 'A', 'menu_disk_condition_id' => $condition->id, 'menu_id' => $menu->id,
        ]);
        $game = $this->game('Extra Game');

        $menus = [$this->menu([
            'number'              => '2',
            'existing_menu_id'    => $menu->id,
            'existing_menu_label' => $menu->label,
            'disks'               => [$this->disk([
                'part'                => 'A',
                'existing_disk_id'    => $disk->id,
                'existing_disk_label' => $disk->label,
                'contents'            => [
                    $this->content([
                        'order'     => 1, 'game_id' => $game->getKey(), 'game_name' => $game->game_name,
                        'link_mode' => 'new_release',
                    ]),
                    $this->content([
                        'order'   => 2, 'game_id' => $game->getKey(), 'game_name' => $game->game_name,
                        'subtype' => 'Docs', 'link_mode' => 'extra',
                    ]),
                ],
            ])],
        ])];

        Livewire::test(MenuImport::class, ['set' => $set])
            ->set('reviewing', true)
            ->set('menus', $menus)
            ->call('runImport');

        $release = GameRelease::where('game_id', $game->getKey())->firstOrFail();
        $linked = MenuDiskContent::where('game_release_id', $release->id)->get();

        // Both the new_release row and the extra attach to the same release.
        $this->assertCount(2, $linked);
    }

    public function testDiskPartMatchingIsTrimmedCaseInsensitiveAndValueScoped(): void
    {
        $set = $this->set();
        $menu = Menu::create(['number' => '1', 'menu_set_id' => $set->id]);
        $disk = MenuDisk::create(['part' => 'A', 'menu_id' => $menu->id]);

        $component = Livewire::test(MenuImport::class, ['set' => $set])
            ->set('reviewing', true)
            ->set('menus', [$this->menu([
                'number'              => '1',
                'existing_menu_id'    => $menu->id,
                'existing_menu_label' => $menu->label,
                'disks'               => [$this->disk(['part' => 'A'])],
            ])]);

        // 'A' matches the existing disk.
        $component->set('menus.0.disks.0.part', 'A');
        $this->assertEquals($disk->id, $component->get('menus.0.disks.0.existing_disk_id'));

        // Case-insensitive / trimmed match.
        $component->set('menus.0.disks.0.part', '  a ');
        $this->assertEquals($disk->id, $component->get('menus.0.disks.0.existing_disk_id'));

        // A non-matching part clears it.
        $component->set('menus.0.disks.0.part', 'Z');
        $this->assertNull($component->get('menus.0.disks.0.existing_disk_id'));

        // A blank part does NOT match a disk that has a part.
        $component->set('menus.0.disks.0.part', '');
        $this->assertNull($component->get('menus.0.disks.0.existing_disk_id'));
    }

    public function testBlankPartMatchesExistingPartlessDisk(): void
    {
        $set = $this->set();
        $condition = MenuDiskCondition::create(['name' => 'Good']);
        // The common case: a single-disk menu where the disk has no part.
        $menu = Menu::create(['number' => '1', 'menu_set_id' => $set->id]);
        $disk = MenuDisk::create([
            'part' => null, 'menu_disk_condition_id' => $condition->id, 'menu_id' => $menu->id,
        ]);
        $game = $this->game('Partless Game');

        $menus = [$this->menu([
            'number'  => '1',
            'disks'   => [$this->disk([
                'part'     => null,
                'contents' => [$this->content([
                    'game_id' => $game->getKey(), 'game_name' => $game->game_name, 'link_mode' => 'new_release',
                ])],
            ])],
        ])];

        $component = Livewire::test(MenuImport::class, ['set' => $set])
            ->set('reviewing', true)
            ->set('menus', $menus);

        // The matcher links both the menu and its part-less disk.
        $component->set('menus.0.number', '1');
        $this->assertEquals($menu->id, $component->get('menus.0.existing_menu_id'));
        $this->assertEquals($disk->id, $component->get('menus.0.disks.0.existing_disk_id'));

        $component->call('runImport');

        // No new disk: content merged into the existing part-less disk.
        $this->assertEquals(1, MenuDisk::count());
        $this->assertEquals(1, MenuDiskContent::where('menu_disk_id', $disk->id)->count());
    }

    public function testRemoveDiskLeavesItOutOfTheImportAndKeepsOtherIndicesStable(): void
    {
        $set = $this->set();
        $condition = MenuDiskCondition::create(['name' => 'Good']);
        $kept = $this->game('Kept Game');
        $dropped = $this->game('Dropped Game');

        $menus = [$this->menu([
            'number' => '3',
            'disks'  => [
                $this->disk([
                    'part'         => 'A',
                    'condition_id' => $condition->id,
                    'contents'     => [$this->content([
                        'game_id' => $dropped->getKey(), 'game_name' => $dropped->game_name,
                    ])],
                ]),
                $this->disk([
                    'part'         => 'B',
                    'condition_id' => $condition->id,
                    'contents'     => [$this->content([
                        'game_id' => $kept->getKey(), 'game_name' => $kept->game_name,
                    ])],
                ]),
            ],
        ])];

        $component = Livewire::test(MenuImport::class, ['set' => $set])
            ->set('reviewing', true)
            ->set('menus', $menus)
            ->call('removeDisk', 0, 0);

        // The surviving disk keeps its original index, so the review screen's
        // index-addressed fields still point at the right row.
        $this->assertNull($component->get('menus.0.disks.0'));
        $this->assertEquals('B', $component->get('menus.0.disks.1.part'));

        $component->call('runImport');

        $this->assertEquals(1, MenuDisk::count());
        $this->assertEquals('B', MenuDisk::first()->part);
        $this->assertEquals(1, GameRelease::where('game_id', $kept->getKey())->count());
        $this->assertEquals(0, GameRelease::where('game_id', $dropped->getKey())->count());
    }

    public function testRemovingTheLastDiskOfAMenuDropsTheMenu(): void
    {
        $set = $this->set();
        $condition = MenuDiskCondition::create(['name' => 'Good']);
        $game = $this->game('Only Game');

        $menus = [
            $this->menu([
                'number' => '1',
                'disks'  => [$this->disk(['part' => 'A', 'condition_id' => $condition->id])],
            ]),
            $this->menu([
                'number' => '2',
                'disks'  => [$this->disk([
                    'part'         => 'A',
                    'condition_id' => $condition->id,
                    'contents'     => [$this->content([
                        'game_id' => $game->getKey(), 'game_name' => $game->game_name,
                    ])],
                ])],
            ]),
        ];

        $component = Livewire::test(MenuImport::class, ['set' => $set])
            ->set('reviewing', true)
            ->set('menus', $menus)
            ->call('removeDisk', 0, 0);

        // Menu 1 had a single disk, so no empty shell is left behind.
        $this->assertNull($component->get('menus.0'));
        $this->assertEquals('2', $component->get('menus.1.number'));

        $component->call('runImport');

        $this->assertEquals(1, Menu::count());
        $this->assertEquals('2', Menu::first()->number);
    }

    public function testRemovingTheLastDiskOfTheImportReturnsToTheUploadStep(): void
    {
        $set = $this->set();

        Livewire::test(MenuImport::class, ['set' => $set])
            ->set('reviewing', true)
            ->set('menus', [$this->menu(['disks' => [$this->disk()]])])
            ->call('removeDisk', 0, 0)
            ->assertSet('reviewing', false)
            ->assertSet('menus', []);
    }

    public function testRemoveContentClosesTheOrderGapAndKeepsOtherKeysStable(): void
    {
        $set = $this->set();
        $condition = MenuDiskCondition::create(['name' => 'Good']);
        $first = $this->game('First Game');
        $dropped = $this->game('Dropped Game');
        $last = $this->game('Last Game');

        $component = Livewire::test(MenuImport::class, ['set' => $set])
            ->set('reviewing', true)
            ->set('menus', [$this->menu([
                'number' => '4',
                'disks'  => [$this->disk([
                    'condition_id' => $condition->id,
                    'contents'     => [
                        $this->content(['order' => 1, 'game_id' => $first->getKey(), 'game_name' => $first->game_name]),
                        $this->content(['order' => 2, 'game_id' => $dropped->getKey(), 'game_name' => $dropped->game_name]),
                        $this->content(['order' => 3, 'game_id' => $last->getKey(), 'game_name' => $last->game_name]),
                    ],
                ])],
            ])])
            ->call('removeContent', 0, 0, 1);

        // The middle row is gone and the last one moved up to order 2, but it
        // keeps its original array key so the index-addressed autocomplete
        // inputs still point at it.
        $this->assertNull($component->get('menus.0.disks.0.contents.1'));
        $this->assertEquals(1, $component->get('menus.0.disks.0.contents.0.order'));
        $this->assertEquals(2, $component->get('menus.0.disks.0.contents.2.order'));
        $this->assertEquals($last->getKey(), $component->get('menus.0.disks.0.contents.2.game_id'));

        $component->call('runImport');

        $orders = MenuDiskContent::orderBy('order')->pluck('order')->all();
        $this->assertEquals([1, 2], $orders);
        $this->assertEquals(0, GameRelease::where('game_id', $dropped->getKey())->count());
    }

    public function testRemoveContentNormalisesOddSheetNumbering(): void
    {
        $set = $this->set();
        $game = $this->game('Numbered Game');

        // A sheet that started at 5 and had its own gaps.
        $component = Livewire::test(MenuImport::class, ['set' => $set])
            ->set('reviewing', true)
            ->set('menus', [$this->menu(['disks' => [$this->disk(['contents' => [
                $this->content(['order' => 5, 'game_id' => $game->getKey()]),
                $this->content(['order' => 6, 'game_id' => $game->getKey()]),
                $this->content(['order' => 9, 'game_id' => $game->getKey()]),
                $this->content(['order' => 12, 'game_id' => $game->getKey()]),
            ]])]])])
            ->call('removeContent', 0, 0, 1);

        $this->assertEquals(1, $component->get('menus.0.disks.0.contents.0.order'));
        $this->assertEquals(2, $component->get('menus.0.disks.0.contents.2.order'));
        $this->assertEquals(3, $component->get('menus.0.disks.0.contents.3.order'));
    }

    public function testRenumberDiskNormalisesWithoutDeleting(): void
    {
        $set = $this->set();

        $component = Livewire::test(MenuImport::class, ['set' => $set])
            ->set('reviewing', true)
            ->set('menus', [$this->menu(['disks' => [$this->disk(['contents' => [
                $this->content(['order' => 10]),
                $this->content(['order' => '']),
                $this->content(['order' => 40]),
            ]])]])])
            ->call('renumberDisk', 0, 0);

        $this->assertEquals(1, $component->get('menus.0.disks.0.contents.0.order'));
        $this->assertEquals(2, $component->get('menus.0.disks.0.contents.1.order'));
        $this->assertEquals(3, $component->get('menus.0.disks.0.contents.2.order'));
    }

    public function testRemovingAMainReleaseRowFlagsItsDependentExtra(): void
    {
        $set = $this->set();
        $condition = MenuDiskCondition::create(['name' => 'Good']);
        $game = $this->game('Orphaned Game');

        $component = Livewire::test(MenuImport::class, ['set' => $set])
            ->set('reviewing', true)
            ->set('menus', [$this->menu([
                'disks' => [$this->disk([
                    'condition_id' => $condition->id,
                    'contents'     => [
                        $this->content([
                            'order' => 1, 'game_id' => $game->getKey(), 'link_mode' => 'new_release',
                        ]),
                        $this->content([
                            'order'   => 2, 'game_id' => $game->getKey(),
                            'subtype' => 'Docs', 'link_mode' => 'extra',
                        ]),
                    ],
                ])],
            ])]);

        $this->assertEquals(0, $component->instance()->getErrorCount());

        // Dropping the main release leaves the extra with nothing to attach to.
        $component->call('removeContent', 0, 0, 0);

        $this->assertEquals(1, $component->instance()->getErrorCount());
        $component->assertSee('This game has no main release on this menu');
    }

    public function testRemovingTheLastContentRowKeepsTheDisk(): void
    {
        $set = $this->set();
        $condition = MenuDiskCondition::create(['name' => 'Good']);

        $component = Livewire::test(MenuImport::class, ['set' => $set])
            ->set('reviewing', true)
            ->set('menus', [$this->menu([
                'number' => '5',
                'disks'  => [$this->disk([
                    'part'         => 'A',
                    'condition_id' => $condition->id,
                    'contents'     => [$this->content(['game_id' => $this->game('Solo Game')->getKey()])],
                ])],
            ])])
            ->call('removeContent', 0, 0, 0);

        // A content-free disk is a normal record — it is kept, unlike an empty menu.
        $this->assertEquals([], $component->get('menus.0.disks.0.contents'));
        $component->call('runImport');

        $this->assertEquals(1, Menu::count());
        $this->assertEquals(1, MenuDisk::count());
        $this->assertEquals(0, MenuDiskContent::count());
    }

    public function testBothBlankNumberAndIssueNeverMatchAMenu(): void
    {
        $set = $this->set();
        // An existing menu with no number and no issue.
        $blankMenu = Menu::create(['number' => null, 'issue' => null, 'menu_set_id' => $set->id]);
        $numberedMenu = Menu::create(['number' => '1', 'menu_set_id' => $set->id]);

        $component = Livewire::test(MenuImport::class, ['set' => $set])
            ->set('reviewing', true)
            ->set('menus', [$this->menu(['number' => '1', 'issue' => null])]);

        // Number 1 matches the numbered menu.
        $component->set('menus.0.number', '1');
        $this->assertEquals($numberedMenu->id, $component->get('menus.0.existing_menu_id'));

        // Clearing both number and issue must NOT match the blank menu.
        $component->set('menus.0.number', '');
        $component->set('menus.0.issue', '');
        $this->assertNull($component->get('menus.0.existing_menu_id'));
    }
}
