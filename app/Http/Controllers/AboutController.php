<?php

namespace App\Http\Controllers;

use App\Models\Andreas;

class AboutController extends Controller
{
    public function index()
    {
        return view('about.index');
    }

    public function andreas()
    {
        $comments = Andreas::all()
            ->sortByDesc('created_at');

        return view('about.andreas')
            ->with([
                'comments'  => $comments,
            ]);
    }
}
