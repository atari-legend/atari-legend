<?php

namespace Tests\Feature\Admin\Magazines;

use App\Models\Changelog;
use App\Models\Location;
use App\Models\Magazine;
use App\Models\MagazineIndex;
use App\Models\MagazineIndexType;
use App\Models\MagazineIssue;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Admin\AdminTestCase;

/**
 * The magazines section: the magazines themselves, their issues - which carry a
 * cover image, either uploaded or pulled from archive.org - and the reference
 * list of index entry types.
 */
class MagazinesTest extends AdminTestCase
{
    // Magazines

    public function test_the_magazine_screens_render(): void
    {
        $location = Location::factory()->create(['name' => 'United Kingdom']);
        $magazine = Magazine::factory()->create([
            'name'        => 'ST Format',
            'location_id' => $location->getKey(),
        ]);
        MagazineIssue::factory()->create(['magazine_id' => $magazine->getKey(), 'issue' => 12]);

        $this->get(route('admin.magazines.magazines.index'))
            ->assertOk()
            ->assertSee('Magazines');

        $this->get(route('admin.magazines.magazines.create'))
            ->assertOk()
            ->assertSee('Create magazine')
            ->assertSee('United Kingdom');

        $this->get(route('admin.magazines.magazines.edit', $magazine))
            ->assertOk()
            ->assertSee('ST Format')
            ->assertSee('United Kingdom');
    }

    public function test_a_magazine_can_be_created_with_a_country_of_origin(): void
    {
        $location = Location::factory()->create(['name' => 'United Kingdom']);

        $this->post(route('admin.magazines.magazines.store'), [
            'name'     => 'ST Format',
            'location' => $location->getKey(),
        ])->assertRedirect(route('admin.magazines.magazines.edit', Magazine::sole()));

        $magazine = Magazine::sole();

        $this->assertSame('ST Format', $magazine->name);
        $this->assertSame($location->getKey(), $magazine->location_id);
        $this->assertChangelog(Changelog::INSERT, 'Magazines', 'ST Format');
    }

    /**
     * The country is optional, and the empty option in the form posts an empty
     * string - which has to dissociate the location rather than fail the
     * numeric rule or be stored as 0.
     */
    public function test_a_magazine_country_can_be_cleared(): void
    {
        $magazine = Magazine::factory()->create([
            'name'        => 'ST Format',
            'location_id' => Location::factory()->create()->getKey(),
        ]);

        $this->put(route('admin.magazines.magazines.update', $magazine), [
            'name'     => 'ST Format UK',
            'location' => '',
        ])->assertRedirect(route('admin.magazines.magazines.index'));

        $this->assertSame('ST Format UK', $magazine->fresh()->name);
        $this->assertNull($magazine->fresh()->location_id);

        // The changelog records the name the magazine had before the rename
        $this->assertChangelog(Changelog::UPDATE, 'Magazines', 'ST Format');
    }

    public function test_a_magazine_needs_a_name_and_a_numeric_country(): void
    {
        $magazine = Magazine::factory()->create(['name' => 'ST Format']);

        $this->put(route('admin.magazines.magazines.update', $magazine), ['name' => ''])
            ->assertSessionHasErrors('name');

        $this->put(route('admin.magazines.magazines.update', $magazine), [
            'name'     => 'ST Format',
            'location' => 'United Kingdom',
        ])->assertSessionHasErrors('location');

        $this->assertSame('ST Format', $magazine->fresh()->name);
        $this->assertNoChangelog();
    }

    public function test_a_magazine_can_be_deleted(): void
    {
        $magazine = Magazine::factory()->create(['name' => 'ST Format']);

        $this->delete(route('admin.magazines.magazines.destroy', $magazine))
            ->assertRedirect(route('admin.magazines.magazines.index'));

        $this->assertSame(0, Magazine::query()->count());
        $this->assertChangelog(Changelog::DELETE, 'Magazines', 'ST Format');
    }

    // Issues

    public function test_the_issue_screens_render(): void
    {
        $magazine = Magazine::factory()->create(['name' => 'ST Format']);
        $issue = MagazineIssue::factory()->create([
            'magazine_id' => $magazine->getKey(),
            'issue'       => 12,
            'label'       => 'Christmas special',
        ]);

        $this->get(route('admin.magazines.issues.create', $magazine))
            ->assertOk()
            ->assertSee('Create issue');

        $this->get(route('admin.magazines.issues.edit', [$magazine, $issue]))
            ->assertOk()
            ->assertSee('ST Format 12 Christmas special')
            ->assertSee('Christmas special');
    }

