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
        // The card renders both the author and the reviewed game per row.
        $reviews = Review::with(['user', 'games'])
            ->where('submission', Review::REVIEW_PUBLISHED)
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();

        return view('components.cards.reviews')
            ->with(['reviews' => $reviews]);
    }
}
