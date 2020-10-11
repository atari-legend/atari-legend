<?php

namespace App\Http\Controllers;

class RobotsController extends Controller
{
    public function index()
    {
        return response()
            ->view('robots')
            ->withHeaders(['Content-Type' => 'text/plain']);
    }
}
