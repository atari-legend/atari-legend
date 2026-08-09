<?php

namespace Tests\Feature\Admin;

use App\Models\Changelog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Base class for tests of the admin panel.
 *
 * Every admin test needs the same two things: a signed-in administrator, and a
 * way to say what the changelog should have recorded. Both live here so that
 * the per-controller tests are only about the controller.
 */
abstract class AdminTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->actingAs($this->admin);
    }

    /**
     * Assert that exactly one changelog entry matches, and that it is
     * attributed to the signed-in administrator.
     *
     * Every write path in the admin panel is expected to call
     * `ChangelogHelper::insert()`, and a missing entry is invisible in the UI -
     * the row still saves - so it has to be asserted explicitly.
     *
     * @param  string  $action  One of the Changelog::INSERT/UPDATE/DELETE constants
     * @param  string  $section  Section name, as passed to ChangelogHelper::insert()
     * @param  string|null  $name  Expected section_name, or null to accept any
     */
    protected function assertChangelog(string $action, string $section, ?string $name = null): void
    {
        $entries = Changelog::query()
            ->where('action', $action)
            ->where('section', $section)
            ->when($name !== null, fn ($query) => $query->where('section_name', $name))
            ->get();

        $this->assertCount(
            1,
            $entries,
            "Expected exactly one '{$action}' changelog entry for '{$section}'"
                . ($name === null ? '' : " named '{$name}'")
                . ', found ' . $entries->count() . '.'
        );

        $this->assertSame(
            $this->admin->user_id,
            $entries->first()->user_id,
            'The changelog entry was not attributed to the acting administrator.'
        );
    }

    /**
     * Assert that nothing was written to the changelog. Useful on the failure
     * paths, where a validation error must not leave a trace.
     */
    protected function assertNoChangelog(): void
    {
        $this->assertSame(0, Changelog::query()->count(), 'Expected no changelog entries.');
    }

    /**
     * Assert that a non-administrator is bounced off a route.
     *
     * The admin middleware redirects to '/' rather than returning 403, so a
     * test that only checked for "not 200" would also pass on a 500.
     *
     * @param  string  $url  URL to request, e.g. route('admin.news.news.index')
     * @param  string  $method  HTTP verb to use
     */
    protected function assertNonAdminIsTurnedAway(string $url, string $method = 'get', array $data = []): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->call(strtoupper($method), $url, $data)
            ->assertRedirect('/');

        // Leave the admin signed in, so this can be called mid-test.
        $this->actingAs($this->admin);
    }
}
