<?php

namespace App\View\Components\Admin;

class Crumb
{
    public string $route;
    public string $label;
    public array $siblings;

    public function __construct(string $route, string $label, ?array $siblings = [])
    {
        $this->route = $route;
        $this->label = $label;
        $this->siblings = $siblings;
    }
}
