<?php

namespace App\View\Components\Admin;

class Crumb
{
    public string $route;
    public string $label;

    public function __construct(string $route, string $label)
    {
        $this->route = $route;
        $this->label = $label;
    }
}
