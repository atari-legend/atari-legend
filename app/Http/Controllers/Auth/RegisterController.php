<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\UserHelper;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
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
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param array $data
     *
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
     * @param array $data
     *
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
            'inactive'        => User::INACTIVE,
        ]);
    }
}
