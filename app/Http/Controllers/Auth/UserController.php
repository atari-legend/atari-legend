<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\UserHelper;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function profile()
    {
        return view('auth.profile');
    }

    public function update(Request $request)
    {
        $request->validate(UserHelper::validationRules(Auth::user()));

        $user = User::find(Auth::user()->user_id);
        $user->update([
            'email'        => $request->email,
            'user_website' => $request->website,
            'user_fb'      => $request->facebook,
            'user_twitter' => $request->twitter,
            'user_af'      => $request->af,
        ]);
        $user->save();

        $request->flash();

        $request->session()->flash('alert-title', 'Profile updated');
        $request->session()->flash(
            'alert-success',
            'Your profile has been updated'
        );

        return view('auth.profile');
    }

    public function password(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        $validator->after(function ($validator) use ($request) {
            $hashedPassword = UserHelper::hashPassword($request->password_current, Auth::user()->salt);
            if ($hashedPassword !== Auth::user()->sha512_password) {
                $validator->errors()->add('password_current', 'The password does not match your current password');
            }
        });
        $validator->validate($request);

        $salt = UserHelper::salt();
        $hashedPassword = UserHelper::hashPassword($request->password, $salt);
        $user = User::find(Auth::user()->user_id);
        $user->update([
            'sha512_password' => $hashedPassword,
            'salt'            => $salt,
        ]);
        $user->save();

        $request->session()->flash('alert-title', 'Password changed');
        $request->session()->flash(
            'alert-success',
            'Your password has been changed'
        );

        return view('auth.profile');
    }
}
