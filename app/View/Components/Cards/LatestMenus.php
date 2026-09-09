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
        // The card renders each row's menu set, screenshot and dump, so load
        // them in one go rather than per row - this card is in the sidebar of
        // several pages, so the lazy loads were multiplying across the site.
        $diskRelations = ['menu.menuSet', 'screenshots', 'menuDiskDump'];

        $dumps = MenuDiskDump::with(
            collect($diskRelations)
                ->map(fn ($relation) => 'menuDisk.' . $relation)
                ->all()
        )
            ->orderByDesc('updated_at')
            ->limit(LatestMenus::MAX_ITEMS)
            ->get();

        $disks = MenuDisk::with($diskRelations)
            ->orderByDesc('updated_at')
            ->limit(LatestMenus::MAX_ITEMS)
            ->get();

        $dumpsOrDisks = $dumps->merge($disks)
            ->sortByDesc('updated_at')
            ->take(LatestMenus::MAX_ITEMS);

        return view('components.cards.latest-menus')
            ->with(['dumpsOrDisks' => $dumpsOrDisks]);
    }
}
