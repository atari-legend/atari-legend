<?php

namespace App\View\Components\Cards;

use App\Models\MagazineIssue;
use Illuminate\View\Component;

class RandomMagazineIssue extends Component
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
        $issue = MagazineIssue::whereNotNull('imgext')
            ->inRandomOrder()
            ->first();

        return view('components.cards.random-magazine-issue')
            ->with(['issue' => $issue]);
    }
}
