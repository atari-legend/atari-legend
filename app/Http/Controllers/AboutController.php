<?php

namespace App\Http\Controllers;

use App\Andreas;

class AboutController extends Controller
{
    public function index()
    {
        return view('about.index');
    }

    public function andreas()
    {
        $comments = Andreas::all()
            ->sortByDesc('timestamp');

        return view('about.andreas')
            ->with([
                'comments'  => $comments,
            ]);
    }
}
