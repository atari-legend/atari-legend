<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $users = User::where('join_date', 'regexp', '[0-9]+')
            ->limit(10)
            ->orderBy('join_date', 'desc')
            ->get();

        return view('admin.home.index')
            ->with([
                'users' => $users,
            ]);
    }
}
