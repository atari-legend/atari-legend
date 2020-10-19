<?php

namespace App\View\Components\Cards;

use App\Models\Interview as ModelsInterview;
use Illuminate\View\Component;

class Interview extends Component
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
        $interview = null;
        ModelsInterview::all()
            ->whenNotEmpty(function ($collection) use (&$interview) {
                $interview = $collection->random();
            });

        return view('components.cards.interview')
            ->with(['interview' => $interview]);
    }
}
