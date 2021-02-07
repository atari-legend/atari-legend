<?php

namespace App\View\Components\Cards;

use App\Models\MenuDisk;
use Illuminate\View\Component;

class Menu extends Component
{
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
        $disk = MenuDisk::has('screenshots')
            ->has('menuDiskDump')
            ->get()
            ->random();

        return view('components.cards.menu')
            ->with([
                'disk' => $disk,
            ]);
    }
}
