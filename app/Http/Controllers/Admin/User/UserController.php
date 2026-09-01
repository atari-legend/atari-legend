<?php

namespace App\Http\Controllers\Admin\User;

use App\Helpers\ChangelogHelper;
use App\Helpers\UserHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
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

        // Keep the avatar the user already has when the form comes back without
        // a file - which is every save that is not about the avatar. The field
        // says "Select a file to replace the current avatar", and there is a
        // separate route for deleting one, so overwriting it with null here
        // dropped an avatar every time an admin edited an e-mail address.
        $ext = $user->avatar_ext;
        if ($request->hasFile('avatar')) {
            $avatar = $request->file('avatar');
            $avatar->storeAs('images/user_avatars/', $user->getKey() . '.' . $avatar->extension(), 'public');
            $ext = $avatar->extension();
        }

        $user->update([
            'email'       => $request->email,
            'permission'  => $request->permission,
            'avatar_ext'  => $ext,
            'website'     => $request->website,
            'facebook'    => $request->facebook,
            'twitter'     => $request->twitter,
            'atari_forum' => $request->af,
            'inactive'    => $request->active ? '0' : '1',
        ]);

        ChangelogHelper::insert([
            'action'           => Changelog::UPDATE,
            'section'          => 'Users',
            'section_id'       => $user->getKey(),
            'section_name'     => $user->userid,
            'sub_section'      => 'User',
            'sub_section_id'   => $user->getKey(),
            'sub_section_name' => $user->userid,
        ]);

        return redirect()->route('admin.users.users.index');
    }

    public function destroy(Request $request, User $user)
    {
        // The hidden button in the users table is an affordance, not a
        // boundary: a hand-crafted request has to be refused here, or it
        // reaches a foreign-key error page. It is not an authorisation failure
        // and the user is not missing, so this is a refused write rather than
        // a 403 or a 404, the same way Games/GameController::destroy() does it.
        if (! $user->is_deletable) {
            $request->session()->flash(
                'alert-danger',
                "'{$user->userid}' cannot be deleted while they still hold a game submission or a dump."
            );

            return redirect()->route('admin.users.users.index');
        }

        $user->delete();

        ChangelogHelper::insert([
            'action'           => Changelog::DELETE,
            'section'          => 'Users',
            'section_id'       => $user->getKey(),
            'section_name'     => $user->userid,
            'sub_section'      => 'User',
            'sub_section_id'   => $user->getKey(),
            'sub_section_name' => $user->userid,
        ]);

        return redirect()->route('admin.users.users.index');
    }

    public function destroyAvatar(User $user)
    {
        Storage::disk('public')->delete('images/user_avatars/' . $user->getKey() . '.' . $user->avatar_ext);
        $user->avatar_ext = null;
        $user->save();

        ChangelogHelper::insert([
            'action'           => Changelog::UPDATE,
            'section'          => 'Users',
            'section_id'       => $user->getKey(),
            'section_name'     => $user->userid,
            'sub_section'      => 'User',
            'sub_section_id'   => $user->getKey(),
            'sub_section_name' => $user->userid,
        ]);

        return redirect()->route('admin.users.users.edit', $user);
    }
}
