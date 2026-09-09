<?php

namespace App\View\Components\Cards;

use App\Models\Link as ModelsLink;
use Illuminate\View\Component;

class Link extends Component
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
        $link = ModelsLink::with('user')->inRandomOrder()->first();

        return view('components.cards.link')
            ->with(['link' => $link]);
    }
}
