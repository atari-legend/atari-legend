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
    /**
     * Proxies MP3 songs from sndhrecord.atari.org.
     *
     * Required as sndhrecord.atari.org is served over HTTP and Atari Legend
     * is over HTTPS, causing mix content warnings or even failure to
     * load with Chrome.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Sndh $sndh Song to proxy
     */
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

    /**
     * Generate a square cover image for a game, used in the music player.
     *
     * This resizes the typical 4:3 aspect ratio screenshot into a square
     * canvas that can be used in the music player. It assumes all screenshots
     * are in landscape mode, and takes the width dimension as the new square
     * canvas size.
     *
     * @param  \App\Models\Game $game Game to generate the cover for
     *
     * @return \Illuminate\Http\Response Image as an HTTP response, or 404 if
     *                                   there are no screenshots for the game
     */
    public function cover(Game $game)
    {
        if ($game->screenshots->isNotEmpty()) {
            $path = $game->screenshots->first()->getPath('game');
            $image = ImageManagerStatic::make(Storage::disk('public')->get($path));
            $image->resizeCanvas($image->width(), $image->width(), 'center', false, '#000000');

            return $image->response();
        } else {
            return response('No screenshot for this game', 404)
                ->header('Content-Type', 'text/plain');
        }
    }
}
