<?php

namespace App\Helpers;

use App\Models\Game;
use App\Models\Release;
use Illuminate\Support\Str;

/**
 * Helper for Release descriptions.
 */
class ReleaseDescriptionHelper
{

    public static function descriptions(Release $release)
    {
        $desc = '';

        if ($release->name !== null && $release->name !== '') {
            $desc = $release->name;
        } else {
            $desc = 'This';
        }

        $desc .= ' is a ';

        if ($release->date !== null) {
            $desc .= $release->date->year . ' ';
        }
        $desc .= 'release of ' . $release->game->game_name . '. ';

        if ($release->locations->isNotEmpty()) {
            $desc .= 'It was released in ';
            $desc .= $release->locations->pluck('name')
                ->join(', ');
        }

        if ($release->publisher !== null || $release->distributors->isNotEmpty()) {
            if ($release->locations->isEmpty()) {
                $desc .= 'It was ';
            } else {
                $desc .= ', ';
            }

            if ($release->publisher !== null) {
                $desc .= 'published by ' . $release->publisher->pub_dev_name;
            }
            if ($release->distributors->isNotEmpty()) {
                if ($release->publisher !== null) {
                    $desc .= ', ';
                }

                $desc .= 'distributed by ' . $release
                    ->distributors
                    ->pluck('pub_dev_name')
                    ->join(', ');
            }
        }

        $desc .= '.';

        if ($release->license !== null && $release->license !== '') {
            $desc .= ' Its license is ' . $release->license . '.';
        }

        if ($release->akas->isNotEmpty()) {
            $desc .= 'It is also known as ' . $release
                ->akas
                ->map(function ($aka) {
                    $s = $aka->name;
                    if ($aka->language !== null) {
                        $s .= ' (' . $aka->language->name . ')';
                    }
                    return $s;
                })
                ->join(', ')
                . '.';
        }

        return [
            $desc,
            join(' ', [
                ReleaseDescriptionHelper::getResolutionsText($release),
                ReleaseDescriptionHelper::getEnhancementsText($release),
            ]),
            ReleaseDescriptionHelper::getMemoryText($release),
            ReleaseDescriptionHelper::getProtectionsText($release),
        ];
    }

    public static function getResolutionsText(Release $release)
    {
        $desc = '';

        if ($release->resolutions->isNotEmpty()) {
            $desc .= 'It supports the following ' .
                Str::plural('resolution', $release->resolutions->count()) .
                ': ' . $release
                ->resolutions
                ->pluck('name')
                ->join(', ');

            $desc .= '.';
        }

        return $desc;
    }

    public static function getEnhancementsText(Release $release)
    {
        $desc = '';
        if ($release->memoryEnhanced->isNotEmpty() || $release->systemEnhanced->isNotEmpty()) {
            $desc = 'It is enhanced for ';

            if ($release->systemEnhanced->isNotEmpty()) {
                $desc .= $release->systemEnhanced
                    ->map(function ($enhanced) {
                        $s = $enhanced->system->name;
                        if ($enhanced->enhancement !== null) {
                            $s .= ' (' . $enhanced->enhancement->name . ')';
                        }
                        return $s;
                    })
                    ->join(', ');
            }

            if ($release->memoryEnhanced->isNotEmpty()) {
                if ($release->systemEnhanced->isNotEmpty()) {
                    $desc .= ' and ';
                }
                $desc .= $release->memoryEnhanced
                    ->map(function ($enhanced) {
                        $s = $enhanced->memory->memory;
                        if ($enhanced->enhancement !== null) {
                            $s .= ' (' . $enhanced->enhancement->name . ')';
                        }
                        return $s;
                    })
                    ->join(', ');
            }

            $desc .= '.';
        }

        return $desc;
    }

    public static function getMemoryText(Release $release)
    {
        $desc = '';

        if ($release->memoryMinimums->isNotEmpty()) {
            $desc .= 'It requires a minimum memory of ';
            $desc .= $release->memoryMinimums
                ->pluck('memory')
                ->join(', ');
        }

        if ($release->memoryIncompatibles->isNotEmpty()) {
            if ($release->memoryMinimums->isNotEmpty()) {
                $desc .= ' and ';
            } else {
                $desc .= 'It ';
            }

            $desc .= 'is incompatible with ';
            $desc .= $release->memoryIncompatibles
                ->pluck('memory')
                ->join(', ');
        }

        if ($release->memoryMinimums->isNotEmpty() || $release->memoryIncompatibles->isNotEmpty()) {
            $desc .= '.';
        }

        return $desc;
    }

    public static function getProtectionsText(Release $release) {
        $desc = '';

        if ($release->copyProtections->isNotEmpty()) {
            $desc .= 'The game is copy protected via ';
            $desc .= $release->copyProtections
                ->map(function ($protection) {
                    $s = $protection->name;
                    if ($protection->pivot->notes !== null && $protection->pivot->notes !== '') {
                        $s .= '('.$protection->pivot->notes.')';
                    }
                    return $s;
                })
                ->join(', ');
        }

        if ($release->diskProtections->isNotEmpty()) {
            if ($release->copyProtections->isNotEmpty()) {
                $desc .= ' and the ';
            } else {
                $desc .= 'The ';
            }

            $desc .= 'media is protected with ';
            $desc .= $release->diskProtections
                ->map(function ($protection) {
                    $s = '';
                    if ($protection->id === 1) {
                        // Special case, the protection 1 is
                        // named 'Yes', when the protection system
                        // is unknown
                        $s = 'an unknown scheme';
                    } else {
                        $s = $protection->name;
                        if ($protection->pivot->notes !== null && $protection->pivot->notes !== '') {
                            $s .= ' ('.$protection->pivot->notes.')';
                        }
                    }
                    return $s;
                })
                ->join(', ');
        }

        if ($release->copyProtections->isNotEmpty() || $release->diskProtections->isNotEmpty()) {
            $desc .= '.';
        }

        return $desc;
    }
}
