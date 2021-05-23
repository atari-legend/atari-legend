<?php

namespace App\Http\Controllers;

use App\Models\Sndh;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GameMusicController extends Controller
{
    public function music(Request $request, Sndh $sndh)
    {
        $url = 'http://sndhrecord.atari.org/mp3/'.$sndh->id;
        if ($request->subtune && $request->subtune > 0) {
            $url .= '-'.sprintf("%'.03d", $request->subtune);
        }
        $url .= '.mp3';

        $response = Http::get($url);

        return response($response->body(), $response->status(), $response->headers());
    }
}
