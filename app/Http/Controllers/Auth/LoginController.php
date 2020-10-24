<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    /**
     * Name of the 'remember me' cookie used by the legacy site
     * We set it so that logging in to the new site also logs in
     * to the legacy site.
     */
    const LEGACY_SITE_COOKIE_NAME = 'cooksession';

    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * @return string Name of the User model column containing the username
     */
    public function username()
    {
        return 'userid';
    }

    /**
     * The user has been authenticated.
     *
     * @param \Illuminate\Http\Request $request
     * @param mixed                    $user
     *
     * @return mixed
     */
    protected function authenticated(Request $request, $user)
    {
        // Set a cookie used to automatically login in the legacy site
        $cookieValue = bin2hex(random_bytes(16));
        // Do not use the Laravel Cookie facade as it encrypts
        // the cookie, and we need to set the raw value
        setcookie(LoginController::LEGACY_SITE_COOKIE_NAME, $cookieValue, time() + 60 * 60 * 24 * 100);

        // Update current session for that user in the DB, used
        // by the legacy site
        User::where('user_id', $user->user_id)
            ->update(['session' => $cookieValue]);
    }

    /**
     * The user has logged out of the application.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return mixed
     */
    protected function loggedOut(Request $request)
    {
        // Remove session cookie used by the legacy site
        if (isset($_COOKIE[LoginController::LEGACY_SITE_COOKIE_NAME])) {
            setcookie(LoginController::LEGACY_SITE_COOKIE_NAME, null, -1);
        }
    }
}
