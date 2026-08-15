<?php

namespace Tests\Feature\Public;

use App\Helpers\UserHelper;
use App\Models\Changelog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The signed-in user's own profile: their details, their avatar and their
 * password.
 *
 * Both forms answer with the profile view rather than a redirect, so a rejected
 * edit is a redirect back with errors and an accepted one is a 200.
 */
class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private function profile(array $overrides = []): array
    {
        return array_merge([
            'email'    => 'sysop@example.org',
            'website'  => 'https://example.org/',
            'facebook' => 'https://www.facebook.com/sysop',
            'twitter'  => 'https://twitter.com/sysop',
            'af'       => 'https://www.atari-forum.com/memberlist.php',
        ], $overrides);
    }

    /**
     * A user who can actually log in, which the factory alone does not give:
     * the legacy credential columns are only filled once a password is set.
     */
    private function userWithPassword(string $password, array $attributes = []): User
    {
        $salt = UserHelper::salt();

        return User::factory()->create(array_merge([
            'salt'            => $salt,
            'sha512_password' => UserHelper::hashPassword($password, $salt),
        ], $attributes));
    }

    public function test_the_profile_page_shows_the_signed_in_user(): void
    {
        $user = User::factory()->create([
            'userid' => 'sysop',
            'email'  => 'sysop@example.org',
        ]);

        $this->actingAs($user)
            ->get(route('auth.profile'))
            ->assertOk()
            ->assertSee('sysop@example.org');
    }

    public function test_a_guest_is_sent_to_the_login_page(): void
    {
        $this->get(route('auth.profile'))->assertRedirect(route('login'));
    }

    public function test_an_unverified_user_cannot_reach_their_profile(): void
    {
        $this->actingAs(User::factory()->unverified()->create())
            ->get(route('auth.profile'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_a_user_can_update_their_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('auth.update'), $this->profile())
            ->assertOk()
            ->assertSessionHas('alert-title', 'Profile updated');

        $user = $user->fresh();

        $this->assertSame('sysop@example.org', $user->email);
        $this->assertSame('https://example.org/', $user->user_website);
        $this->assertSame('https://www.facebook.com/sysop', $user->user_fb);
        $this->assertSame('https://twitter.com/sysop', $user->user_twitter);
        $this->assertSame('https://www.atari-forum.com/memberlist.php', $user->user_af);
    }

    /**
     * The controller re-seeds the authenticated user from the row it just
     * saved, so the page it answers with already shows the new details rather
     * than lagging a request behind.
     */
    public function test_the_updated_profile_is_shown_straight_away(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('auth.update'), $this->profile())
            ->assertOk()
            ->assertSee('sysop@example.org');
    }

    public function test_updating_the_profile_is_logged_in_the_changelog(): void
    {
        $user = User::factory()->create(['userid' => 'sysop']);

        $this->actingAs($user)->post(route('auth.update'), $this->profile());

        $changelog = Changelog::sole();

        $this->assertSame(Changelog::UPDATE, $changelog->action);
        $this->assertSame('Users', $changelog->section);
        $this->assertSame($user->user_id, $changelog->section_id);
        $this->assertSame('sysop', $changelog->section_name);
        $this->assertSame($user->user_id, $changelog->user_id);
    }

    public function test_the_profile_needs_an_email(): void
    {
        $user = User::factory()->create(['email' => 'sysop@example.org']);

        $this->actingAs($user)
            ->post(route('auth.update'), $this->profile(['email' => null]))
            ->assertSessionHasErrors('email');

        $this->assertSame('sysop@example.org', $user->fresh()->email);
    }

    public function test_the_profile_rejects_social_links_pointing_elsewhere(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('auth.update'), $this->profile([
                'website'  => 'not a url',
                'facebook' => 'https://example.org/sysop',
                'twitter'  => 'https://example.org/sysop',
                'af'       => 'https://example.org/sysop',
            ]))
            ->assertSessionHasErrors(['website', 'facebook', 'twitter', 'af']);

        $this->assertNull($user->fresh()->user_website);
    }

    /**
     * Saving the form without touching the address must not trip the uniqueness
     * rule on the user's own email.
     */
    public function test_a_user_may_keep_their_own_email(): void
    {
        $user = User::factory()->create(['email' => 'sysop@example.org']);

        $this->actingAs($user)
            ->post(route('auth.update'), $this->profile(['email' => 'sysop@example.org']))
            ->assertOk()
            ->assertSessionHasNoErrors();
    }

    public function test_a_user_cannot_take_another_users_email(): void
    {
        User::factory()->create(['email' => 'someone@example.org']);
        $user = User::factory()->create(['email' => 'sysop@example.org']);

        $this->actingAs($user)
            ->post(route('auth.update'), $this->profile(['email' => 'someone@example.org']))
            ->assertSessionHasErrors('email');

        $this->assertSame('sysop@example.org', $user->fresh()->email);
    }

    public function test_a_user_can_upload_an_avatar(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('auth.update'), $this->profile([
                'avatar' => UploadedFile::fake()->image('me.png'),
            ]))
            ->assertOk();

        $this->assertSame('png', $user->fresh()->avatar_ext);
        Storage::disk('public')->assertExists('images/user_avatars/' . $user->user_id . '.png');
    }

    public function test_an_avatar_must_be_an_image(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('auth.update'), $this->profile([
                'avatar' => UploadedFile::fake()->create('me.txt', 1, 'text/plain'),
            ]))
            ->assertSessionHasErrors('avatar');

        $this->assertNull($user->fresh()->avatar_ext);
    }

    public function test_a_user_can_remove_their_avatar(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['avatar_ext' => 'png']);
        Storage::disk('public')->put('images/user_avatars/' . $user->user_id . '.png', 'an image');

        $this->actingAs($user)
            ->post(route('auth.update'), $this->profile(['avatar-removed' => '1']))
            ->assertOk();

        $this->assertNull($user->fresh()->avatar_ext);
        Storage::disk('public')->assertMissing('images/user_avatars/' . $user->user_id . '.png');
    }

    public function test_a_user_can_change_their_password(): void
    {
        $user = $this->userWithPassword('the-old-password');
        $salt = $user->salt;

        $this->actingAs($user)
            ->post(route('auth.password'), [
                'password_current'      => 'the-old-password',
                'password'              => 'the-new-password',
                'password_confirmation' => 'the-new-password',
            ])
            ->assertOk()
            ->assertSessionHas('alert-title', 'Password changed');

        $user = $user->fresh();

        $this->assertNotSame($salt, $user->salt);
        $this->assertSame(
            UserHelper::hashPassword('the-new-password', $user->salt),
            $user->sha512_password
        );
    }

    public function test_the_new_password_works_at_the_login_form(): void
    {
        $user = $this->userWithPassword('the-old-password', ['userid' => 'sysop']);

        $this->actingAs($user)->post(route('auth.password'), [
            'password_current'      => 'the-old-password',
            'password'              => 'the-new-password',
            'password_confirmation' => 'the-new-password',
        ]);

        $this->post(route('logout'));

        $this->post(route('login'), ['userid' => 'sysop', 'password' => 'the-new-password']);

        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_the_current_password_must_be_right(): void
    {
        $user = $this->userWithPassword('the-old-password');
        $hash = $user->sha512_password;

        $this->actingAs($user)
            ->post(route('auth.password'), [
                'password_current'      => 'guessing',
                'password'              => 'the-new-password',
                'password_confirmation' => 'the-new-password',
            ])
            ->assertSessionHasErrors('password_current');

        $this->assertSame($hash, $user->fresh()->sha512_password);
    }

    public function test_the_new_password_must_be_confirmed(): void
    {
        $user = $this->userWithPassword('the-old-password');
        $hash = $user->sha512_password;

        $this->actingAs($user)
            ->post(route('auth.password'), [
                'password_current'      => 'the-old-password',
                'password'              => 'the-new-password',
                'password_confirmation' => 'something-else',
            ])
            ->assertSessionHasErrors('password');

        $this->assertSame($hash, $user->fresh()->sha512_password);
    }

    public function test_the_new_password_must_be_at_least_eight_characters(): void
    {
        $user = $this->userWithPassword('the-old-password');
        $hash = $user->sha512_password;

        $this->actingAs($user)
            ->post(route('auth.password'), [
                'password_current'      => 'the-old-password',
                'password'              => 'short',
                'password_confirmation' => 'short',
            ])
            ->assertSessionHasErrors('password');

        $this->assertSame($hash, $user->fresh()->sha512_password);
    }

    public function test_changing_the_password_is_logged_in_the_changelog(): void
    {
        $user = $this->userWithPassword('the-old-password', ['userid' => 'sysop']);

        $this->actingAs($user)->post(route('auth.password'), [
            'password_current'      => 'the-old-password',
            'password'              => 'the-new-password',
            'password_confirmation' => 'the-new-password',
        ]);

        $changelog = Changelog::sole();

        $this->assertSame(Changelog::UPDATE, $changelog->action);
        $this->assertSame('Users', $changelog->section);
        $this->assertSame($user->user_id, $changelog->section_id);
        $this->assertSame('sysop', $changelog->section_name);
    }

    public function test_a_guest_cannot_change_a_password(): void
    {
        $this->post(route('auth.password'), [
            'password_current'      => 'the-old-password',
            'password'              => 'the-new-password',
            'password_confirmation' => 'the-new-password',
        ])->assertRedirect(route('login'));
    }

    public function test_the_current_password_is_required(): void
    {
        $user = $this->userWithPassword('the-old-password');

        $this->actingAs($user)
            ->post(route('auth.password'), [
                'password'              => 'the-new-password',
                'password_confirmation' => 'the-new-password',
            ])
            ->assertSessionHasErrors('password_current');
    }

    public function test_a_legacy_user_with_null_salt_cannot_change_password_and_is_told_to_reset(): void
    {
        $user = User::factory()->create([
            'salt'            => null,
            'sha512_password' => null,
        ]);

        $this->actingAs($user)
            ->post(route('auth.password'), [
                'password_current'      => 'any-password',
                'password'              => 'the-new-password',
                'password_confirmation' => 'the-new-password',
            ])
            ->assertSessionHasErrors([
                'password_current' => "Your account uses a legacy password scheme. Please use the 'Forgot Your Password?' link on the login page to reset your password.",
            ]);
    }
}
