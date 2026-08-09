<?php

namespace Tests\Feature\Admin;

use App\Models\Individual;
use App\Models\PublisherDeveloper;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A blank profile must reach the database as NULL, otherwise "has a bio" stops
 * being answerable — see 2026_08_09_100000_normalise_blank_profiles.
 */
class BlankProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create([
            'permission' => User::PERMISSION_ADMIN,
            'inactive'   => User::ACTIVE,
        ]));
    }

    public function test_blank_individual_profile_is_stored_as_null(): void
    {
        $this->post(route('admin.games.individuals.store'), [
            'name'    => 'Alice',
            'profile' => '',
            'email'   => '',
        ]);

        $text = Individual::where('ind_name', 'Alice')->firstOrFail()->text;

        $this->assertNull($text->ind_profile);
        $this->assertNull($text->ind_email);
    }

    public function test_whitespace_only_individual_profile_is_stored_as_null(): void
    {
        $this->post(route('admin.games.individuals.store'), [
            'name'    => 'Bob',
            'profile' => "\t\t\t\t",
        ]);

        $this->assertNull(Individual::where('ind_name', 'Bob')->firstOrFail()->text->ind_profile);
    }

    public function test_a_real_individual_profile_is_kept(): void
    {
        $this->post(route('admin.games.individuals.store'), [
            'name'    => 'Carol',
            'profile' => 'Member in Dune',
        ]);

        $this->assertSame(
            'Member in Dune',
            Individual::where('ind_name', 'Carol')->firstOrFail()->text->ind_profile
        );
    }

    public function test_clearing_an_individual_profile_stores_null(): void
    {
        $this->post(route('admin.games.individuals.store'), [
            'name'    => 'Dave',
            'profile' => 'Member in Dune',
        ]);

        $individual = Individual::where('ind_name', 'Dave')->firstOrFail();

        $this->put(route('admin.games.individuals.update', $individual), [
            'name'    => 'Dave',
            'profile' => '',
        ]);

        $this->assertNull($individual->fresh()->text->ind_profile);
    }

    public function test_blank_company_profile_is_stored_as_null(): void
    {
        $this->post(route('admin.games.companies.store'), [
            'name'    => 'Ocean',
            'profile' => "\t\t\t\t",
        ]);

        $this->assertNull(
            PublisherDeveloper::where('pub_dev_name', 'Ocean')->firstOrFail()->text->pub_dev_profile
        );
    }

    public function test_a_real_company_profile_is_kept(): void
    {
        $this->post(route('admin.games.companies.store'), [
            'name'    => 'Infogrames',
            'profile' => 'Founded in 1983',
        ]);

        $this->assertSame(
            'Founded in 1983',
            PublisherDeveloper::where('pub_dev_name', 'Infogrames')->firstOrFail()->text->pub_dev_profile
        );
    }
}
