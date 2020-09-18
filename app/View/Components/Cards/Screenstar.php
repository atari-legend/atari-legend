<?php

namespace App\View\Components\Cards;

use Illuminate\View\Component;

class Screenstar extends Component
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
        $screenstar = \App\Review::where('review_edit', 0)
            ->get()
            ->random();

        return view('components.cards.screenstar')
            ->with(['screenstar' => $screenstar]);
    }
}
