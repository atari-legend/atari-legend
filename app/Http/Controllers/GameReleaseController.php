<?php

namespace App\Http\Controllers;

use App\Helpers\GameHelper;
use App\Helpers\JsonLd;
use App\Helpers\ReleaseDescriptionHelper;
use App\Models\GameRelease;
use Illuminate\Support\Str;

class GameReleaseController extends Controller
{
    const FA_MEDIA_TYPE_ICONS = [
        1 => 'far fa-save',
        2 => 'far fa-save',
        3 => 'fas fa-sd-card',
        4 => 'fas fa-cloud-download-alt',
    ];

    public function show(GameRelease $release)
    {
        if ($release->menuDiskContents->isNotEmpty()) {
            // Release on menus should not be visible as standalone pages
            abort(404);
        }

        $boxscans = $release->boxscans
            ->filter(function ($boxscan) {
                return Str::startsWith($boxscan->type, 'Box');
            })
            ->map(function ($boxscan) use ($release) {
                return [
                    'release' => $release,
                    'boxscan' => asset('storage/' . $boxscan->path),
                    'preview' => route('games.releases.boxscan', [
                        'release' => $release,
                        'id'      => $boxscan->getKey(),
                    ]),
                ];
            });

        $jsonLd = (new JsonLd('VideoGame', url()->current()))
            ->add('name', $release->game->name)
            ->add('description', GameHelper::description($release->game))
            ->add('applicationCategory', 'Game')
            ->add('operatingSystem', 'TOS')
            ->add('gamePlatform', 'Atari ST');
        if ($release->game->screenshots->isNotEmpty()) {
            $jsonLd->add('image', $release->game->screenshots->random()->getUrlRoute('game', $release->game));
        }
        if ($release->game->genres->isNotEmpty()) {
            $jsonLd->add('genre', $release->game->genres->pluck('name'));
        }

        return view('games.releases.show')
            ->with([
                'release'        => $release,
                'boxscans'       => $boxscans,
                'descriptions'   => ReleaseDescriptionHelper::descriptions($release),
                'mediaTypeIcons' => GameReleaseController::FA_MEDIA_TYPE_ICONS,
                'jsonLd'         => $jsonLd,
            ]);
    }
}
