<?php

namespace App\Http\Controllers\Admin\Ajax;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function users(Request $request)
    {
        $users = User::select('userid', 'user_id')
            ->orderBy('userid')
            ->limit(10);

        if ($request->filled('q')) {
            $users = $users->where('userid', 'like', '%' . $request->q . '%');
        }

        $results = $users->get();

        return response()->json($results);
    }
}
