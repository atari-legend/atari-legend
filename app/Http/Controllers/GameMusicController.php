<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Sndh;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic;

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

    public function cover(Game $game)
    {
        if ($game->screenshots->isNotEmpty()) {
            $path = 'images/game_screenshots/'.$game->screenshots->first()->file;
            $image = ImageManagerStatic::make(Storage::disk('public')->get($path));
            $image->resizeCanvas($image->width(), $image->width(), 'center', false, '#000000');
            return $image->response();
        } else {
            return response('No screenshot for this game', 404)
                ->header('Content-Type', 'text/plain');
        }
    }
}
