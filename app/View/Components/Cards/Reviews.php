<?php

namespace App\View\Components\Cards;

use Illuminate\View\Component;

class Reviews extends Component
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
        $reviews = \App\Review::all()
            ->sortByDesc('review_date')
            ->take(3);

        return view('components.cards.reviews')
            ->with(['reviews' => $reviews]);
    }
}
