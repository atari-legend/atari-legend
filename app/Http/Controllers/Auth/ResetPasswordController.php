<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\UserHelper;
use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\ResetsPasswords;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Set the user's password.
     *
     * @param \Illuminate\Contracts\Auth\CanResetPassword $user
     * @param string                                      $password
     *
     * @return void
     */
    protected function setUserPassword($user, $password)
    {
        $salt = UserHelper::salt();
        $hashedPassword = UserHelper::hashPassword($password, $salt);

        $user->sha512_password = $hashedPassword;
        $user->salt = $salt;
        // Empty old MD5 password
        $user->password = null;
    }
}
