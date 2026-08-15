<?php

namespace Tests\Feature\Public;

use App\Helpers\UserHelper;
use App\Models\Changelog;
use App\Models\User;
use Buzz\LaravelHCaptcha\HttpClientContract;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Registration, login, email verification and password resets - everything
 * `Auth::routes(['verify' => true])` wires up.
 *
 * Two legacy details shape most of these tests: users log in with their
 * username rather than their email, and credentials are a sha512 hash with a
 * per-user salt rather than a bcrypt `password`.
 */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The registration form is behind hCaptcha, whose `captcha` validation rule
     * posts to hcaptcha.com through a Guzzle client of its own - `Http::fake()`
     * never sees it. Swapping the package's HTTP client in the container is the
     * only seam, and it lets a test decide whether the captcha passes.
     */
    private function fakeCaptcha(bool $passes = true): void
    {
        config(['captcha.http_client' => HttpClientContract::class]);

        $this->mock(HttpClientContract::class)
            ->shouldReceive('post')
            ->andReturn(['success' => $passes]);
    }

    private function registration(array $overrides = []): array
    {
        return array_merge([
            'userid'                => 'sysop',
            'email'                 => 'sysop@example.org',
            'password'              => 'a-good-password',
            'password_confirmation' => 'a-good-password',
            'website'               => null,
            'facebook'              => null,
            'twitter'               => null,
            'af'                    => null,
            'license'               => 'on',
            'h-captcha-response'    => 'a-token',
        ], $overrides);
    }

    /**
     * A user who can actually log in, which the factory alone does not give:
     * the legacy columns are only filled once a password is set.
     */
    private function userWithPassword(string $password, array $attributes = []): User
    {
        $salt = UserHelper::salt();

        return User::factory()->create(array_merge([
            'salt'            => $salt,
            'sha512_password' => UserHelper::hashPassword($password, $salt),
        ], $attributes));
    }

    private function verificationUrl(User $user): string
    {
        return URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id'   => $user->getKey(),
            'hash' => sha1($user->getEmailForVerification()),
        ]);
    }

    public function test_the_registration_form_loads(): void
    {
        $this->get(route('register'))->assertOk()->assertSee('Register');
    }

    public function test_a_visitor_can_register(): void
    {
        Notification::fake();
        $this->fakeCaptcha();

        $this->post(route('register'), $this->registration([
            'website' => 'https://example.org/',
        ]))->assertRedirect('/');

        $user = User::sole();

        $this->assertSame('sysop', $user->userid);
        $this->assertSame('sysop@example.org', $user->email);
        $this->assertSame('https://example.org/', $user->user_website);
        $this->assertSame(User::PERMISSION_USER, $user->permission);
        $this->assertSame(User::ACTIVE, $user->inactive);
        $this->assertAuthenticatedAs($user);
    }

    public function test_a_visitor_can_register_with_omitted_optional_fields(): void
    {
        Notification::fake();
        $this->fakeCaptcha();

        $data = $this->registration();
        unset($data['website'], $data['facebook'], $data['twitter'], $data['af']);

        $this->post(route('register'), $data)->assertRedirect('/');

        $user = User::sole();

        $this->assertSame('sysop', $user->userid);
        $this->assertSame('sysop@example.org', $user->email);
        $this->assertNull($user->user_website);
        $this->assertNull($user->user_fb);
        $this->assertNull($user->user_twitter);
        $this->assertNull($user->user_af);
        $this->assertAuthenticatedAs($user);
    }

    /**
     * The password is stored under the legacy scheme, never in plain text and
     * never reusing another user's salt.
     */
    public function test_registration_stores_a_salted_password(): void
    {
        $this->fakeCaptcha();

        $this->post(route('register'), $this->registration())->assertRedirect('/');

        $user = User::sole();

        $this->assertNotEmpty($user->salt);
        $this->assertSame(
            UserHelper::hashPassword('a-good-password', $user->salt),
            $user->sha512_password
        );
    }

    public function test_registration_sends_the_verification_mail(): void
    {
        Notification::fake();
        $this->fakeCaptcha();

        $this->post(route('register'), $this->registration());

        Notification::assertSentTo(User::sole(), VerifyEmail::class);
    }

    public function test_registration_is_logged_in_the_changelog(): void
    {
        $this->fakeCaptcha();

        $this->post(route('register'), $this->registration());

        $user = User::sole();
        $changelog = Changelog::sole();

        $this->assertSame(Changelog::INSERT, $changelog->action);
        $this->assertSame('Users', $changelog->section);
        $this->assertSame($user->user_id, $changelog->section_id);
        $this->assertSame('sysop', $changelog->section_name);
        $this->assertSame($user->user_id, $changelog->user_id);
    }

    public function test_registration_needs_a_username_an_email_and_a_password(): void
    {
        $this->fakeCaptcha();

        $this->post(route('register'), [])
            ->assertSessionHasErrors(['userid', 'email', 'password', 'license']);

        $this->assertSame(0, User::query()->count());
    }

    public function test_registration_needs_a_confirmed_password_of_at_least_eight_characters(): void
    {
        $this->fakeCaptcha();

        $this->post(route('register'), $this->registration([
            'password'              => 'short',
            'password_confirmation' => 'short',
        ]))->assertSessionHasErrors('password');

        $this->post(route('register'), $this->registration([
            'password_confirmation' => 'something-else',
        ]))->assertSessionHasErrors('password');

        $this->assertSame(0, User::query()->count());
    }

    public function test_registration_rejects_a_username_already_taken(): void
    {
        $this->fakeCaptcha();
        User::factory()->create(['userid' => 'sysop']);

        $this->post(route('register'), $this->registration())
            ->assertSessionHasErrors('userid');

        $this->assertSame(1, User::query()->count());
    }

    public function test_registration_rejects_an_email_already_taken(): void
    {
        $this->fakeCaptcha();
        User::factory()->create(['email' => 'sysop@example.org']);

        $this->post(route('register'), $this->registration(['userid' => 'someone-else']))
            ->assertSessionHasErrors('email');

        $this->assertSame(1, User::query()->count());
    }

    /**
     * The profile rules are merged into the registration ones, so the social
     * links are checked at sign-up too.
     */
    public function test_registration_checks_the_social_links(): void
    {
        $this->fakeCaptcha();

        $this->post(route('register'), $this->registration([
            'facebook' => 'https://example.org/sysop',
        ]))->assertSessionHasErrors('facebook');

        $this->assertSame(0, User::query()->count());
    }

    public function test_registration_needs_the_license_to_be_accepted(): void
    {
        $this->fakeCaptcha();

        $this->post(route('register'), $this->registration(['license' => null]))
            ->assertSessionHasErrors('license');

        $this->assertSame(0, User::query()->count());
    }

    public function test_registration_needs_the_captcha_to_pass(): void
    {
        $this->fakeCaptcha(passes: false);

        $this->post(route('register'), $this->registration())
            ->assertSessionHasErrors('h-captcha-response');

        $this->assertSame(0, User::query()->count());
    }

    public function test_a_signed_in_user_is_sent_away_from_the_registration_form(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('register'))
            ->assertRedirect('/');
    }

    public function test_the_login_form_loads(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Login');
    }

    public function test_a_user_can_log_in_with_their_username(): void
    {
        $user = $this->userWithPassword('a-good-password', ['userid' => 'sysop']);

        $this->post(route('login'), [
            'userid'   => 'sysop',
            'password' => 'a-good-password',
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_rejects_a_wrong_password(): void
    {
        $this->userWithPassword('a-good-password', ['userid' => 'sysop']);

        $this->from(route('login'))
            ->post(route('login'), ['userid' => 'sysop', 'password' => 'guessing'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('userid');

        $this->assertGuest();
    }

    public function test_login_rejects_an_unknown_user(): void
    {
        $this->from(route('login'))
            ->post(route('login'), ['userid' => 'nobody', 'password' => 'a-good-password'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('userid');

        $this->assertGuest();
    }

    public function test_login_rejects_legacy_account_with_null_salt_gracefully(): void
    {
        User::factory()->create([
            'userid'          => 'legacy_user',
            'salt'            => null,
            'sha512_password' => null,
        ]);

        $this->from(route('login'))
            ->post(route('login'), ['userid' => 'legacy_user', 'password' => 'a-good-password'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('userid');

        $this->assertGuest();
    }

    /**
     * `inactive` is the legacy site's way of disabling an account, and the
     * custom user provider honours it even when the password is right.
     */
    public function test_an_inactive_account_cannot_log_in(): void
    {
        $this->userWithPassword('a-good-password', [
            'userid'   => 'sysop',
            'inactive' => User::INACTIVE,
        ]);

        $this->post(route('login'), ['userid' => 'sysop', 'password' => 'a-good-password'])
            ->assertSessionHasErrors('userid');

        $this->assertGuest();
    }

    public function test_a_user_can_log_out(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('logout'))
            ->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_an_unverified_user_is_held_back(): void
    {
        $this->actingAs(User::factory()->unverified()->create())
            ->get(route('home.index'))
            ->assertRedirect(route('verification.notice'));
    }

    /**
     * `inactive` counts as unverified too, so an account the legacy site
     * disabled is held at the same door even with a verified email.
     */
    public function test_an_inactive_user_is_held_back(): void
    {
        $this->actingAs(User::factory()->create(['inactive' => User::INACTIVE]))
            ->get(route('home.index'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_the_verification_notice_is_shown(): void
    {
        $this->actingAs(User::factory()->unverified()->create())
            ->get(route('verification.notice'))
            ->assertOk()
            ->assertSee('Verify Your Email Address');
    }

    public function test_a_signed_link_verifies_the_email(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get($this->verificationUrl($user))
            ->assertRedirect('/');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->get(route('home.index'))->assertOk();
    }

    public function test_an_unsigned_verification_link_is_rejected(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('verification.verify', [
                'id'   => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ]))
            ->assertForbidden();

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_a_verification_link_for_another_email_is_rejected(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(URL::temporarySignedRoute('verification.verify', now()->addHour(), [
                'id'   => $user->getKey(),
                'hash' => sha1('someone.else@example.org'),
            ]))
            ->assertForbidden();

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_a_guest_cannot_verify_an_email(): void
    {
        $user = User::factory()->unverified()->create();

        $this->get($this->verificationUrl($user))->assertRedirect(route('login'));

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_the_verification_mail_can_be_resent(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->post(route('verification.resend'))
            ->assertRedirect();

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_the_forgotten_password_form_loads(): void
    {
        $this->get(route('password.request'))->assertOk()->assertSee('Reset Password');
    }

    public function test_a_reset_link_is_sent(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'sysop@example.org']);

        $this->post(route('password.email'), ['email' => 'sysop@example.org'])
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_no_reset_link_is_sent_to_an_unknown_email(): void
    {
        Notification::fake();

        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'nobody@example.org'])
            ->assertSessionHasErrors('email');

        Notification::assertNothingSent();
    }

    public function test_the_reset_form_loads_with_a_token(): void
    {
        $this->get(route('password.reset', ['token' => 'a-token']))->assertOk();
    }

    /**
     * A reset rewrites the legacy credentials: a fresh salt, a matching hash,
     * and the older MD5 password blanked out.
     */
    public function test_a_valid_token_changes_the_password(): void
    {
        $user = $this->userWithPassword('the-old-password', ['email' => 'sysop@example.org']);
        $token = Password::broker()->createToken($user);

        $this->post(route('password.update'), [
            'token'                 => $token,
            'email'                 => 'sysop@example.org',
            'password'              => 'the-new-password',
            'password_confirmation' => 'the-new-password',
        ])->assertRedirect('/');

        $user = $user->fresh();

        $this->assertSame(
            UserHelper::hashPassword('the-new-password', $user->salt),
            $user->sha512_password
        );
        $this->assertNull($user->password);
    }

    public function test_the_new_password_works_at_the_login_form(): void
    {
        $user = $this->userWithPassword('the-old-password', [
            'userid' => 'sysop',
            'email'  => 'sysop@example.org',
        ]);

        $this->post(route('password.update'), [
            'token'                 => Password::broker()->createToken($user),
            'email'                 => 'sysop@example.org',
            'password'              => 'the-new-password',
            'password_confirmation' => 'the-new-password',
        ]);

        $this->post(route('logout'));

        $this->post(route('login'), ['userid' => 'sysop', 'password' => 'the-old-password'])
            ->assertSessionHasErrors('userid');
        $this->assertGuest();

        $this->post(route('login'), ['userid' => 'sysop', 'password' => 'the-new-password']);
        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_an_invalid_token_does_not_change_the_password(): void
    {
        $user = $this->userWithPassword('the-old-password', ['email' => 'sysop@example.org']);
        $hash = $user->sha512_password;

        $this->from(route('password.reset', ['token' => 'not-a-token']))
            ->post(route('password.update'), [
                'token'                 => 'not-a-token',
                'email'                 => 'sysop@example.org',
                'password'              => 'the-new-password',
                'password_confirmation' => 'the-new-password',
            ])
            ->assertSessionHasErrors('email');

        $this->assertSame($hash, $user->fresh()->sha512_password);
        $this->assertGuest();
    }

    public function test_a_reset_needs_a_confirmed_password_of_at_least_eight_characters(): void
    {
        $user = $this->userWithPassword('the-old-password', ['email' => 'sysop@example.org']);
        $hash = $user->sha512_password;

        $this->post(route('password.update'), [
            'token'                 => Password::broker()->createToken($user),
            'email'                 => 'sysop@example.org',
            'password'              => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');

        $this->assertSame($hash, $user->fresh()->sha512_password);
    }
}
