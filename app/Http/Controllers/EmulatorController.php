<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class EmulatorController extends Controller
{
    public function index(): View
    {
        return view('emulator.index');
    }
}
