<?php

namespace App\View\Components\Cards;

use App\Models\MenuDisk as ModelMenuDisk;
use Illuminate\View\Component;

class MenuDisk extends Component
{
    public ?int $id = null;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(?int $id = null)
    {
        $this->id = $id;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    public function render()
    {
        $disk = null;
        if ($this->id !== null) {
            $disk = ModelMenuDisk::find($this->id);
        } else {
            $disk = ModelMenuDisk::has('screenshots')
                ->has('menuDiskDump')
                ->inRandomOrder()
                ->first();
        }

        return view('components.cards.menu')
            ->with([
                'id'   => $this->id,
                'disk' => $disk,
            ]);
    }
}
