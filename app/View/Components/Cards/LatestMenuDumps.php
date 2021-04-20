<?php

namespace App\View\Components\Cards;

use App\Models\MenuDiskDump;
use App\Models\User;
use Illuminate\View\Component;

class LatestMenuDumps extends Component
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
        $dumps = MenuDiskDump::orderByDesc('created_at')
            ->limit(20)
            ->get();

        return view('components.cards.latest-menu-dumps')
            ->with(['dumps' => $dumps]);
    }
}
