<?php

namespace App\View\Components\Cards;

use App\Models\Review;
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
        $screenstar = null;
        $firstRelease = null;
        $screenstar = Review::with(['user', 'game.releases'])
            ->where('submission', Review::REVIEW_PUBLISHED)
            ->inRandomOrder()
            ->first();

        if ($screenstar) {
            $firstRelease = $screenstar->game?->releases
                ->filter(function ($release) {
                    return $release->date !== null;
                })
                ->sortBy('date')
                ->first();
        }

        return view('components.cards.screenstar')
            ->with([
                'screenstar'    => $screenstar,
                'firstRelease'  => $firstRelease,
            ]);
    }
}
