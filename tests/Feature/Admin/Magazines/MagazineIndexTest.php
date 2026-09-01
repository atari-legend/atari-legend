<?php

namespace Tests\Feature\Admin\Magazines;

use App\Livewire\Admin\MagazineIndex as MagazineIndexComponent;
use App\Models\Changelog;
use App\Models\Game;
use App\Models\Individual;
use App\Models\MagazineIndex;
use App\Models\MagazineIndexType;
use App\Models\MagazineIssue;
use App\Models\MenuSoftware;
use Livewire\Livewire;
use Tests\Feature\Admin\AdminTestCase;

/**
 * The index editor hanging off a magazine issue: one row per article, each
 * pointing at a game, a piece of menu software, an individual, or nothing at
 * all.
 *
 * The rows are edited in place - Livewire binds straight into the issue's
 * `indices` relation - so the tests are about what ends up in the database
 * after each interaction, not about the markup.
 */
class MagazineIndexTest extends AdminTestCase
{
    private function issue(): MagazineIssue
    {
        return MagazineIssue::factory()->create(['issue' => 12]);
    }

    public function test_it_lists_the_entries_of_its_issue(): void
    {
        $issue = $this->issue();

        MagazineIndex::factory()->forGame()->create([
            'magazine_issue_id' => $issue->getKey(),
            'title'             => 'Xenon reviewed',
            'game_id'           => Game::factory()->named('Xenon')->create()->getKey(),
        ]);
        MagazineIndex::factory()->forSoftware()->create([
            'magazine_issue_id' => $issue->getKey(),
            'title'             => 'Tracker roundup',
            'menu_software_id'  => MenuSoftware::factory()->named('Xtracker')->create()->getKey(),
        ]);
        MagazineIndex::factory()->forIndividual()->create([
            'magazine_issue_id' => $issue->getKey(),
            'title'             => 'Meet the coder',
            'individual_id'     => Individual::factory()->create(['name' => 'Jochen Hippel'])->getKey(),
        ]);

        MagazineIndex::factory()->create(['title' => 'Another issue entirely']);

        Livewire::test(MagazineIndexComponent::class, ['issue' => $issue])
            ->assertSee('Xenon reviewed')
            ->assertSee('Xenon')
            ->assertSee('Tracker roundup')
            ->assertSee('Xtracker')
            ->assertSee('Meet the coder')
            ->assertSee('Jochen Hippel')
            ->assertDontSee('Another issue entirely');
    }

    /**
     * The type dropdown is filled from the whole reference list, not from the
     * types already in use in this issue.
     */
    public function test_it_offers_every_index_type(): void
    {
        MagazineIndexType::factory()->named('Cover disk')->create();

        MagazineIndex::factory()->create([
            'magazine_issue_id' => ($issue = $this->issue())->getKey(),
        ]);

        Livewire::test(MagazineIndexComponent::class, ['issue' => $issue])
            ->assertSee('Cover disk')
            ->assertSee('Review');
    }

    public function test_a_row_can_be_added(): void
    {
        $issue = $this->issue();

        Livewire::test(MagazineIndexComponent::class, ['issue' => $issue])
            ->call('addRow')
            ->assertDispatched('magazine-index-rows-updated');

        $this->assertSame(1, $issue->indices()->count());

        // Adding a row saves the rows already on screen first
        $this->assertChangelog(Changelog::UPDATE, 'Magazines', $issue->magazine->name);
    }

    public function test_a_row_can_be_deleted(): void
    {
        $issue = $this->issue();

        $kept = MagazineIndex::factory()->create([
            'magazine_issue_id' => $issue->getKey(),
            'title'             => 'Kept',
        ]);
        $removed = MagazineIndex::factory()->create([
            'magazine_issue_id' => $issue->getKey(),
            'title'             => 'Removed',
        ]);

        Livewire::test(MagazineIndexComponent::class, ['issue' => $issue])
            ->call('deleteRow', $removed->getKey())
            ->assertDispatched('magazine-index-rows-updated')
            ->assertSee('Kept')
            ->assertDontSee('Removed');

        $this->assertSame([$kept->getKey()], $issue->indices()->pluck('id')->all());
    }

