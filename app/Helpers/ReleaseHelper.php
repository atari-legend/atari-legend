<?php

namespace App\Helpers;

use App\Models\Release;
use App\View\Components\Admin\Crumb;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Helper for Releases.
 */
class ReleaseHelper
{
    /**
     * Get a list of breadcrumbs for sibling releases of a given release.
     *
     * @param \App\Models\Release Release to get the siblings breadcrumbs for.
     * @param string              Route name to use, defaults to the release show page.
     * @return array[] Sibling releases breadcrumps.
     */
    public static function siblingReleasesCrumbs(Release $release,
        string $route = 'admin.games.releases.show')
    {
        return $release->game->releases
            ->sortBy('year')
            ->except($release->getKey())
            ->map(fn ($r) => new Crumb(
                route($route, ['game' => $release->game, 'release' => $r]),
                $r->full_label
            ))
            ->toArray();
    }

    public static function boxScans(Release $release): Collection
    {
        return $release->boxscans
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
    }
}
