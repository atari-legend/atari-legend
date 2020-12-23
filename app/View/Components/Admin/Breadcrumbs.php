<?php

namespace App\View\Components\Admin;

use Illuminate\View\Component;

class Breadcrumbs extends Component
{

    private array $crumbs;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(array $crumbs)
    {
        $this->crumbs = $crumbs;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    public function render()
    {
        return view('components.admin.breadcrumbs')
            ->with([
                'crumbs' => $this->crumbs,
            ]);
    }
}
