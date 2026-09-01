<?php

namespace Tests\Feature\Admin;

use App\Models\Individual;
use App\Models\PubDev;
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

        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_blank_individual_profile_is_stored_as_null(): void
    {
        $this->post(route('admin.games.individuals.store'), [
            'name'    => 'Alice',
            'profile' => '',
            'email'   => '',
        ]);

        $individual = Individual::where('name', 'Alice')->firstOrFail();

        $this->assertNull($individual->profile);
        $this->assertNull($individual->email);
    }

    public function test_whitespace_only_individual_profile_is_stored_as_null(): void
    {
        $this->post(route('admin.games.individuals.store'), [
            'name'    => 'Bob',
            'profile' => "\t\t\t\t",
        ]);

        $this->assertNull(Individual::where('name', 'Bob')->firstOrFail()->profile);
    }

    public function test_a_real_individual_profile_is_kept(): void
    {
        $this->post(route('admin.games.individuals.store'), [
            'name'    => 'Carol',
            'profile' => 'Member in Dune',
        ]);

        $this->assertSame(
            'Member in Dune',
            Individual::where('name', 'Carol')->firstOrFail()->profile
        );
    }

    public function test_clearing_an_individual_profile_stores_null(): void
    {
        $this->post(route('admin.games.individuals.store'), [
            'name'    => 'Dave',
            'profile' => 'Member in Dune',
        ]);

        $individual = Individual::where('name', 'Dave')->firstOrFail();

        $this->put(route('admin.games.individuals.update', $individual), [
            'name'    => 'Dave',
            'profile' => '',
        ]);

        $this->assertNull($individual->fresh()->profile);
    }

    public function test_blank_company_profile_is_stored_as_null(): void
    {
        $this->post(route('admin.games.companies.store'), [
            'name'    => 'Ocean',
            'profile' => "\t\t\t\t",
        ]);

        $this->assertNull(
            PubDev::where('name', 'Ocean')->firstOrFail()->profile
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
            PubDev::where('name', 'Infogrames')->firstOrFail()->profile
        );
    }
}
