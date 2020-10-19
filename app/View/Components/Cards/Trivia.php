<?php

namespace App\View\Components\Cards;

use App\Models\Trivia as ModelsTrivia;
use Illuminate\View\Component;

class Trivia extends Component
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
        $trivia = null;
        ModelsTrivia::all()
            ->whenNotEmpty(function ($collection) use (&$trivia) {
                $trivia = $collection->random();
            });

        return view('components.cards.trivia')
            ->with(['trivia' => $trivia]);
    }
}
