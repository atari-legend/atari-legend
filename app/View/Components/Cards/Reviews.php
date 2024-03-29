<?php

namespace App\View\Components\Cards;

use App\Models\Review;
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
        $reviews = Review::where('review_edit', Review::REVIEW_PUBLISHED)
            ->orderByDesc('review_date')
            ->limit(3)
            ->get();

        return view('components.cards.reviews')
            ->with(['reviews' => $reviews]);
    }
}
