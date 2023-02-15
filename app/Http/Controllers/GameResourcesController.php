<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Support\Facades\Storage;

class GameResourcesController extends Controller
{
    public function screenshot(Game $game, int $id, string $ext)
    {
        $screenshot = $game->screenshots->first(function ($s) use ($id, $ext) {
            return $s->getKey() === $id && $s->imgext === $ext;
        });
        if ($screenshot) {
            return response()->file(Storage::path('public/' . $screenshot->getPath('game')));
        } else {
            abort('404');
        }
    }
}
