<?php

namespace App\Helpers;

use App\Models\Game;
use Illuminate\Support\Str;

/**
 * Helper for Game.
 */
class GameHelper
{
    /**
     * Generate a description for a game, suitable to use as a HTML description meta tag.
     *
     * @param \App\Models\Game $game Game to generate the description for.
     *
     * @return string The game description.
     */
    public static function description(Game $game)
    {
        $desc = "$game->game_name is a ";

        // Genres
        if ($game->genres->isNotEmpty()) {
            $desc .= $game->genres->implode('name', ', ').' ';
        }

        $desc .= 'game for the Atari ST ';

        // Developers
        if ($game->developers->isNotEmpty()) {
            $desc .= 'developed by '.$game->developers->implode('pub_dev_name', ', ').' ';
        }

        $extraInfo = [];

        // Release (with publishers)
        if ($game->releases->isNotEmpty()) {
            $extraInfo[] = $game->releases->count().' '.Str::plural('release', $game->releases->count());

            $boxscans = $game->releases
                ->flatMap(function ($release) {
                    return $release->boxscans;
                })->count() + $game->boxscans()->count();
            if ($boxscans > 0) {
                $extraInfo[] = $boxscans.' '.Str::plural('boxscan', $boxscans);
            }

            $releases = $game->releases->map(function ($release) {
                $year = 'n/a';
                if ($release->date !== null) {
                    $year = $release->date->year;
                }
                if ($release->publisher !== null) {
                    $year = $year.' (by '.$release->publisher->pub_dev_name.')';
                }

                if ($year !== 'n/a') {
                    return $year;
                } else {
                    return null;
                }
            })
                ->filter(function ($value) {
                    return !is_null($value);
                })
                ->unique();

            if ($releases->isNotEmpty()) {
                $desc .= 'released in '.$releases->join(', ');
            }
        }

        if ($game->screenshots->isNotEmpty()) {
            $extraInfo[] = $game->screenshots->count().' '.Str::plural('screenshot', $game->screenshots->count());
        }

        if ($game->reviews->isNotEmpty()) {
            $extraInfo[] = $game->reviews->count().' '.Str::plural('review', $game->reviews->count());
        }

        if (count($extraInfo) > 0) {
            $desc .= ' ('.join(', ', $extraInfo).')';
        }

        $desc .= '.';

        if ($game->akas->isNotEmpty()) {
            $desc .= ' It is also known as: '.$game->akas->implode('aka_name', ', ').'.';
        }

        return $desc;
    }

    /**
     * Check if a game has boxscans.
     *
     * Currently looks both at the game-level boxscans, and release-level ones.
     *
     * @param App\Models\Game $game Game to check for boxscans.
     *
     * @return bool true if the game has boxscans, false otherwise.
     */
    public static function hasBoxscan(Game $game)
    {
        return $game->boxscans->isNotEmpty() || $game->releases
            ->filter(function ($release) {
                return $release->boxscans->isNotEmpty();
            })
            ->isNotEmpty();
    }

    /**
     * List developers for a game.
     *
     * @param App\Game $game Game to list the developers for
     *
     * @return string List of developers, comma-delimited
     */
    public static function developers(Game $game)
    {
        return $game->developers->map(function ($developer) {
            return $developer->pub_dev_name;
        })
            ->join(', ');
    }
}
