<?php

namespace App\View\Components\Cards;

use App\Models\MenuDisk;
use App\Models\MenuDiskDump;
use Illuminate\View\Component;

class LatestMenus extends Component
{
    const MAX_ITEMS = 7;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    public function render()
    {
        $dumps = MenuDiskDump::orderByDesc('updated_at')
            ->limit(LatestMenus::MAX_ITEMS)
            ->get();

        $disks = MenuDisk::orderByDesc('updated_at')
            ->limit(LatestMenus::MAX_ITEMS)
            ->get();

        $dumpsOrDisks = $dumps->merge($disks)
            ->sortByDesc('updated_at')
            ->take(LatestMenus::MAX_ITEMS);

        return view('components.cards.latest-menus')
            ->with(['dumpsOrDisks' => $dumpsOrDisks]);
    }
}
