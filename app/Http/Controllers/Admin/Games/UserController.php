<?php

namespace App\Http\Controllers\Admin\Games;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\View\Components\Admin\Crumb;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        return view('admin.users.users.index')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.users.users.index'), 'Users'),
                ],
            ]);
    }

    public function edit(User $user)
    {
        return view('admin.users.users.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.users.users.index'), 'Users'),
                    new Crumb(route('admin.users.users.edit', $user), $user->userid),
                ],
                'user'        => $user,
            ]);
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'email'    => ['required', 'email', Rule::unique('users')->ignore($user)],
            'facebook' => 'nullable|url|starts_with:https://www.facebook.com/',
            'twitter'  => 'nullable|url|starts_with:https://twitter.com/',
            'forum'    => 'nullable|url|starts_with:https://www.atari-forum.com/',
            'website'  => 'nullable|url',
        ]);

        $user->update([
            'email'        => $request->email,
            'permission'   => $request->permission,
            'user_website' => $request->website,
            'user_fb'      => $request->facebook,
            'user_twitter' => $request->twitter,
            'user_af'      => $request->forum,
            'inactive'     => $request->active ? '0' : '1',
        ]);

        return redirect()->route('admin.users.users.edit', $user);
    }

    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->route('admin.users.users.index');
    }
}
