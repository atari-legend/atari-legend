<?php

namespace App\View\Components\Cards;

use App\Models\MagazineIssue;
use Illuminate\View\Component;

class LatestMagazineIssues extends Component
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
        $issues = MagazineIssue::orderByDesc('updated_at')
            ->limit(LatestMagazineIssues::MAX_ITEMS)
            ->get();

        return view('components.cards.latest-magazine-issues')
            ->with(['issues' => $issues]);
    }
}