    public function test_an_entry_can_be_edited_and_saved(): void
    {
        $issue = $this->issue();

        $index = MagazineIndex::factory()->create([
            'magazine_issue_id' => $issue->getKey(),
            'title'             => 'Untitled',
            'page'              => 4,
        ]);
        $type = MagazineIndexType::factory()->named('Cover disk')->create();

        Livewire::test(MagazineIndexComponent::class, ['issue' => $issue])
            ->set('issue.indices.0.title', 'Xenon reviewed')
            ->set('issue.indices.0.page', 42)
            ->set('issue.indices.0.score', '85%')
            ->set('issue.indices.0.magazine_index_type_id', $type->getKey())
            ->call('save')
            ->assertHasNoErrors();

        $index->refresh();

        $this->assertSame('Xenon reviewed', $index->title);
        $this->assertSame(42, $index->page);
        $this->assertSame('85%', $index->score);
        $this->assertSame($type->getKey(), $index->magazine_index_type_id);
        $this->assertChangelog(Changelog::UPDATE, 'Magazines', $issue->magazine->name);
    }

    /**
     * The page field is free text in practice - clearing it posts an empty
     * string, which has to be stored as NULL so that the auto-sort and the
     * public index do not treat it as page 0.
     */
    public function test_a_blank_page_is_stored_as_null(): void
    {
        $issue = $this->issue();

        $index = MagazineIndex::factory()->create([
            'magazine_issue_id' => $issue->getKey(),
            'page'              => 42,
        ]);

        Livewire::test(MagazineIndexComponent::class, ['issue' => $issue])
            ->set('issue.indices.0.page', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull($index->fresh()->page);
    }

    public function test_a_non_numeric_page_is_rejected(): void
    {
        $issue = $this->issue();

        $index = MagazineIndex::factory()->create([
            'magazine_issue_id' => $issue->getKey(),
            'page'              => 42,
        ]);

        Livewire::test(MagazineIndexComponent::class, ['issue' => $issue])
            ->set('issue.indices.0.page', 'forty two')
            ->call('save')
            ->assertHasErrors('issue.indices.0.page');

        $this->assertSame(42, $index->fresh()->page);
        $this->assertNoChangelog();
    }

    public function test_an_entry_can_point_at_a_game(): void
    {
        $issue = $this->issue();
        $index = MagazineIndex::factory()->create(['magazine_issue_id' => $issue->getKey()]);
        $game = Game::factory()->named('Xenon')->create();

        $component = Livewire::test(MagazineIndexComponent::class, ['issue' => $issue])
            ->call('updateGame', $index->getKey(), $game->getKey());

        $this->assertSame($game->getKey(), $index->fresh()->game_id);
        $component->assertSee('Xenon');

        $component->call('updateGame', $index->getKey(), null);

        $this->assertNull($index->fresh()->game_id);
    }

    public function test_an_entry_can_point_at_a_piece_of_software(): void
    {
        $issue = $this->issue();
        $index = MagazineIndex::factory()->create(['magazine_issue_id' => $issue->getKey()]);
        $software = MenuSoftware::factory()->named('Xtracker')->create();

        $component = Livewire::test(MagazineIndexComponent::class, ['issue' => $issue])
            ->call('updateSoftware', $index->getKey(), $software->getKey());

        $this->assertSame($software->getKey(), $index->fresh()->menu_software_id);
        $component->assertSee('Xtracker');

        $component->call('updateSoftware', $index->getKey(), null);

        $this->assertNull($index->fresh()->menu_software_id);
    }

    public function test_an_entry_can_point_at_an_individual(): void
    {
        $issue = $this->issue();
        $index = MagazineIndex::factory()->create(['magazine_issue_id' => $issue->getKey()]);
        $individual = Individual::factory()->create(['name' => 'Jochen Hippel']);

        $component = Livewire::test(MagazineIndexComponent::class, ['issue' => $issue])
            ->call('updateIndividual', $index->getKey(), $individual->getKey());

        $this->assertSame($individual->getKey(), $index->fresh()->individual_id);
        $component->assertSee('Jochen Hippel');

        $component->call('updateIndividual', $index->getKey(), null);

        $this->assertNull($index->fresh()->individual_id);
    }

    /**
     * The index is entered in whatever order the pages are typed in, so the
     * auto-sort checkbox is the only thing putting it in page order - it must
     * reorder the rows on screen without touching the rows themselves.
     */
    public function test_auto_sort_orders_the_rows_by_page(): void
    {
        $issue = $this->issue();

        MagazineIndex::factory()->create([
            'magazine_issue_id' => $issue->getKey(),
            'title'             => 'Late article',
            'page'              => 90,
        ]);
        MagazineIndex::factory()->create([
            'magazine_issue_id' => $issue->getKey(),
            'title'             => 'Early article',
            'page'              => 5,
        ]);

        Livewire::test(MagazineIndexComponent::class, ['issue' => $issue])
            ->assertSeeInOrder(['Late article', 'Early article'])
            ->set('sort', true)
            ->assertSeeInOrder(['Early article', 'Late article']);
    }
}
