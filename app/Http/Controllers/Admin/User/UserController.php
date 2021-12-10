<?php

namespace App\Http\Controllers\Admin\User;

use App\Helpers\UserHelper;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\View\Components\Admin\Crumb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
        $request->validate(UserHelper::validationRules($user));

        $ext = null;
        if ($request->hasFile('avatar')) {
            $avatar = $request->file('avatar');
            $avatar->storeAs('images/user_avatars/', $user->user_id.'.'.$avatar->extension(), 'public');
            $ext = $avatar->extension();
        }

        $user->update([
            'email'        => $request->email,
            'permission'   => $request->permission,
            'avatar_ext'   => $ext,
            'user_website' => $request->website,
            'user_fb'      => $request->facebook,
            'user_twitter' => $request->twitter,
            'user_af'      => $request->forum,
            'inactive'     => $request->active ? '0' : '1',
        ]);

        return redirect()->route('admin.users.users.index');
    }

    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->route('admin.users.users.index');
    }

    public function destroyAvatar(User $user)
    {
        Storage::disk('public')->delete('images/user_avatars/'.$user->user_id.'.'.$user->avatar_ext);
        $user->avatar_ext = null;
        $user->save();

        return redirect()->route('admin.users.users.edit', $user);
    }
}
