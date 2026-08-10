<?php

namespace Tests\Unit\Helpers;

use App\Helpers\UserHelper;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * The profile form's validation rules, and the legacy password hashing that
 * predates Laravel's own.
 */
class UserHelperValidationTest extends TestCase
{
    use RefreshDatabase;

    private function validate(array $data, ?User $currentUser = null): \Illuminate\Validation\Validator
    {
        return Validator::make($data, UserHelper::validationRules($currentUser));
    }

    public function test_social_links_must_point_at_the_right_site(): void
    {
        $this->assertTrue($this->validate([
            'email'    => 'sysop@example.org',
            'facebook' => 'https://www.facebook.com/atarilegend',
            'twitter'  => 'https://twitter.com/atarilegend',
            'af'       => 'https://www.atari-forum.com/memberlist.php',
        ])->passes());

        $this->assertArrayHasKey('facebook', $this->validate([
            'email'    => 'sysop@example.org',
            'facebook' => 'https://example.org/atarilegend',
        ])->errors()->toArray());
    }

    public function test_social_links_are_optional(): void
    {
        $this->assertTrue($this->validate([
            'email'    => 'sysop@example.org',
            'website'  => null,
            'facebook' => null,
            'twitter'  => null,
            'af'       => null,
        ])->passes());
    }

    public function test_a_website_must_be_a_url(): void
    {
        $this->assertArrayHasKey(
            'website',
            $this->validate(['email' => 'sysop@example.org', 'website' => 'not a url'])->errors()->toArray()
        );
    }

    public function test_an_email_is_required_and_must_be_unique(): void
    {
        User::factory()->create(['email' => 'taken@example.org']);

        $this->assertArrayHasKey('email', $this->validate([])->errors()->toArray());
        $this->assertArrayHasKey(
            'email',
            $this->validate(['email' => 'taken@example.org'])->errors()->toArray()
        );
    }

    /**
     * Editing your own profile must not trip the uniqueness rule on your own
     * address - the reason validationRules() takes a user at all.
     */
    public function test_a_user_may_keep_their_own_email(): void
    {
        $user = User::factory()->create(['email' => 'sysop@example.org']);

        $this->assertTrue($this->validate(['email' => 'sysop@example.org'], $user)->passes());
    }

    public function test_another_users_email_is_still_rejected(): void
    {
        $user = User::factory()->create(['email' => 'sysop@example.org']);
        User::factory()->create(['email' => 'someone@example.org']);

        $this->assertArrayHasKey(
            'email',
            $this->validate(['email' => 'someone@example.org'], $user)->errors()->toArray()
        );
    }

    /**
     * The legacy scheme hashes the password once on its own, then again with
     * the user's salt. Both steps matter: the same password under a different
     * salt must not collide.
     */
    public function test_password_hashing_is_salted(): void
    {
        $hash = UserHelper::hashPassword('secret', 'a-salt');

        $this->assertSame($hash, UserHelper::hashPassword('secret', 'a-salt'));
        $this->assertNotSame($hash, UserHelper::hashPassword('secret', 'another-salt'));
        $this->assertNotSame($hash, UserHelper::hashPassword('different', 'a-salt'));
    }

    public function test_password_hashing_matches_the_legacy_algorithm(): void
    {
        $this->assertSame(
            hash('sha512', hash('sha512', 'secret') . 'a-salt'),
            UserHelper::hashPassword('secret', 'a-salt')
        );
    }

    public function test_salts_are_unique_and_the_right_shape(): void
    {
        $salts = collect(range(1, 20))->map(fn () => UserHelper::salt());

        $this->assertCount(20, $salts->unique());
        foreach ($salts as $salt) {
            $this->assertMatchesRegularExpression('/^[0-9a-f]{128}$/', $salt);
        }
    }
}