    /**
     * The green "Save" button posts `stay`, which is what decides whether the
     * admin lands back on the issue or on the magazine.
     */
    public function test_an_issue_can_be_created_and_edited(): void
    {
        $magazine = Magazine::factory()->create(['name' => 'ST Format']);

        $this->post(route('admin.magazines.issues.store', $magazine), [
            'issue'          => 12,
            'label'          => 'Christmas special',
            'archiveorg_url' => 'https://archive.org/details/st-format-012/',
            'published'      => '1990-07-01',
            'page_count'     => 132,
            'circulation'    => 50000,
            'stay'           => 'true',
        ])->assertRedirect(route('admin.magazines.issues.edit', [$magazine, MagazineIssue::sole()]));

        $issue = MagazineIssue::sole();

        $this->assertSame(12, $issue->issue);
        $this->assertSame('Christmas special', $issue->label);
        $this->assertSame('https://archive.org/details/st-format-012/', $issue->archiveorg_url);
        $this->assertSame('1990-07-01', $issue->published->toDateString());
        $this->assertSame(132, $issue->page_count);
        $this->assertSame(50000, $issue->circulation);
        $this->assertChangelog(Changelog::INSERT, 'Magazines', 'ST Format');

        $this->put(route('admin.magazines.issues.update', [$magazine, $issue]), [
            'issue'         => 13,
            'alternate_url' => 'https://example.org/st-format-13.pdf',
            'published'     => '1990-08-01',
        ])->assertRedirect(route('admin.magazines.magazines.edit', $magazine));

        $issue->refresh();

        $this->assertSame(13, $issue->issue);
        $this->assertNull($issue->label);
        $this->assertNull($issue->archiveorg_url);
        $this->assertSame('https://example.org/st-format-13.pdf', $issue->alternate_url);
        $this->assertChangelog(Changelog::UPDATE, 'Magazines', 'ST Format');
    }

    public function test_an_issue_rejects_a_bad_url_or_date(): void
    {
        $magazine = Magazine::factory()->create();
        $issue = MagazineIssue::factory()->create([
            'magazine_id' => $magazine->getKey(),
            'issue'       => 12,
        ]);

        $this->put(route('admin.magazines.issues.update', [$magazine, $issue]), [
            'issue'         => 13,
            'alternate_url' => 'not a url',
            'published'     => 'sometime in 1990',
        ])->assertSessionHasErrors(['alternate_url', 'published']);

        $this->assertSame(12, $issue->fresh()->issue);
        $this->assertNoChangelog();
    }

    public function test_a_cover_can_be_uploaded_and_removed(): void
    {
        Storage::fake('public');

        $magazine = Magazine::factory()->create(['name' => 'ST Format']);
        $issue = MagazineIssue::factory()->create([
            'magazine_id' => $magazine->getKey(),
            'issue'       => 12,
            'imgext'      => null,
        ]);

        $this->put(route('admin.magazines.issues.update', [$magazine, $issue]), [
            'issue' => 12,
            'image' => UploadedFile::fake()->image('cover.png'),
        ])->assertRedirect();

        $this->assertSame('png', $issue->fresh()->imgext);
        Storage::disk('public')->assertExists('images/magazine_scans/' . $issue->getKey() . '.png');

        $this->put(route('admin.magazines.issues.update', [$magazine, $issue]), [
            'issue'        => 12,
            'destroyImage' => '1',
        ])->assertRedirect();

        $this->assertNull($issue->fresh()->imgext);
        Storage::disk('public')->assertMissing('images/magazine_scans/' . $issue->getKey() . '.png');
    }

    /**
     * The "Fetch from Archive.org" button reuses the archive.org URL of the
     * issue: the identifier is pulled out of it, and the cover thumbnail is
     * downloaded from the matching download URL. The extension stored on the
     * issue comes from the response's content type, not from the URL.
     */
    public function test_a_cover_can_be_fetched_from_archive_org(): void
    {
        Storage::fake('public');
        Http::fake([
            'archive.org/*' => Http::response('binary-jpeg-data', 200, ['Content-Type' => 'image/jpeg']),
        ]);

        $magazine = Magazine::factory()->create(['name' => 'ST Format']);

        $this->post(route('admin.magazines.issues.store', $magazine), [
            'issue'              => 12,
            'archiveorg_url'     => 'https://archive.org/details/st-format-012/',
            'useArchiveOrgCover' => '1',
        ])->assertRedirect();

        $issue = MagazineIssue::sole();

        Http::assertSent(fn ($request) => $request->url()
            === 'https://archive.org/download/st-format-012/page/cover_w600.jpg');

        $this->assertSame('jpeg', $issue->imgext);
        Storage::disk('public')->assertExists('images/magazine_scans/' . $issue->getKey() . '.jpeg');
        $this->assertSame(
            'binary-jpeg-data',
            Storage::disk('public')->get('images/magazine_scans/' . $issue->getKey() . '.jpeg')
        );
    }

