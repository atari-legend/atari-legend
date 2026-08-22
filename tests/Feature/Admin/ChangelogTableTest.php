<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\ChangelogTable;
use App\Models\Changelog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ChangelogTableTest extends TestCase
{
    use RefreshDatabase;

    private User $alice;
    private User $bob;

    protected function setUp(): void
    {
        parent::setUp();

        $this->alice = User::factory()->create(['userid' => 'Alice']);
        $this->bob = User::factory()->create(['userid' => 'Bob']);

        $this->actingAs($this->alice);

        // Alice inserts a game in 2025, Bob updates a screenshot in 2026
        $this->entry($this->alice, Changelog::INSERT, 'Games', 'Bubble Bobble', '', '2025-06-01');
        $this->entry($this->alice, Changelog::UPDATE, 'Games', 'Xenon', 'Screenshots', '2026-02-10');
        $this->entry($this->bob, Changelog::UPDATE, 'Reviews', 'Xenon review', 'Comments', '2026-07-20');
    }

    private function entry(
        User $user,
        string $action,
        string $section,
        string $sectionName,
        string $subSection,
        string $date
    ): Changelog {
        return Changelog::create([
            'action'           => $action,
            'section'          => $section,
            'section_id'       => 1,
            'section_name'     => $sectionName,
            'sub_section'      => $subSection,
            'sub_section_id'   => 0,
            'sub_section_name' => '',
            'user_id'          => $user->getKey(),
            'timestamp'        => Carbon::parse($date . ' 12:00:00')->timestamp,
        ]);
    }

    /**
     * The most recent entry comes first without touching any control.
     */
    public function test_defaults_to_newest_first(): void
    {
        Livewire::test(ChangelogTable::class)
            ->assertSeeInOrder(['Xenon review', 'Xenon', 'Bubble Bobble']);
    }

    public function test_filters_by_action(): void
    {
        Livewire::test(ChangelogTable::class)
            ->set('filterComponents.action', Changelog::INSERT)
            ->assertSee('Bubble Bobble')
            ->assertDontSee('Xenon review');
    }

    public function test_filters_by_section(): void
    {
        Livewire::test(ChangelogTable::class)
            ->set('filterComponents.section', 'Reviews')
            ->assertSee('Xenon review')
            ->assertDontSee('Bubble Bobble');
    }

    /**
     * Sub-section options only list the ones used by the selected section.
     */
    public function test_sub_section_options_follow_the_section(): void
    {
        $component = Livewire::test(ChangelogTable::class)
            ->set('filterComponents.section', 'Games');

        $options = $component->instance()->filters()['sub_section']->getOptions();

        $this->assertSame(['' => 'Any', 'Screenshots' => 'Screenshots'], $options);
    }

    public function test_filters_by_sub_section(): void
    {
        Livewire::test(ChangelogTable::class)
            ->set('filterComponents.sub_section', 'Comments')
            ->assertSee('Xenon review')
            ->assertDontSee('Bubble Bobble');
    }

    public function test_filters_by_user(): void
    {
        Livewire::test(ChangelogTable::class)
            ->set('filterComponents.user', strval($this->bob->getKey()))
            ->assertSee('Xenon review')
            ->assertDontSee('Bubble Bobble');
    }

    /**
     * The date bounds are inclusive, and cover the whole of the day they name.
     */
    public function test_filters_by_date_range(): void
    {
        Livewire::test(ChangelogTable::class)
            ->set('filterComponents.from', '2026-01-01')
            ->assertSee('Xenon review')
            ->assertDontSee('Bubble Bobble');

        Livewire::test(ChangelogTable::class)
            ->set('filterComponents.to', '2026-02-10')
            ->assertSee('Bubble Bobble')
            ->assertDontSee('Xenon review');
    }

    public function test_admin_page_loads(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.others.changelog.index'))
            ->assertOk()
            ->assertSee('Changelog');
    }
}
