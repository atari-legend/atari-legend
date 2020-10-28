<?php

namespace App\View\Components\Cards;

use App\Models\Interview as ModelsInterview;
use Illuminate\Database\Eloquent\Builder;
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

        // Only select interviews for which we have a picture
        // of the individual
        ModelsInterview::whereHas('individual', function (Builder $queryIndividual) {
            return $queryIndividual->whereHas('text', function (Builder $queryText) {
                return $queryText->whereNotNull('ind_imgext')
                    ->where('ind_imgext', '!=', '');
            });
        })
            ->get()
            ->whenNotEmpty(function ($collection) use (&$interview) {
                $interview = $collection->random();
            });

        return view('components.cards.interview')
            ->with(['interview' => $interview]);
    }
}
