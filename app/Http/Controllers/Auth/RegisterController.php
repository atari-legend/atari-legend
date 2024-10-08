<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\ChangelogHelper;
use App\Helpers\UserHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller implements HasMiddleware
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/';

    public static function middleware(): array
    {
        return ['guest'];
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, array_merge([
            'userid'             => ['required', 'string', 'max:255', 'unique:users'],
            'password'           => ['required', 'string', 'min:8', 'confirmed'],
            'license'            => ['required', 'accepted'],
            'h-captcha-response' => ['required', 'captcha'],
        ], UserHelper::validationRules()));
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        $salt = UserHelper::salt();
        $hashedPassword = UserHelper::hashPassword($data['password'], $salt);

        return User::create([
            'userid'          => $data['userid'],
            'email'           => $data['email'],
            'sha512_password' => $hashedPassword,
            'salt'            => $salt,
            'user_website'    => $data['website'],
            'user_fb'         => $data['facebook'],
            'user_twitter'    => $data['twitter'],
            'user_af'         => $data['af'],
            'permission'      => User::PERMISSION_USER,
            'join_date'       => time(),
            'inactive'        => User::ACTIVE,
        ]);
    }

    /**
     * The user has been registered.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function registered(Request $request, $user)
    {
        ChangelogHelper::insert([
            'action'           => Changelog::INSERT,
            'section'          => 'Users',
            'section_id'       => $user->getKey(),
            'section_name'     => $user->userid,
            'sub_section'      => 'User',
            'sub_section_id'   => $user->getKey(),
            'sub_section_name' => $user->userid,
        ]);
    }
}
