<?php

namespace Tests\Feature\Admin\Menus;

use App\Models\Changelog;
use App\Models\Crew;
use App\Models\Individual;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Admin\AdminTestCase;

/**
 * The crew editor: the groups that released the menus, their history, their
 * logo, and how they nest into each other.
 *
 * The nesting is one table read from both ends - a crew's sub-crews and the
 * crews it is itself part of - so unlinking has two routes onto the same row,
 * and either of them must leave both crews standing.
 */
class MenuCrewTest extends AdminTestCase
{
    public function test_the_crew_list_shows_the_crews(): void
    {
        Crew::factory()->create(['crew_name' => 'The Replicants']);

        $this->get(route('admin.menus.crews.index'))
            ->assertOk()
            ->assertSee('The Replicants');
    }

    public function test_the_create_form_renders_empty(): void
    {
        $this->get(route('admin.menus.crews.create'))
            ->assertOk()
            ->assertSee('Add a new crew');
    }

    public function test_the_edit_form_shows_the_crew_and_its_people(): void
    {
        $crew = Crew::factory()->create([
            'crew_name'    => 'The Replicants',
            'crew_history' => 'Cracking since 1988.',
        ]);
        $individual = Individual::factory()->create(['ind_name' => 'Someone']);

        $this->post(route('admin.menus.crews.addIndividual', $crew), [
            'individual' => $individual->getKey(),
        ])->assertRedirect();

        $this->get(route('admin.menus.crews.edit', $crew))
            ->assertOk()
            ->assertSee('The Replicants')
            ->assertSee('Cracking since 1988.')
            ->assertSee('Someone');
    }

    public function test_a_crew_can_be_renamed_and_given_a_history(): void
    {
        $crew = Crew::factory()->create(['crew_name' => 'The Replicants']);

        $this->put(route('admin.menus.crews.update', $crew), [
            'name'    => 'Replicants',
            'history' => 'Cracking since 1988.',
        ])->assertRedirect(route('admin.menus.crews.edit', $crew));

        $crew->refresh();

        $this->assertSame('Replicants', $crew->crew_name);
        $this->assertSame('Cracking since 1988.', $crew->crew_history);

        $this->assertChangelog(Changelog::UPDATE, 'Crew', 'The Replicants');
        $this->assertSame($crew->getKey(), Changelog::where('action', Changelog::UPDATE)->sole()->section_id);
    }

    public function test_a_crew_cannot_be_renamed_to_nothing(): void
    {
        $crew = Crew::factory()->create(['crew_name' => 'The Replicants']);

        $this->put(route('admin.menus.crews.update', $crew), ['name' => ''])
            ->assertSessionHasErrors('name');

        $this->assertSame('The Replicants', $crew->fresh()->crew_name);
        $this->assertNoChangelog();
    }

    public function test_a_crew_can_be_deleted(): void
    {
        $crew = Crew::factory()->create(['crew_name' => 'The Replicants']);

        $this->delete(route('admin.menus.crews.destroy', $crew))
            ->assertRedirect(route('admin.menus.crews.index'));

        $this->assertSame(0, Crew::query()->count());
        $this->assertChangelog(Changelog::DELETE, 'Crew', 'The Replicants');
    }

    /**
     * The same link can be broken from the child's page as from the parent's.
     * Only the link goes: both crews - and the child's own sub-crews - survive,
     * which is the part that would be easy to get wrong.
     */
    public function test_a_crew_can_be_detached_from_its_parent_without_being_deleted(): void
    {
        $parent = Crew::factory()->create(['crew_name' => 'The Replicants']);
        $child = Crew::factory()->create(['crew_name' => 'Replicants Junior']);
        $grandChild = Crew::factory()->create(['crew_name' => 'Replicants Minor']);

        $this->post(route('admin.menus.crews.addSubCrew', $parent), ['subcrew' => $child->getKey()])
            ->assertRedirect();
        $this->post(route('admin.menus.crews.addSubCrew', $child), ['subcrew' => $grandChild->getKey()])
            ->assertRedirect();

        $this->assertSame(['The Replicants'], $child->fresh()->parentCrews->pluck('crew_name')->all());

        $this->delete(route('admin.menus.crews.removeParentCrew', ['crew' => $child, 'parentCrew' => $parent]))
            ->assertRedirect(route('admin.menus.crews.edit', $child));

        $this->assertCount(0, $child->fresh()->parentCrews);
        $this->assertCount(0, $parent->fresh()->subCrews);
        $this->assertSame(3, Crew::query()->count());
        $this->assertSame(['Replicants Minor'], $child->fresh()->subCrews->pluck('crew_name')->all());

        $this->assertChangelog(Changelog::DELETE, 'Crew', 'Replicants Junior');
    }

