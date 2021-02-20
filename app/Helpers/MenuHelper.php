<?php

namespace App\Helpers;

/**
 * Helper for menus.
 */
class MenuHelper
{
    /**
     * @param int $disks Number of disks in the menu set
     * @param int $missing Number of missing disks
     *
     * @return float Percentage of completion of a menu set
     */
    public static function percentComplete(int $disks, int $missing): float
    {
        return ($disks - $missing) / $disks * 100;
    }

}
