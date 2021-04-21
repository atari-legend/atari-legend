<?php

namespace App\View\Components\Cards;

use App\Models\MenuDisk;
use App\Models\MenuDiskDump;
use App\Models\User;
use Illuminate\View\Component;

class LatestMenus extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(?User $user)
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
            ->limit(20)
            ->get();

        $disks = MenuDisk::orderByDesc('updated_at')
            ->limit(20)
            ->get();

        $dumpsOrDisks = $dumps->merge($disks)
            ->sortByDesc('updated_at')
            ->take(20);

        return view('components.cards.latest-menus')
            ->with(['dumpsOrDisks' => $dumpsOrDisks]);
    }
}