    public function test_the_edit_form_lists_the_crews_this_one_is_part_of(): void
    {
        $parent = Crew::factory()->create(['crew_name' => 'The Replicants']);
        $child = Crew::factory()->create(['crew_name' => 'Replicants Junior']);

        $this->post(route('admin.menus.crews.addSubCrew', $parent), ['subcrew' => $child->getKey()])
            ->assertRedirect();

        $this->get(route('admin.menus.crews.edit', $child))
            ->assertOk()
            ->assertSee('Part of')
            ->assertSee('The Replicants');
    }

    /**
     * Both add routes look their argument up by hand, so an id that no longer
     * exists has to fall through quietly rather than blow up on a null.
     */
    public function test_adding_a_member_or_a_sub_crew_that_does_not_exist_does_nothing(): void
    {
        $crew = Crew::factory()->create(['crew_name' => 'The Replicants']);

        $this->post(route('admin.menus.crews.addIndividual', $crew), ['individual' => 12345])
            ->assertRedirect(route('admin.menus.crews.edit', $crew));
        $this->post(route('admin.menus.crews.addSubCrew', $crew), ['subcrew' => 12345])
            ->assertRedirect(route('admin.menus.crews.edit', $crew));

        $this->assertCount(0, $crew->fresh()->individuals);
        $this->assertCount(0, $crew->fresh()->subCrews);
        $this->assertNoChangelog();
    }

    public function test_a_logo_can_be_uploaded_and_removed(): void
    {
        Storage::fake('public');

        $crew = Crew::factory()->create(['crew_name' => 'The Replicants']);

        $this->post(route('admin.menus.crews.storeLogo', $crew), [
            'logo' => UploadedFile::fake()->image('replicants.png'),
        ])->assertRedirect(route('admin.menus.crews.edit', $crew));

        $crew->refresh();

        $this->assertSame('png', $crew->crew_logo);
        Storage::disk('public')->assertExists('images/crew_logos/' . $crew->getKey() . '.png');
        $this->assertChangelog(Changelog::INSERT, 'Crew', 'The Replicants');

        $this->delete(route('admin.menus.crews.destroyLogo', $crew))
            ->assertRedirect(route('admin.menus.crews.edit', $crew));

        $crew->refresh();

        $this->assertNull($crew->crew_logo);
        $this->assertNull($crew->logo_file);
        Storage::disk('public')->assertMissing('images/crew_logos/' . $crew->getKey() . '.png');
        $this->assertChangelog(Changelog::DELETE, 'Crew', 'The Replicants');
    }

    public function test_submitting_the_logo_form_with_no_file_changes_nothing(): void
    {
        Storage::fake('public');

        $crew = Crew::factory()->create(['crew_name' => 'The Replicants']);

        $this->post(route('admin.menus.crews.storeLogo', $crew), [])
            ->assertRedirect(route('admin.menus.crews.edit', $crew));

        $this->assertNull($crew->fresh()->crew_logo);
        $this->assertNoChangelog();
    }

    public function test_the_crew_editor_is_closed_to_non_admins(): void
    {
        $crew = Crew::factory()->create();

        $this->assertNonAdminIsTurnedAway(route('admin.menus.crews.create'));
        $this->assertNonAdminIsTurnedAway(route('admin.menus.crews.edit', $crew));
    }
}
