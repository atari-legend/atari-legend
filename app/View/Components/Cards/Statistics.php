<?php

namespace App\View\Components\Cards;

use App\Helpers\StatisticsHelper;
use Illuminate\View\Component;

class Statistics extends Component
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
        $stats = StatisticsHelper::getStatistics();

        return view('components.cards.statistics')
            ->with([
                'stats'        => $stats,
            ]);
    }
}
