<?php

namespace App\Helpers;

use App\Models\MenuSet;
use Illuminate\Support\Str;

/**
 * Helper for menus.
 */
class MenuHelper
{
    /**
     * @param int $disks   Number of disks in the menu set
     * @param int $missing Number of missing disks
     *
     * @return float Percentage of completion of a menu set
     */
    public static function percentComplete(int $disks, int $missing): float
    {
        return ($disks - $missing) / $disks * 100;
    }

    /**
     * @param object $set   Menuset
     * @param int $missing  Number of missing disks
     *
     * @return string Description of the menu set
     */
    public static function description(MenuSet $set, int $missingCount): string
    {
        $disks = $set->menus->pluck('disks')->flatten();
        $scrolls = $disks->filter(function ($disk) {
            return $disk->scrolltext !== null;
        })->count();

        return join(' ', [
            'Atari ST menu set '.$set->name.':',
            $disks->count().' '.Str::plural('disk', $disks->count()).',',
            $missingCount.' missing,',
            $scrolls.' '.Str::plural('scrolltext', $scrolls).',',
        ]);
    }
}