    public function test_an_issue_can_be_deleted(): void
    {
        $magazine = Magazine::factory()->create(['name' => 'ST Format']);
        $issue = MagazineIssue::factory()->create(['magazine_id' => $magazine->getKey()]);

        $this->delete(route('admin.magazines.issues.destroy', [$magazine, $issue]))
            ->assertRedirect(route('admin.magazines.magazines.edit', $magazine));

        $this->assertSame(0, MagazineIssue::query()->count());
        $this->assertChangelog(Changelog::DELETE, 'Magazines', 'ST Format');
    }

    // Index types

    public function test_the_index_types_screen_lists_the_types_and_their_usage(): void
    {
        $type = MagazineIndexType::factory()->named('Cover disk')->create();
        MagazineIndex::factory()->create(['magazine_index_type_id' => $type->getKey()]);

        $this->get(route('admin.magazines.index-types.index'))
            ->assertOk()
            ->assertSee('Cover disk')
            // Seeded by the migration
            ->assertSee('Review')
            ->assertSee('1 index');
    }

    public function test_an_index_type_can_be_created_and_renamed(): void
    {
        $this->post(route('admin.magazines.index-types.store'), ['name' => 'Cover disk'])
            ->assertRedirect(route('admin.magazines.index-types.index'));

        $type = MagazineIndexType::where('name', 'Cover disk')->sole();
        $this->assertChangelog(Changelog::INSERT, 'Magazines', 'Cover disk');

        $this->put(route('admin.magazines.index-types.update', $type), ['name' => 'Coverdisk'])
            ->assertRedirect(route('admin.magazines.index-types.index'));

        $this->assertSame('Coverdisk', $type->fresh()->name);

        // The changelog names the type as it was before the rename
        $this->assertChangelog(Changelog::UPDATE, 'Magazines', 'Cover disk');
    }

    /**
     * Deleting a type leaves the index entries that used it in place, with no
     * type - the foreign key is nulled rather than cascaded, so an accidental
     * delete does not take an issue's index with it.
     *
     * Note: the controller records this as an UPDATE rather than a DELETE; the
     * assertion below describes what it does today.
     */
    public function test_an_index_type_can_be_deleted_without_losing_its_entries(): void
    {
        $type = MagazineIndexType::factory()->named('Cover disk')->create();
        $index = MagazineIndex::factory()->create([
            'magazine_index_type_id' => $type->getKey(),
        ]);

        $this->delete(route('admin.magazines.index-types.destroy', $type))
            ->assertRedirect(route('admin.magazines.index-types.index'));

        $this->assertSame(0, MagazineIndexType::where('name', 'Cover disk')->count());
        $this->assertNotNull($index->fresh());
        $this->assertNull($index->fresh()->magazine_index_type_id);
        $this->assertChangelog(Changelog::UPDATE, 'Magazines', 'Cover disk');
    }

    public function test_an_index_type_needs_a_name(): void
    {
        $type = MagazineIndexType::factory()->named('Cover disk')->create();

        $this->post(route('admin.magazines.index-types.store'), [])
            ->assertSessionHasErrors('name');

        $this->put(route('admin.magazines.index-types.update', $type), ['name' => ''])
            ->assertSessionHasErrors('name');

        $this->assertSame('Cover disk', $type->fresh()->name);
        $this->assertNoChangelog();
    }

    public function test_the_magazines_section_is_closed_to_non_admins(): void
    {
        $magazine = Magazine::factory()->create();

        $this->assertNonAdminIsTurnedAway(route('admin.magazines.magazines.create'));
        $this->assertNonAdminIsTurnedAway(route('admin.magazines.magazines.edit', $magazine));
        $this->assertNonAdminIsTurnedAway(route('admin.magazines.issues.create', $magazine));
        $this->assertNonAdminIsTurnedAway(route('admin.magazines.index-types.index'));
    }
}
